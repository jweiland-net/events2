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
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Maps2\Domain\Model\Location;
use JWeiland\Maps2\Domain\Model\RadiusResult;
use JWeiland\Maps2\Utility\GeocodeUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class DataHandlerHook
 *
 * @package JWeiland\Events2\Hooks
 */
class DataHandlerHook
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var GeocodeUtility
     */
    protected $geocodeUtility;

    /**
     * DataHandlerHook constructor.
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->geocodeUtility = $this->objectManager->get(GeocodeUtility::class);
    }

    /**
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     */
    public function processDatamap_afterAllOperations($dataHandler)
    {
        if (array_key_exists('tx_events2_domain_model_event', $dataHandler->datamap)) {
            foreach ($dataHandler->datamap['tx_events2_domain_model_event'] as $eventUid => $eventRecord) {
                $this->addDayRelationsForEvent($this->getRealUid($eventUid, $dataHandler));
            }
        }
    }

    /**
     * try to find a similar poiCollection. If found connect it with current record.
     *
     * @param string $status "new" od something else to update the record
     * @param string $table The table name
     * @param int $uid The UID of the new or updated record. Can be prepended with NEW if record is new. Use: $this->substNEWwithIDs to convert
     * @param array $fieldArray The fields of the current record
     * @param DataHandler $dataHandler
     *
     * @throws \Exception
     */
    public function processDatamap_afterDatabaseOperations($status, $table, $uid, array $fieldArray, $dataHandler)
    {
        // process this hook only on expected table
        if ($table !== 'tx_events2_domain_model_location') {
            return;
        }

        $eventLocation = BackendUtility::getRecord($table, $this->getRealUid($uid, $dataHandler));

        if ($eventLocation['tx_maps2_uid']) {
            // sync categories
            $this->updateMmEntries($eventLocation);
        } else {
            // create new map-record and set it in relation
            $response = $this->geocodeUtility->findPositionByAddress($this->getAddress($eventLocation));
            if ($response instanceof ObjectStorage && $response->count()) {
                /** @var RadiusResult $firstResult */
                $firstResult = $response->current();
                $location = $firstResult->getGeometry()->getLocation();
                $address = $firstResult->getFormattedAddress();
                $poiUid = $this->createNewPoiCollection($location, $address, $eventLocation);
                $this->updateCurrentRecord($poiUid, $eventLocation);

                // sync categories
                $this->updateMmEntries($eventLocation);
            }
        }
    }

    /**
     * Add day relations to event
     *
     * @param int $eventUid
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function addDayRelationsForEvent($eventUid)
    {
        /** @var DayRelationService $dayRelationService */
        $dayRelationService = $this->objectManager->get(DayRelationService::class);
        $dayRelationService->createDayRelations($eventUid);
    }

    /**
     * If a record was new, its uid is not an int. It's a string starting with "NEW"
     * This method returns the real uid as int.
     *
     * @param string $uid
     * @param DataHandler $dataHandler
     *
     * @return int
     */
    protected function getRealUid($uid, $dataHandler)
    {
        if (GeneralUtility::isFirstPartOfStr($uid, 'NEW')) {
            $uid = $dataHandler->substNEWwithIDs[$uid];
        }
        return (int)$uid;
    }

    /**
     * get address for google search.
     *
     * @param array $eventLocation
     *
     * @return string Prepared address for URI
     */
    public function getAddress(array $eventLocation)
    {
        $address = [];
        $addressParts = ['street', 'house_number', 'zip', 'city', 'country'];

        foreach ($addressParts as $addressPart) {
            $value = trim($eventLocation[$addressPart]);
            if ($addressPart === 'country') {
                $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
                    'cn_short_en',
                    'static_countries',
                    'uid=' . (int)$eventLocation[$addressPart]
                );
                if (!empty($row)) {
                    $address[] = $row['cn_short_en'];
                }
            } elseif (!empty($value)) {
                $address[] = $eventLocation[$addressPart];
            }
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
     * @param array $eventLocation
     *
     * @return void
     */
    public function updateCurrentRecord($poi, array $eventLocation)
    {
        $this->getDatabaseConnection()->exec_UPDATEquery(
            'tx_events2_domain_model_location',
            'uid=' . (int)$eventLocation['uid'],
            [
                'tx_maps2_uid' => (int)$poi
            ]
        );
        $eventLocation['tx_maps2_uid'] = (int)$poi;
    }

    /**
     * creates a new poiCollection before updating the current address record.
     *
     * @param Location $location
     * @param string $address Formatted Address returned from Google
     * @param array $eventLocation
     *
     * @return int insert UID
     *
     * @throws \Exception
     */
    public function createNewPoiCollection(Location $location, $address, array $eventLocation)
    {
        $tsConfig = $this->getTsConfig($eventLocation);

        $fieldValues = [];
        $fieldValues['pid'] = (int)$tsConfig['pid'];
        $fieldValues['tstamp'] = time();
        $fieldValues['crdate'] = time();
        $fieldValues['cruser_id'] = $GLOBALS['BE_USER']->user['uid'];
        $fieldValues['hidden'] = 0;
        $fieldValues['deleted'] = 0;
        $fieldValues['latitude'] = $location->getLat();
        $fieldValues['longitude'] = $location->getLng();
        $fieldValues['collection_type'] = 'Point';
        $fieldValues['title'] = $eventLocation['location'];
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
     * @param array $eventLocation
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getTsConfig(array $eventLocation)
    {
        $tsConfig = BackendUtility::getModTSconfig($eventLocation['pid'], 'ext.events2');
        if (is_array($tsConfig) && !empty($tsConfig['properties']['pid'])) {
            return $tsConfig['properties'];
        } else {
            throw new \Exception('no PID for maps2 given. Please add this PID in extension configuration of events2 or set it in pageTSconfig', 1364889195);
        }
    }

    /**
     * Define all asserted categories of this event also to the related poiCollections.
     *
     * @param array $eventLocation
     *
     * @return void
     */
    public function updateMmEntries(array $eventLocation)
    {
        // delete all with poiCollection related categories
        $this->getDatabaseConnection()->exec_DELETEquery(
            'sys_category_record_mm',
            sprintf(
                'uid_foreign=%d AND tablenames=%s',
                (int)$eventLocation['tx_maps2_uid'],
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
                (int)$eventLocation['uid'],
                $this->getDatabaseConnection()->fullQuoteStr(
                    'tx_events2_domain_model_event',
                    'sys_category_record_mm'
                )
            )
        );

        if (count($rows)) {
            $row = [];
            // overwrite all rows as new data for poiCollection
            foreach ($rows as $key => $row) {
                $row['uid_foreign'] = (int)$eventLocation['tx_maps2_uid'];
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
            'uid=' . (int)$eventLocation['tx_maps2_uid'],
            [
                'categories' => count($rows)
            ]
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
