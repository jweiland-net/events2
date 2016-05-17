<?php

namespace JWeiland\Events2\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Stefan Froemken <projects@jweiland.net>, jweiland.net
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CreateMap
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * Current location record
     *
     * @var array
     */
    protected $currentRecord = array();

    /**
     * initializes this object.
     */
    public function init()
    {
        $this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
    }

    /**
     * try to find a similar poiCollection. If found connect it with current record.
     *
     * @param string                                   $status     "new" od something else to update the record
     * @param string                                   $table      The table name
     * @param int                                      $uid        The UID of the new or updated record. Can be prepended with NEW if record is new. Use: $this->substNEWwithIDs to convert
     * @param array                                    $fieldArray The fields of the current record
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
     */
    public function processDatamap_afterDatabaseOperations($status, $table, $uid, array $fieldArray, \TYPO3\CMS\Core\DataHandling\DataHandler $pObj)
    {
        // process this hook only on expected table
        if ($table !== 'tx_events2_domain_model_location') {
            return;
        }

        $this->init();

        if ($status === 'new') {
            $uid = current($pObj->substNEWwithIDs);
        }

        $this->currentRecord = $this->getFullRecord($table, $uid);

        if ($this->currentRecord['tx_maps2_uid']) {
            // sync categories
            $this->updateMmEntries();
        } else {
            // create new map-record and set it in relation
            $jSon = GeneralUtility::getUrl('http://maps.googleapis.com/maps/api/geocode/json?address=' . $this->getAddress() . '&sensor=false');
            $response = json_decode($jSon, true);
            if (is_array($response) && $response['status'] === 'OK') {
                $location = $response['results'][0]['geometry']['location'];
                $address = $response['results'][0]['formatted_address'];
                $poiUid = $this->createNewPoiCollection($location, $address);
                $this->updateCurrentRecord($poiUid);

                // sync categories
                $this->updateMmEntries();
            }
        }
    }

    /**
     * get full location record
     * While updating a record, only the changed fields will be in $fieldArray.
     *
     * @param string $table
     * @param int    $uid
     *
     * @return array
     */
    public function getFullRecord($table, $uid)
    {
        return BackendUtility::getRecord($table, $uid);
    }

    /**
     * get address for google search.
     *
     * @return string Prepared address for URI
     */
    public function getAddress()
    {
        $address = array();
        $address[] = $this->currentRecord['street'];
        $address[] = $this->currentRecord['house_number'];
        $address[] = $this->currentRecord['zip'];
        $address[] = $this->currentRecord['city'];
        $address[] = 'Deutschland';

        return rawurlencode(implode(' ', $address));
    }

    /**
     * try to find a similar poiCollection.
     *
     * @param array $location
     *
     * @return int The UID of the PoiCollection. 0 if not found
     */
    public function findPoiByLocation(array $location)
    {
        $poi = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
            'uid',
            'tx_maps2_domain_model_poicollection',
            'latitude='.$location['lat'].
            ' AND longitude='.$location['lng'].
            BackendUtility::BEenableFields('tx_maps2_domain_model_poicollection').
            BackendUtility::deleteClause('tx_maps2_domain_model_poicollection')
        );
        if ($poi) {
            return $poi['uid'];
        }

        return 0;
    }

    /**
     * update address record.
     *
     * @param int $poi
     */
    public function updateCurrentRecord($poi)
    {
        $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
            'tx_events2_domain_model_location',
            'uid='.$this->currentRecord['uid'],
            array('tx_maps2_uid' => $poi)
        );
        $this->currentRecord['tx_maps2_uid'] = $poi;
    }

    /**
     * creates a new poiCollection before updating the current address record.
     *
     * @param array  $location
     * @param string $address  Formatted Address returned from Google
     *
     * @return int insert UID
     */
    public function createNewPoiCollection(array $location, $address)
    {
        $tsConfig = $this->getTsConfig();

        $fieldValues = array();
        $fieldValues['pid'] = (int) $tsConfig['pid'];
        $fieldValues['tstamp'] = time();
        $fieldValues['crdate'] = time();
        $fieldValues['cruser_id'] = $GLOBALS['BE_USER']->user['uid'];
        $fieldValues['hidden'] = 0;
        $fieldValues['deleted'] = 0;
        $fieldValues['latitude'] = $location['lat'];
        $fieldValues['longitude'] = $location['lng'];
        $fieldValues['collection_type'] = 'Point';
        $fieldValues['title'] = $this->currentRecord['location'];
        $fieldValues['address'] = $address;

        $GLOBALS['TYPO3_DB']->exec_INSERTquery(
            'tx_maps2_domain_model_poicollection',
            $fieldValues
        );

        return $GLOBALS['TYPO3_DB']->sql_insert_id();
    }

    /**
     * get TSconfig.
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getTsConfig()
    {
        $tsConfig = BackendUtility::getModTSconfig($this->currentRecord['pid'], 'ext.events2');
        if (is_array($tsConfig) && !empty($tsConfig['properties']['pid'])) {
            return $tsConfig['properties'];
        } else {
            throw new \Exception('no PID for maps2 given. Please add this PID in extension configuration of events2 or set it in pageTSconfig', 1364889195);
        }
    }

    /**
     * define all asserted categories of this event also to the related poiCollections.
     */
    public function updateMmEntries()
    {
        // delete all with poiCollection related categories
        $GLOBALS['TYPO3_DB']->exec_DELETEquery(
            'sys_category_record_mm',
            'uid_foreign='.(int) $this->currentRecord['tx_maps2_uid'].
                ' AND tablenames="tx_maps2_domain_model_poicollection"'
        );

        // get all with events2 related categories
        $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            '*',
            'sys_category_record_mm',
            'uid_foreign='.$this->currentRecord['uid'].
                ' AND tablenames="tx_events2_domain_model_event"'
        );

        if (count($rows)) {
            // overwrite all rows as new data for poiCollection
            foreach ($rows as $key => $row) {
                $row['uid_foreign'] = (int) $this->currentRecord['tx_maps2_uid'];
                $row['tablenames'] = 'tx_maps2_domain_model_poicollection';
                $rows[$key] = $row;
            }

            // insert rows for with poiCollection related categories
            $GLOBALS['TYPO3_DB']->exec_INSERTmultipleRows(
                'sys_category_record_mm',
                array('uid_local', 'uid_foreign', 'tablenames', 'sorting', 'sorting_foreign'),
                $rows
            );
        }

        // update field categories of maps2-record (amount of relations)
        $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
            'tx_maps2_domain_model_poicollection',
            'uid='.(int) $this->currentRecord['tx_maps2_uid'],
            array('categories' => count($rows))
        );
    }
}
