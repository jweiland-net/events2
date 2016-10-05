<?php

namespace JWeiland\Events2\Command;

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
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use JWeiland\Events2\Service\DayRelations;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RepairCommandController extends CommandController
{
    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @var \JWeiland\Events2\Utility\DateTimeUtility
     */
    protected $dateTimeUtility;
    
    /**
     * Needed to wrap activity bar:
     * ...........F.......
     * ....N....S.........
     *
     * @var int
     */
    protected $rowCounter = 0;

    /**
     * inject DateTime Utility.
     *
     * @param \JWeiland\Events2\Utility\DateTimeUtility $dateTimeUtility
     */
    public function injectDateTimeUtility(\JWeiland\Events2\Utility\DateTimeUtility $dateTimeUtility)
    {
        $this->dateTimeUtility = $dateTimeUtility;
    }

    /**
     * initializes this object.
     */
    public function initializeObject()
    {
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];
    }

    /**
     * Repair events.
     */
    public function eventsCommand()
    {
        $this->outputLine('Start repairing day records of events');
    
        $this->outputLine('');
        $this->removeDuplicatedDayRecords();
        $this->outputLine('');
        $this->removeDaysWithoutEventRelation();
        $this->outputLine('');
        $this->removeRecordsWithoutRelationToEventDay();
        $this->outputLine('');
        $this->removeDuplicateDaysWithDifferentTimes();
        $this->outputLine('');
        $this->updateAmountOfRelatedRecords();
        $this->outputLine('');
        $this->reGenerateDayRelations();
        $this->outputLine('');
        $this->updateReferenceIndex();
    }
    
    /**
     * In previous versions we had no check to prevent inserting duplicate records.
     *
     * @return void
     */
    protected function removeDuplicatedDayRecords()
    {
        $counter = 0;
        $this->rowCounter = 0;
        $this->outputLine('Search for duplicate day records. This script removes them and reassign events to the not deleted one.');
    
        // get all duplicated day records
        $rows = $this->databaseConnection->exec_SELECTgetRows(
            'uid, day, COUNT(*) as Anzahl',
            'tx_events2_domain_model_day',
            '1=1',
            'day HAVING Anzahl > 1', 'Anzahl DESC', ''
        );
        if (!empty($rows)) {
            foreach ($rows as $row) {
                // get all days which have to be reassigned to new records
                $duplicateDays = $this->databaseConnection->exec_SELECTgetRows(
                    'uid',
                    'tx_events2_domain_model_day',
                    'day='.$row['day'].' AND uid<>'.(int)$row['uid']
                );
                if (!empty($duplicateDays)) {
                    foreach ($duplicateDays as $duplicateDay) {
                        // check if there are relations to this record
                        $eventDayRelations = $this->databaseConnection->exec_SELECTgetRows(
                            'uid_local, uid_foreign',
                            'tx_events2_event_day_mm',
                            'uid_foreign='.(int)$duplicateDay['uid']
                        );
                        if ($eventDayRelations) {
                            foreach ($eventDayRelations as $eventDayRelation) {
                                // each relation has to be updated to the one and only valid day record
                                $this->databaseConnection->exec_UPDATEquery(
                                    'tx_events2_event_day_mm',
                                    'uid_local='.(int)$eventDayRelation['uid_local'].' AND uid_foreign='.(int)$eventDayRelation['uid_foreign'],
                                    array(
                                        'uid_foreign' => (int)$row['uid'],
                                    )
                                );
                            }
                            // now we can delete the duplicate day
                            $this->databaseConnection->exec_DELETEquery(
                                'tx_events2_domain_model_day',
                                'uid='.(int)$duplicateDay['uid']
                            );
                            $this->echoValue();
                            ++$counter;
                        }
                    }
                }
            }
        }
        $this->outputLine(PHP_EOL . 'I have deleted ' . $counter . ' duplicate day records and reassigned events to new once.');
    }
    
    /**
     * If an event was deleted, the relation to the day records will not be deleted in earlier versions
     *
     * @return void
     */
    protected function removeDaysWithoutEventRelation()
    {
        $counter = 0;
        $this->rowCounter = 0;
        $this->outputLine('Search for days which do not have a relation to an event anymore and remove entry from day table.');
    
        // get all day records
        // @ToDo: Maybe it's easier to check it with help of JOIN and NULL
        $rows = $this->databaseConnection->exec_SELECTgetRows(
            'uid',
            'tx_events2_domain_model_day',
            '1=1'
        );
        if (!empty($rows)) {
            foreach ($rows as $row) {
                // check if a relation exists
                $mmRelation = $this->databaseConnection->exec_SELECTgetSingleRow(
                    'uid_local, uid_foreign',
                    'tx_events2_event_day_mm',
                    'uid_foreign='.(int)$row['uid']
                );
                // if there is no relation, the day record can be deleted
                if (empty($mmRelation)) {
                    $this->databaseConnection->exec_DELETEquery(
                        'tx_events2_domain_model_day',
                        'uid='.(int)$row['uid']
                    );
                    $this->echoValue();
                    ++$counter;
                }
            }
        }
        $this->outputLine(PHP_EOL . 'I have deleted ' . $counter . ' day records which do not have a mm relation anymore.');
    }
    
    /**
     * Remove all MM records with no relation to event and/or day
     *
     * @return void
     */
    protected function removeRecordsWithoutRelationToEventDay()
    {
        $counter = 0;
        $this->rowCounter = 0;
        $this->outputLine('Search for MM-relations which do not have a relation to an event or a day anymore and remove entry from MM table.');
    
        // get all MM records
        $rows = $this->databaseConnection->exec_SELECTgetRows(
            'uid_local, uid_foreign',
            'tx_events2_event_day_mm',
            '1=1'
        );
        if (!empty($rows)) {
            foreach ($rows as $row) {
                // remove MM Relation if day record does not exists
                $dayRecord = $this->databaseConnection->exec_SELECTgetSingleRow(
                    'uid',
                    'tx_events2_domain_model_day',
                    'uid='.(int)$row['uid_foreign']
                );
                if (empty($dayRecord)) {
                    $this->databaseConnection->exec_DELETEquery(
                        'tx_events2_event_day_mm',
                        'uid_local='.(int)$row['uid_local'].' AND uid_foreign='.(int)$row['uid_foreign']
                    );
                    $this->echoValue();
                    ++$counter;
                }
                // remove MM Relation if event record does not exists
                $eventRecord = $this->databaseConnection->exec_SELECTgetSingleRow(
                    'uid',
                    'tx_events2_domain_model_event',
                    'uid='.(int)$row['uid_local']
                );
                if (empty($eventRecord)) {
                    $this->databaseConnection->exec_DELETEquery(
                        'tx_events2_event_day_mm',
                        'uid_local='.(int)$row['uid_local'].' AND uid_foreign='.(int)$row['uid_foreign']
                    );
                    $this->echoValue();
                    ++$counter;
                }
            }
        }
        $this->outputLine(PHP_EOL . 'I have deleted ' . $counter . ' MM records which do not have a relation to an event or a day.');
    }
    
    /**
     * In previous versions we have generated day records that way: new \DateTime('15.03.2015')
     * But PHP adds current time to that date. So it results in multiple day records with same day, but different times
     * Now solved with sanitizing dates with ->modify('midnight')
     *
     * @return void
     */
    protected function removeDuplicateDaysWithDifferentTimes()
    {
        $counter = 0;
        $this->rowCounter = 0;
        $this->outputLine('Search for multiple days for one day. This is the case, if time is not 00:00:00. This script removes them and reassign events to the correct one.');
    
        // get all day records
        $rows = $this->databaseConnection->exec_SELECTgetRows(
            'uid, pid, day, cruser_id',
            'tx_events2_domain_model_day',
            '1=1'
        );
        foreach ($rows as $row) {
            // Check if time of day is always 00:00:00
            $correctDate = $this->dateTimeUtility->standardizeDateTimeObject(new \DateTime(date('c', $row['day'])));
            $eventDate = new \DateTime(date('c', $row['day']));
        
            if ($correctDate != $eventDate) {
                // the times are different. Try to find a day with correct timestamp or create a new correct one
                $day = $this->databaseConnection->exec_SELECTgetSingleRow(
                    'uid',
                    'tx_events2_domain_model_day',
                    'day='.(int)$correctDate->format('U')
                );
                if (empty($day)) {
                    // we have to create a new day record
                    $fieldsArray = array();
                    $fieldsArray['day'] = (int)$correctDate->format('U');
                    $fieldsArray['tstamp'] = time();
                    $fieldsArray['pid'] = (int)$row['pid'];
                    $fieldsArray['crdate'] = time();
                    $fieldsArray['cruser_id'] = (int)$row['cruser_id'];
                    $this->databaseConnection->exec_INSERTquery('tx_events2_domain_model_day', $fieldsArray);
                    $dayUid = (int)$this->databaseConnection->sql_insert_id();
                } else {
                    // we can use the found record as new relation
                    $dayUid = $day['uid'];
                }
                // update MM-Relation
                $this->databaseConnection->exec_UPDATEquery(
                    'tx_events2_event_day_mm',
                    'uid_foreign=' . (int)$row['uid'],
                    array(
                        'uid_foreign' => (int)$dayUid,
                    )
                );
                // delete wrong day record
                $this->databaseConnection->exec_DELETEquery(
                    'tx_events2_domain_model_day',
                    'uid=' . (int)$row
                );
                $this->echoValue();
                ++$counter;
            }
        }
        $this->outputLine(PHP_EOL . 'I have updated ' . $counter . ' MM records and deleted its day records which have a wrong time.');
    }
    
    /**
     * TYPO3 saves the amount of records in 1:N relations.
     * This script updates this value, if not set
     *
     * @return void
     */
    protected function updateAmountOfRelatedRecords()
    {
        $counter = 0;
        $this->rowCounter = 0;
        $this->outputLine('Update amount of related day records in events col "days".');
    
        // get all event records
        $rows = $this->databaseConnection->exec_SELECTgetRows(
            'uid, days',
            'tx_events2_domain_model_event',
            '1=1'
        );
    
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $amount = $this->databaseConnection->exec_SELECTcountRows(
                    '*',
                    'tx_events2_event_day_mm',
                    'uid_local=' . (int)$row['uid']
                );
                $this->databaseConnection->exec_UPDATEquery(
                    'tx_events2_domain_model_event',
                    'uid=' . (int)$row['uid'],
                    array(
                        'days' => (int)$amount,
                    )
                );
                $this->echoValue();
                ++$counter;
            }
        }
        $this->outputLine(PHP_EOL . 'I have updated ' . $counter . ' event records and set the correct amount of related day records.');
    }
    
    /**
     * After solving bugs in DayGenerator it would be good to recreate all days for events
     *
     * @return void
     */
    protected function reGenerateDayRelations()
    {
        $counter = 0;
        $this->rowCounter = 0;
        $this->outputLine('Delete all event-day relations and recreate them.');
    
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        /** @var DayRelations $dayRelations */
        $dayRelations = $objectManager->get('JWeiland\\Events2\\Service\\DayRelations');
    
        // get all event records
        $rows = $this->databaseConnection->exec_SELECTgetRows(
            '*',
            'tx_events2_domain_model_event',
            '1=1'
        );
    
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $dayRelations->createDayRelations($row);
                $this->echoValue();
                ++$counter;
            }
        }
        $this->outputLine(PHP_EOL . 'We have recreated the day records for ' . $counter . ' event records.');
    }
    
    /**
     * Update TYPO3s Reference index
     *
     * @return void
     */
    protected function updateReferenceIndex()
    {
        $counter = 0;
        $this->rowCounter = 0;
        $this->outputLine('Update reference index.');
    
        // get all event records
        $rows = $this->databaseConnection->exec_SELECTgetRows(
            'uid',
            'tx_events2_domain_model_event',
            '1=1'
        );
        if (!empty($rows)) {
            /** @var \TYPO3\CMS\Core\Database\ReferenceIndex $refObject */
            $refObject = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\ReferenceIndex');
        
            foreach ($rows as $row) {
                $refObject->updateRefIndexTable('tx_events2_domain_model_event', $row['uid']);
                $this->echoValue();
                ++$counter;
            }
        }
        $this->outputLine(PHP_EOL . 'I have updated ' . $counter . ' reference indexes for events.');
    }
    
    /**
     * echo "whatEver"
     *
     * @param string $value
     * @param boolean $reset
     * @return void
     */
    protected function echoValue($value = '.', $reset = FALSE) {
        if ($reset) $this->rowCounter = 0;
        if ($this->rowCounter < 40) {
            echo $value;
            $this->rowCounter++;
        } else {
            echo PHP_EOL . $value;
            $this->rowCounter = 1;
        }
    }
}

