<?php

namespace JWeiland\Events2\Command;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Stefan Froemken <projects@jweiland.net>, jweiland.net
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RepairCommandController extends \TYPO3\CMS\Extbase\Mvc\Controller\CommandController
{
    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection = null;

    /**
     * @var \JWeiland\Events2\Utility\DateTimeUtility
     */
    protected $dateTimeUtility = null;

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
        $counter = array(
            'duplicated' => 0,
            'daysWithoutMm' => 0,
            'mmWithoutRelation' => 0,
            'wrongTime' => 0,
            'eventCols' => 0,
            'refIndex' => 0,
        );

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
                    'day='.$row['day'].' AND uid<>'.(int) $row['uid']
                );
                if (!empty($duplicateDays)) {
                    foreach ($duplicateDays as $duplicateDay) {
                        // check if there are relations to this record
                        $eventDayRelations = $this->databaseConnection->exec_SELECTgetRows(
                            'uid_local, uid_foreign',
                            'tx_events2_event_day_mm',
                            'uid_foreign='.(int) $duplicateDay['uid']
                        );
                        if ($eventDayRelations) {
                            foreach ($eventDayRelations as $eventDayRelation) {
                                // each relation has to be updated to the one and only valid day record
                                $this->databaseConnection->exec_UPDATEquery(
                                    'tx_events2_event_day_mm',
                                    'uid_local='.(int) $eventDayRelation['uid_local'].' AND uid_foreign='.(int) $eventDayRelation['uid_foreign'],
                                    array(
                                        'uid_foreign' => (int) $row['uid'],
                                    )
                                );
                            }
                            // now we can delete the duplicate day
                            $this->databaseConnection->exec_DELETEquery(
                                'tx_events2_domain_model_day',
                                'uid='.(int) $duplicateDay['uid']
                            );
                            ++$counter['duplicated'];
                        }
                    }
                }
            }
        }

        $this->outputLine('I have deleted '.$counter['duplicated'].' duplicate records and reassigned them.');
        $this->outputLine('');

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
                    'uid_foreign='.(int) $row['uid']
                );
                // if there is no relation, the day record can be deleted
                if (empty($mmRelation)) {
                    $this->databaseConnection->exec_DELETEquery(
                        'tx_events2_domain_model_day',
                        'uid='.(int) $row['uid']
                    );
                    ++$counter['daysWithoutMm'];
                }
            }
        }

        $this->outputLine('I have deleted '.$counter['daysWithoutMm'].' day records which do not have a mm relation.');
        $this->outputLine('');

        $this->outputLine('Search for MM-relations which do not have a relation to an event or a day anymore and remove entry from MM.');

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
                    'uid='.(int) $row['uid_foreign']
                );
                if (empty($dayRecord)) {
                    $this->databaseConnection->exec_DELETEquery(
                        'tx_events2_event_day_mm',
                        'uid_local='.(int) $row['uid_local'].' AND uid_foreign='.(int) $row['uid_foreign']
                    );
                    ++$counter['mmWithoutRelation'];
                }
                // remove MM Relation if event record does not exists
                $eventRecord = $this->databaseConnection->exec_SELECTgetSingleRow(
                    'uid',
                    'tx_events2_domain_model_event',
                    'uid='.(int) $row['uid_local']
                );
                if (empty($eventRecord)) {
                    $this->databaseConnection->exec_DELETEquery(
                        'tx_events2_event_day_mm',
                        'uid_local='.(int) $row['uid_local'].' AND uid_foreign='.(int) $row['uid_foreign']
                    );
                    ++$counter['mmWithoutRelation'];
                }
            }
        }

        $this->outputLine('I have deleted '.$counter['mmWithoutRelation'].' mm records which do not have a relation to an event or a day.');
        $this->outputLine('');

        $this->outputLine('Search for multiple days for one day. This is the case, if time is not 00:00:00. This script removes them and reassign events to the correct one.');

        // get all day records
        $rows = $this->databaseConnection->exec_SELECTgetRows(
            'uid, pid, day, cruser_id, sys_language_uid',
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
                    'day='.(int) $correctDate->format('U')
                );
                if (empty($day)) {
                    // we have to create a new day record
                    $fieldsArray = array();
                    $fieldsArray['day'] = (int) $correctDate->format('U');
                    $fieldsArray['tstamp'] = time();
                    $fieldsArray['pid'] = (int) $row['pid'];
                    $fieldsArray['crdate'] = time();
                    $fieldsArray['cruser_id'] = (int) $row['cruser_id'];
                    $fieldsArray['sys_language_uid'] = (int) $row['sys_language_uid'];
                    $this->databaseConnection->exec_INSERTquery('tx_events2_domain_model_day', $fieldsArray);
                    $dayUid = (int) $this->databaseConnection->sql_insert_id();
                } else {
                    // we can use the found record as new relation
                    $dayUid = $day['uid'];
                }
                // update MM-Relation
                $this->databaseConnection->exec_UPDATEquery(
                    'tx_events2_event_day_mm',
                    'uid_foreign='.(int) $row['uid'],
                    array(
                        'uid_foreign' => (int) $dayUid,
                    )
                );
                // delete wrong day record
                $this->databaseConnection->exec_DELETEquery(
                    'tx_events2_domain_model_day',
                    'uid='.(int) $row
                );
                ++$counter['wrongTime'];
            }
        }

        $this->outputLine('I have updated '.$counter['wrongTime'].' mm records and deleted its day records which have a wrong time.');
        $this->outputLine('');

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
                    'uid_local='.(int) $row['uid']
                );
                $this->databaseConnection->exec_UPDATEquery(
                    'tx_events2_domain_model_event',
                    'uid='.(int) $row['uid'],
                    array(
                        'days' => (int) $amount,
                    )
                );
                ++$counter['eventCols'];
            }
        }

        $this->outputLine('I have updated '.$counter['eventCols'].' event records and set the correct amount of related day records.');
        $this->outputLine('');

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
                ++$counter['refIndex'];
            }
        }
        $this->outputLine('I have updated '.$counter['refIndex'].' ref indexes for events.');
        $this->outputLine('');
    }
}
