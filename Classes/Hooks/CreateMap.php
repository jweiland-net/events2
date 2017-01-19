<?php

namespace JWeiland\Events2\Hooks;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Maps2\Domain\Model\Location;
use JWeiland\Maps2\Domain\Model\RadiusResult;
use JWeiland\Maps2\Utility\GeocodeUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

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
     * @var GeocodeUtility
     */
    protected $geocodeUtility;
    
    /**
     * @var ExtConf
     */
    protected $extConf;
    
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
        $this->extConf = GeneralUtility::makeInstance('JWeiland\\Events2\\Configuration\\ExtConf');
        $this->geocodeUtility = $this->objectManager->get('JWeiland\\Maps2\\Utility\\GeocodeUtility');
    }

    /**
     * try to find a similar poiCollection. If found connect it with current record.
     *
     * @param string      $status     "new" od something else to update the record
     * @param string      $table      The table name
     * @param int         $uid        The UID of the new or updated record. Can be prepended with NEW if record is new. Use: $this->substNEWwithIDs to convert
     * @param array       $fieldArray The fields of the current record
     * @param DataHandler $pObj
     */
    public function processDatamap_afterDatabaseOperations($status, $table, $uid, array $fieldArray, DataHandler $pObj)
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
            $response = $this->geocodeUtility->findPositionByAddress($this->getAddress());
            if ($response instanceof ObjectStorage && $response->count()) {
                /** @var RadiusResult $firstResult */
                $firstResult = $response->current();
                $location = $firstResult->getGeometry()->getLocation();
                $address = $firstResult->getFormattedAddress();
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
        if ($this->extConf->getDefaultCountry()) {
            $address[] = $this->extConf->getDefaultCountry();
        }

        return implode(' ', $address);
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
        $poi = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
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
        $this->getDatabaseConnection()->exec_UPDATEquery(
            'tx_events2_domain_model_location',
            'uid=' . (int)$this->currentRecord['uid'],
            array(
                'tx_maps2_uid' => (int)$poi
            )
        );
        $this->currentRecord['tx_maps2_uid'] = (int)$poi;
    }

    /**
     * creates a new poiCollection before updating the current address record.
     *
     * @param Location $location
     * @param string   $address  Formatted Address returned from Google
     *
     * @return int insert UID
     */
    public function createNewPoiCollection(Location $location, $address)
    {
        $tsConfig = $this->getTsConfig();

        $fieldValues = array();
        $fieldValues['pid'] = (int)$tsConfig['pid'];
        $fieldValues['tstamp'] = time();
        $fieldValues['crdate'] = time();
        $fieldValues['cruser_id'] = $GLOBALS['BE_USER']->user['uid'];
        $fieldValues['hidden'] = 0;
        $fieldValues['deleted'] = 0;
        $fieldValues['latitude'] = $location->getLat();
        $fieldValues['longitude'] = $location->getLng();
        $fieldValues['collection_type'] = 'Point';
        $fieldValues['title'] = $this->currentRecord['location'];
        $fieldValues['address'] = $address;

        $this->getDatabaseConnection()->exec_INSERTquery(
            'tx_maps2_domain_model_poicollection',
            $fieldValues
        );

        return $this->getDatabaseConnection()->sql_insert_id();
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
     * Define all asserted categories of this event also to the related poiCollections.
     *
     * @return void
     */
    public function updateMmEntries()
    {
        // delete all with poiCollection related categories
        $this->getDatabaseConnection()->exec_DELETEquery(
            'sys_category_record_mm',
            sprintf(
                'uid_foreign=%d AND tablenames=%s',
                (int)$this->currentRecord['tx_maps2_uid'],
                $this->getDatabaseConnection()->fullQuoteStr(
                    'tx_maps2_domain_model_poicollection',
                    'sys_category_record_mm'
                )
            )
        );

        // get all with events2 related categories
        $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            'sys_category_record_mm',
            sprintf(
                'uid_foreign=%d AND tablenames=%s',
                (int)$this->currentRecord['uid'],
                $this->getDatabaseConnection()->fullQuoteStr(
                    'tx_events2_domain_model_event',
                    'sys_category_record_mm'
                )
            )
        );

        if (count($rows)) {
            $row = array();
            // overwrite all rows as new data for poiCollection
            foreach ($rows as $key => $row) {
                $row['uid_foreign'] = (int)$this->currentRecord['tx_maps2_uid'];
                $row['tablenames'] = 'tx_maps2_domain_model_poicollection';
                $rows[$key] = $row;
            }

            // insert rows for with poiCollection related categories
            $this->getDatabaseConnection()->exec_INSERTmultipleRows(
                'sys_category_record_mm',
                array_keys($row),
                $rows
            );
        }

        // update field categories of maps2-record (amount of relations)
        $this->getDatabaseConnection()->exec_UPDATEquery(
            'tx_maps2_domain_model_poicollection',
            'uid=' . (int)$this->currentRecord['tx_maps2_uid'],
            array(
                'categories' => count($rows)
            )
        );
    }
    
    /**
     * Get TYPO3s Database Connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
