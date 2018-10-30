<?php

namespace JWeiland\Events2\Hooks;

/*
 * This file is part of the events2 project.
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
use JWeiland\Maps2\Domain\Model\RadiusResult;
use JWeiland\Maps2\Service\GoogleMapsService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * This hook class should only be loaded, if EXT:maps2 was activated
 * Please check ext_localconf.php for correct settings
 *
 */
class CreateMaps2RecordHook
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var GoogleMapsService
     */
    protected $googleMapsService;

    /**
     * DataHandlerHook constructor.
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->googleMapsService = $this->objectManager->get(GoogleMapsService::class);
    }

    /**
     * try to find a similar poiCollection. If found connect it with current record.
     *
     * @param string $status "new" od something else to update the record
     * @param string $table The table name
     * @param int $uid The UID of the new or updated record. Can be prepended with NEW if record is new. Use: $this->substNEWwithIDs to convert
     * @param array $fieldArray The fields of the current record
     * @param DataHandler $dataHandler
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
            // sync events2 with POI categories
            $this->updateMmEntries($eventLocation);
        } else {
            // create new map-record and set it in relation
            $radiusResult = $this->googleMapsService->getFirstFoundPositionByAddress($this->getAddress($eventLocation));
            if ($radiusResult instanceof RadiusResult) {
                $tsConfig = $this->getTsConfig($eventLocation);
                $this->googleMapsService->assignPoiCollectionToForeignRecord(
                    $this->googleMapsService->createNewPoiCollection(
                        (int)$tsConfig['pid'],
                        $radiusResult,
                        [
                            'title' => $eventLocation['location']
                        ]
                    ),
                    $eventLocation,
                    'tx_events2_domain_model_location',
                    'tx_maps2_uid'
                );
            }
        }
    }

    /**
     * If a record was new, its uid is not an int. It's a string starting with "NEW"
     * This method returns the real uid as int.
     *
     * @param string $uid
     * @param DataHandler $dataHandler
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
     * get TSconfig.
     *
     * @param array $eventLocation
     * @return array
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
