<?php

namespace JWeiland\Events2\Service;

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
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * f.e. the time domain model can be connected 4 times with an event (exceptionTimes, differentTimes, MultipleTimes and eventTime)
 * So which time has priority, which time has to be merged
 * This Utility tries to help you/me to make live easier with events2.
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class EventService
{
    /**
     * @var \JWeiland\Events2\Configuration\ExtConf
     */
    protected $extConf;

    /**
     * @var DateTimeUtility
     */
    protected $dateTimeUtility;

    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * injects extConf
     *
     * @param ExtConf $extConf
     *
     * @return void
     */
    public function injectExtConf(ExtConf $extConf)
    {
        $this->extConf = $extConf;
    }

    /**
     * inject DateTime Utility.
     *
     * @param DateTimeUtility $dateTimeUtility
     *
     * @return void
     */
    public function injectDateTimeUtility(DateTimeUtility $dateTimeUtility)
    {
        $this->dateTimeUtility = $dateTimeUtility;
    }

    /**
     * inject eventRepository
     *
     * @param EventRepository $eventRepository
     *
     * @return void
     */
    public function injectEventRepository(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    /**
     * get exceptions for given day
     * you can limit the result by a given type.
     *
     * @param Event  $event
     * @param Day    $day
     * @param string $type  There are different exception types like Add, Remove, Time or Info. If empty add all exceptions
     *
     * @return \SplObjectStorage
     */
    public function getExceptionsForDay(Event $event, Day $day, $type = '')
    {
        $type = GeneralUtility::trimExplode(',', strtolower($type), true);
        $exceptions = new \SplObjectStorage();
        /** @var \JWeiland\Events2\Domain\Model\Exception $exception */
        foreach ($event->getExceptions() as $exception) {
            $exceptionDate = $this->dateTimeUtility->standardizeDateTimeObject($exception->getExceptionDate());
            $currentDate = $this->dateTimeUtility->standardizeDateTimeObject($day->getDay());
            // we compare objects here so no === possible
            if ($exceptionDate == $currentDate) {
                if ($type === [] || in_array(strtolower($exception->getExceptionType()), $type)) {
                    $exceptions->attach($exception);
                }
            }
        }

        return $exceptions;
    }

    /**
     * each event can have one or more times for one day
     * This method looks into all time related records and fetches the times with highest priority.
     *
     * @param Event $event
     * @param Day   $day
     *
     * @return \SplObjectStorage
     */
    public function getTimesForDay(Event $event, Day $day)
    {
        // times from exceptions have priority 1
        // The exceptions of type "Add" were already moved to event->getDays (DayGenerator), but not their time records
        // that's why we collect exceptions of type "Add" and "Time" here
        $timesFromExceptions = $this->getExceptionsForDay($event, $day, 'add, time');
        if ($timesFromExceptions->count()) {
            $times = new \SplObjectStorage();
            /** @var \JWeiland\Events2\Domain\Model\Exception $exception */
            foreach ($timesFromExceptions as $exception) {
                $time = $exception->getExceptionTime();
                if ($time instanceof Time) {
                    $times->attach($exception->getExceptionTime());
                }
            }

            return $times;
        }
        // times from event->differentTimes have priority 2
        $differentTimes = $this->getDifferentTimesForDay($event, $day);
        if ($differentTimes->count()) {
            return $differentTimes;
        }
        // times from event have priority 3
        $eventTimes = $this->getTimesFromEvent($event);
        if ($eventTimes->count()) {
            return $eventTimes;
        }

        // if there are no times available return empty SplObjectStorage
        return new \SplObjectStorage();
    }

    /**
     * get sorted times for specified day
     *
     * @param Event $event
     * @param Day   $day
     *
     * @return \SplObjectStorage
     */
    public function getSortedTimesForDay(Event $event, Day $day)
    {
        // @ToDo: I'm sure there are better ways to do this:
        $sortedTimes = [];
        $sortedStorage = new \SplObjectStorage();

        $times = $this->getTimesForDay($event, $day);
        /** @var Time $time */
        foreach ($times as $time) {
            $sortedTimes[$time->getTimeBegin()] = $time;
        }

        ksort($sortedTimes);

        foreach ($sortedTimes as $time) {
            $sortedStorage->attach($time);
        }
        return $sortedStorage;
    }

    /**
     * you can override the times in an event for a special weekday
     * so this method checks and returns times, if there are times defined for given day.
     *
     * @param Event $event
     * @param Day   $day
     *
     * @return \SplObjectStorage
     */
    protected function getDifferentTimesForDay(Event $event, Day $day)
    {
        $times = new \SplObjectStorage();
        if ($event->getEventType() !== 'single') {
            /** @var \JWeiland\Events2\Domain\Model\Time $time */
            foreach ($event->getDifferentTimes() as $time) {
                if (strtolower($time->getWeekday()) === strtolower($day->getDay()->format('l'))) {
                    $times->attach($time);
                }
            }
        }

        return $times;
    }

    /**
     * Each event has ONE time record, but if checkbox "same day" was checked, you can add additional times
     * This method checks both parts, merges them to one SplObjectStorage and returns the result.
     *
     * @param Event $event
     *
     * @return \SplObjectStorage
     */
    protected function getTimesFromEvent(Event $event)
    {
        $times = new \SplObjectStorage();
        // add normal event time
        $eventTime = $event->getEventTime();
        if ($eventTime instanceof Time) {
            $times->attach($eventTime);
        }

        // add value of multiple times
        // but only if checkbox "same day" is set
        if (
            $event->getSameDay() &&
            $event->getEventType() !== 'single'
        ) {
            $multipleTimes = $event->getMultipleTimes();
            /* @var \JWeiland\Events2\Domain\Model\Time $time */
            foreach ($multipleTimes as $multipleTime) {
                $times->attach($multipleTime);
            }
        }

        return $times;
    }

    /**
     * Get next day for event
     *
     * @param int $eventUid
     *
     * @return Day|false
     */
    public function getNextDayForEvent($eventUid)
    {
        /** @var Event $event */
        $event = $this->eventRepository->findByIdentifier((int)$eventUid);
        if (!$event instanceof Event) {
            return false;
        }

        $days = [];

        /** @var Day $day */
        foreach ($event->getDays() as $day) {
            $dayTime = $day->getSortDayTime()->format('U');
            if ($dayTime > time()) {
                $days[$day->getSortDayTime()->format('U')] = $day;
            }
        }
        ksort($days);
        reset($days);

        return current($days);
    }

    /**
     * Get last day for event
     * Useful to check, if an event is over.
     * Needed by SolrIndexer, as we can't create JOIN Queries in Solr configuration
     *
     * @param int $eventUid
     *
     * @return Day|false
     */
    public function getLastDayForEvent($eventUid)
    {
        /** @var Event $event */
        $event = $this->eventRepository->findByIdentifier((int)$eventUid);
        if ($event->getDays()->count()) {
            $days = [];

            /** @var Day $day */
            foreach ($event->getDays() as $day) {
                $dayTime = $day->getSortDayTime()->format('U');
                if ($dayTime > time()) {
                    $days[$day->getSortDayTime()->format('U')] = $day;
                }
            }
            krsort($days);
            reset($days);

            return current($days);
        } else {
            return false;
        }
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
