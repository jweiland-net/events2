<?php

namespace JWeiland\Events2\Tca;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CreateUpdateDays
{
    /**
     * @var \JWeiland\Events2\Service\DayRelations
     */
    protected $dayRelations;

    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * initializes this object.
     */
    public function init()
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $this->dayRelations = $objectManager->get('JWeiland\\Events2\\Service\\DayRelations');
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];
    }

    /**
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
     */
    public function processDatamap_afterAllOperations(DataHandler $pObj)
    {
        // return if unexpected table
        if (!array_key_exists('tx_events2_domain_model_event', $pObj->datamap)) {
            return;
        }

        $this->init();

        $event = $this->getFullEventRecord('tx_events2_domain_model_event', $pObj);
        $this->dayRelations->createDayRelations($event);
    }

    /**
     * get full event record
     * While updating a record only the changed fields will be in $fieldArray.
     *
     * @param string                                   $table
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     *
     * @return array
     */
    public function getFullEventRecord($table, DataHandler $dataHandler)
    {
        $uid = $this->getRealUid(key($dataHandler->datamap['tx_events2_domain_model_event']), $dataHandler);
        $event = BackendUtility::getRecord($table, $uid);
        $this->addExceptionsToEvent($event, $dataHandler);

        // as we don't get the relation to which col the time record belongs in DataHandler,
        // we have to select them again from DB
        $this->addTimeRecordsForColumn($event, 'event', 'event_time');
        $this->addTimeRecordsForColumn($event, 'event', 'multiple_times');
        $this->addTimeRecordsForColumn($event, 'event', 'different_times');

        return $event;
    }
    
    /**
     * Add Exceptions for Event
     *
     * @param array $event
     * @param DataHandler $dataHandler
     *
     * @return void
     */
    protected function addExceptionsToEvent(array &$event, $dataHandler)
    {
        if ($event['exceptions']) {
            $exceptions = array();
            foreach (array_keys($dataHandler->datamap['tx_events2_domain_model_exception']) as $exception) {
                $exceptions[] = $this->getRealUid($exception, $dataHandler);
            }
            $where = sprintf(
                'uid IN (%s) AND exception_type IN (%s, %s) %s %s',
                implode(',', $exceptions),
                $this->getDatabaseConnection()->fullQuoteStr('Add', 'tx_events2_domain_model_exception'),
                $this->getDatabaseConnection()->fullQuoteStr('Time', 'tx_events2_domain_model_exception'),
                BackendUtility::BEenableFields('tx_events2_domain_model_exception'),
                BackendUtility::deleteClause('tx_events2_domain_model_exception')
            );
            $event['exceptions'] = $this->databaseConnection->exec_SELECTgetRows(
                'uid, event, exception_date, exception_time, exception_type',
                'tx_events2_domain_model_exception',
                $where
            );
            if (!empty($event['exceptions'])) {
                foreach ($event['exceptions'] as &$exception) {
                    $this->addTimeRecordsForColumn($exception, 'exception', 'exception_time');
                }
            }
        } else {
            $event['exceptions'] = array();
        }
    }
    
    /**
     * Add time records for specified columns
     *
     * @param array $record
     * @param string $recordType 'event' or 'exception'
     * @param string $column
     *
     * @return void
     */
    protected function addTimeRecordsForColumn(&$record, $recordType, $column)
    {
        $where = sprintf(
            '%s=%d AND type=%s %s %s',
            $recordType,
            (int)$record['uid'],
            $this->getDatabaseConnection()->fullQuoteStr($column, 'tx_events2_domain_model_time'),
            BackendUtility::BEenableFields('tx_events2_domain_model_time'),
            BackendUtility::deleteClause('tx_events2_domain_model_time')
        );
        $rows = $this->databaseConnection->exec_SELECTgetRows(
            'uid, time_begin, time_end, time_entry, duration, type',
            'tx_events2_domain_model_time',
            $where
        );
        if (empty($rows)) {
            // @ToDo: ErrorHandling
            $rows = array();
        }
        $record[$column] = $rows;
    }

    /**
     * if a record was new its uid is not an int. It's a string starting with "NEW"
     * This method returns the real uid as int.
     *
     * @param string                                   $uid
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     *
     * @return int
     */
    protected function getRealUid($uid, DataHandler $dataHandler)
    {
        if (GeneralUtility::isFirstPartOfStr($uid, 'NEW')) {
            $uid = $dataHandler->substNEWwithIDs[$uid];
        }

        return $uid;
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
