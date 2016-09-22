<?php

namespace JWeiland\Events2\Service;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DayRelations
{
    protected $eventRecord = array();

    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection = null;

    /**
     * @var \JWeiland\Events2\Service\DayGenerator
     */
    protected $dayGenerator;

    /**
     * @var \JWeiland\Events2\Utility\DateTimeUtility
     */
    protected $dateTimeUtility = null;

    /**
     * inject dayGenerator.
     *
     * @param \JWeiland\Events2\Service\DayGenerator $dayGenerator
     */
    public function injectDayGenerator(\JWeiland\Events2\Service\DayGenerator $dayGenerator)
    {
        $this->dayGenerator = $dayGenerator;
    }

    /**
     * inject dateTimeUtility.
     *
     * @param \JWeiland\Events2\Utility\DateTimeUtility $dateTimeUtility
     */
    public function injectDateTimeUtility(\JWeiland\Events2\Utility\DateTimeUtility $dateTimeUtility)
    {
        $this->dateTimeUtility = $dateTimeUtility;
    }

    /**
     * initialize object
     * set database connection.
     */
    public function initializeObject()
    {
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];
    }

    /**
     * get event record.
     *
     * @return array
     */
    public function getEventRecord()
    {
        return $this->eventRecord;
    }

    /**
     * set event record.
     *
     * @param array $eventRecord
     */
    public function setEventRecord(array $eventRecord)
    {
        $this->eventRecord = $eventRecord;
    }

    /**
     * @param array $event
     */
    public function createDayRelations(array $event)
    {
        if (empty($event) || empty($event['uid'])) {
            // write a warning (2) to sys_log
            GeneralUtility::sysLog('Related days could not be created, because of an empty event or a non given event uid', 'events2', 2);
        } else {
            foreach ($event as $key => $value) {
                $this->eventRecord[GeneralUtility::camelCaseToLowerCaseUnderscored($key)] = $value;
            }
            $this->dayGenerator->initialize($this->eventRecord);
            $days = $this->dayGenerator->getDayStorage();

            // delete entries with current event uid from mm-table
            $this->deleteAllRelatedRecords((int)$this->eventRecord['uid']);

            foreach ($days as $day) {
                $this->addDay($day);
            }

            // add days amount to event
            $this->databaseConnection->exec_UPDATEquery(
                'tx_events2_domain_model_event',
                'uid=' . (int)$this->eventRecord['uid'],
                array(
                    'days' => count($days),
                )
            );
        }
    }

    /**
     * add day to db
     * Also MM-Tables will be filled.
     *
     * @param \DateTime $day
     *
     * @return int UID of inserted day
     */
    public function addDay(\DateTime $day)
    {
        // to prevent adding multiple days for ONE day we set them all to midnight 00:00:00
        $day = $this->dateTimeUtility->standardizeDateTimeObject($day);
        $dayUid = (int)$this->addDayRecord($day);

        // if $dayUid == 0 an error in query appears. So, do not update anything
        if ($dayUid > 0) {
            // add relation in mm-table
            $this->addRelation($this->eventRecord['uid'], $dayUid, $day);

            // add amount of events to day record
            $amount = $this->databaseConnection->exec_SELECTcountRows(
                '*',
                'tx_events2_event_day_mm',
                'uid_foreign=' . $dayUid
            );
            if ($amount) {
                $this->databaseConnection->exec_UPDATEquery(
                    'tx_events2_domain_model_day',
                    'uid=' . $dayUid,
                    array(
                        'events' => (int)$amount,
                    )
                );
            }
        }

        return $dayUid;
    }

    /**
     * add day record if not already exists.
     *
     * @param \DateTime $day
     *
     * @return int The affected row uid
     */
    protected function addDayRecord(\DateTime $day)
    {
        $row = $this->databaseConnection->exec_SELECTgetSingleRow(
            'uid',
            'tx_events2_domain_model_day',
            sprintf(
                'day=%d %s %s',
                (int)$day->format('U'),
                BackendUtility::BEenableFields('tx_events2_domain_model_day'),
                BackendUtility::deleteClause('tx_events2_domain_model_day')
            )
        );

        if ($row === null) {
            // TODO: Errorhandling
            return 0;
        } elseif ($row === false) {
            $time = time();
            $fieldsArray = array();
            $fieldsArray['day'] = (int)$day->format('U');
            $fieldsArray['tstamp'] = $time;
            $fieldsArray['pid'] = (int)$this->eventRecord['pid'];
            $fieldsArray['crdate'] = $time;
            $fieldsArray['cruser_id'] = (int)$GLOBALS['BE_USER']->user['uid'];

            $this->databaseConnection->exec_INSERTquery('tx_events2_domain_model_day', $fieldsArray);

            return (int)$this->databaseConnection->sql_insert_id();
        } else {
            return (int)$row['uid'];
        }
    }

    /**
     * add relation to day record in mm table.
     *
     * @param int       $eventUid
     * @param int       $dayUid
     * @param \DateTime $day
     */
    protected function addRelation($eventUid, $dayUid, \DateTime $day)
    {
        // we don't need a SELECT query here, because we have deleted all related records just before
        $fieldsArray = array();
        $fieldsArray['uid_local'] = (int)$eventUid;
        $fieldsArray['uid_foreign'] = (int)$dayUid;
        $fieldsArray['sorting'] = (int)$day->format('U');

        $this->databaseConnection->exec_INSERTquery(
            'tx_events2_event_day_mm',
            $fieldsArray
        );
    }

    /**
     * delete all related records from mm-table.
     *
     * @param int $eventUid
     */
    protected function deleteAllRelatedRecords($eventUid)
    {
        $this->databaseConnection->exec_DELETEquery(
            'tx_events2_event_day_mm',
            'uid_local=' . (int)$eventUid
        );
    }
}
