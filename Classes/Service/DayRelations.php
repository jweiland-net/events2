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
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DayRelations
{
    /**
     * @var array
     */
    protected $eventRecord = array();

    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @var \JWeiland\Events2\Service\DayGenerator
     */
    protected $dayGenerator;

    /**
     * @var \JWeiland\Events2\Utility\DateTimeUtility
     */
    protected $dateTimeUtility;
    
    /**
     * @var array
     */
    protected $cachedSortDayTime = array();

    /**
     * inject dayGenerator.
     *
     * @param DayGenerator $dayGenerator
     */
    public function injectDayGenerator(DayGenerator $dayGenerator)
    {
        $this->dayGenerator = $dayGenerator;
    }

    /**
     * inject dateTimeUtility.
     *
     * @param DateTimeUtility $dateTimeUtility
     */
    public function injectDateTimeUtility(DateTimeUtility $dateTimeUtility)
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
     * @return void
     */
    public function addDay(\DateTime $day)
    {
        // to prevent adding multiple days for ONE day we set them all to midnight 00:00:00
        $day = $this->dateTimeUtility->standardizeDateTimeObject($day);
        $times = $this->getTimesForDay($day);
        if (!empty($times)) {
            foreach ($times as $time) {
                $this->addDayRecord($day, $time);
            }
        } else {
            $this->addDayRecord($day);
        }
    }
    
    /**
     * each event can have one or more times for one day
     * This method looks into all time related records and fetches the times with highest priority.
     *
     * @param \DateTime $day
     *
     * @return array
     */
    public function getTimesForDay(\DateTime $day)
    {
        // times from exceptions have priority 1
        $timesFromExceptions = $this->eventRecord['exceptions'];
        if (!empty($timesFromExceptions)) {
            $times = array();
            foreach ($timesFromExceptions as $exception) {
                if (
                    $exception['exception_date'] == $day->format('U') &&
                    (
                        $exception['exception_type'] == 'Add' ||
                        $exception['exception_type'] == 'Time'
                    )
                ) {
                    foreach ($exception['exception_time'] as $time) {
                        $times[] = $time;
                    }
                }
            }
            if (!empty($times)) {
                return $times;
            }
        }
        // times from event->differentTimes have priority 2
        $differentTimes = $this->getDifferentTimesForDay($day);
        if (!empty($differentTimes)) {
            return $differentTimes;
        }
        // times from event have priority 3
        $eventTimes = $this->getTimesFromEvent();
        if (!empty($eventTimes)) {
            return $eventTimes;
        }
        
        // if there are no times available return empty array
        return array();
    }
    
    /**
     * you can override the times in an event for a special weekday
     * so this method checks and returns times, if there are times defined for given day.
     *
     * @param \DateTime $day
     *
     * @return array
     */
    protected function getDifferentTimesForDay(\DateTime $day)
    {
        $times = array();
        if (
            $this->eventRecord['event_type'] !== 'single' &&
            !empty($this->eventRecord['different_times'])
        ) {
            // you only can set different times in case of type "duration" and "recurring". But not: single
            foreach ($this->eventRecord['different_times'] as $time) {
                if (strtolower($time['weekday']) === strtolower($day->format('l'))) {
                    $times[] = $time;
                }
            }
        }
        
        return $times;
    }
    
    /**
     * Each event has ONE time record, but if checkbox "same day" was checked, you can add additional times
     * This method checks both parts, merges them to one SplObjectStorage and returns the result.
     *
     * @return array
     */
    protected function getTimesFromEvent()
    {
        $times = array();
        // add normal event time
        if (!empty($this->eventRecord['event_time'])) {
            foreach ($this->eventRecord['event_time'] as $time) {
                $times[] = $time;
            }
        }
        
        // add value of multiple times
        // but only if checkbox "same day" is set
        // and event type is NOT single
        if (
            $this->eventRecord['event_type'] !== 'single' &&
            $this->eventRecord['same_day'] &&
            !empty($this->eventRecord['multiple_times'])
        ) {
            foreach ($this->eventRecord['multiple_times'] as $multipleTime) {
                $times[] = $multipleTime;
            }
        }
        
        return $times;
    }
    
    /**
     * Add day record.
     *
     * @param \DateTime $day
     * @param array $time
     *
     * @return int The affected row uid
     */
    protected function addDayRecord(\DateTime $day, array $time = array())
    {
        $hour = $minute = 0;
        if (
            array_key_exists('time_begin', $time) &&
            preg_match('@^([0-1][0-9]|2[0-3]):[0-5][0-9]$@', $time['time_begin'])
        ) {
            list($hour, $minute) = explode(':', $time['time_begin']);
        }
        
        $fieldsArray = array();
        $fieldsArray['day'] = $day->format('U');
        $fieldsArray['day_time'] = $this->getDayTime($day, $hour, $minute)->format('U');
        $fieldsArray['sort_day_time'] = $this->getSortDayTime($day, $hour, $minute);
        $fieldsArray['event'] = (int)$this->eventRecord['uid'];
        $fieldsArray['deleted'] = (int)$this->eventRecord['deleted'];
        $fieldsArray['hidden'] = (int)$this->eventRecord['hidden'];
        $fieldsArray['tstamp'] = time();
        $fieldsArray['pid'] = (int)$this->eventRecord['pid'];
        $fieldsArray['crdate'] = time();
        $fieldsArray['cruser_id'] = (int)$GLOBALS['BE_USER']->user['uid'];

        $this->databaseConnection->exec_INSERTquery('tx_events2_domain_model_day', $fieldsArray);

        $insertId = (int)$this->databaseConnection->sql_insert_id();
        
        // if $dayUid == 0 an error in query appears. So, do not update anything
        if ($insertId > 0) {
            // add relation in mm-table
            $this->addRelation($this->eventRecord['uid'], $insertId, $day);
        }
        
        return $insertId;
    }
    
    /**
     * Get day time
     * Each day individual hour and minute will be added to day
     *
     * Day: 17.01.2017 00:00:00 + 8h + 30m
     * Day: 18.01.2017 00:00:00 + 10h + 15m
     * Day: 19.01.2017 00:00:00 + 9h + 25m
     * Day: 20.01.2017 00:00:00 + 14h + 45m
     *
     * @param \DateTime $day
     * @param int $hour
     * @param int $minute
     *
     * @return \DateTime
     */
    protected function getDayTime(\DateTime $day, $hour, $minute)
    {
        // Don't modify original day
        $dayTime = clone $day;
        $dayTime->modify(sprintf(
            '+%d hour +%d minute',
            (int)$hour,
            (int)$minute
        ));
        return $dayTime;
    }
    
    /**
     * Get timestamp which is the same for all event days of type duration
     * Instead of getDayTime this method will return the same timestamp for all days in event
     *
     * Day: 17.01.2017 00:00:00 + 8h + 30m  = 17.01.2017 08:30:00
     * Day: 18.01.2017 00:00:00 + 10h + 15m = 17.01.2017 08:30:00
     * Day: 19.01.2017 00:00:00 + 9h + 25m  = 17.01.2017 08:30:00
     * Day: 20.01.2017 00:00:00 + 14h + 45m = 17.01.2017 08:30:00
     *
     * @param \DateTime $day
     * @param int $hour
     * @param int $minute
     *
     * @return int
     */
    protected function getSortDayTime(\DateTime $day, $hour, $minute)
    {
        if (array_key_exists($this->eventRecord['uid'], $this->cachedSortDayTime)) {
            return (int)$this->cachedSortDayTime[$this->eventRecord['uid']];
        }
        
        if (in_array($this->eventRecord['event_type'], array('duration', 'single'))) {
            $sortDayTime = $this->eventRecord['event_begin'];
            $sortDayTime += ($hour * 60 * 60) + $minute * 60;
            
            $this->cachedSortDayTime = array(
                $this->eventRecord['uid'] => $sortDayTime
            );
        } else {
            // @ToDo: Maybe this is a good place to implement merging again
            // If a day has multiple times for ONE day, we want to merge these days to ONE day in list.
            // $sortDayTime = $this->getDayTime($day, $hour, $minute)->format('U');
            // $this->cachedSortDayTime = array();
            $sortDayTime = $this->getDayTime($day, $hour, $minute)->format('U');
            $this->cachedSortDayTime = array(
                $this->eventRecord['uid'] => $sortDayTime
            );
        }
        return (int)$sortDayTime;
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
     * @param int $event
     *
     * @return void
     */
    protected function deleteAllRelatedRecords($event)
    {
        // delete MM entries
        $this->databaseConnection->exec_DELETEquery(
            'tx_events2_event_day_mm',
            'uid_local=' . (int)$event
        );
        // delete day records
        $this->databaseConnection->exec_DELETEquery(
            'tx_events2_domain_model_day',
            'event=' . (int)$event
        );
    }
}
