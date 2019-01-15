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
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * f.e. the time domain model can be connected 4 times with an event (exceptionTimes, differentTimes, MultipleTimes and eventTime)
 * So which time has priority, which time has to be merged
 * This Utility tries to help you/me to make life easier with events2.
 */
class EventService
{
    /**
     * @var ExtConf
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
     * Injects extConf
     *
     * @param ExtConf $extConf
     */
    public function injectExtConf(ExtConf $extConf)
    {
        $this->extConf = $extConf;
    }

    /**
     * Inject DateTime Utility.
     *
     * @param DateTimeUtility $dateTimeUtility
     */
    public function injectDateTimeUtility(DateTimeUtility $dateTimeUtility)
    {
        $this->dateTimeUtility = $dateTimeUtility;
    }

    /**
     * Inject eventRepository
     *
     * @param EventRepository $eventRepository
     */
    public function injectEventRepository(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    /**
     * Get exceptions for given day
     * You can limit the result by a given type.
     *
     * @param Event $event
     * @param \DateTime $date
     * @param string $type There are different exception types like Add, Remove, Time or Info. If empty add all exceptions
     * @return \SplObjectStorage|Exception[]
     */
    public function getExceptionsForDate(Event $event, \DateTime $date, $type = '')
    {
        $type = GeneralUtility::trimExplode(',', strtolower($type), true);
        $exceptions = new \SplObjectStorage();
        foreach ($event->getExceptions() as $exception) {
            $exceptionDate = $this->dateTimeUtility->standardizeDateTimeObject($exception->getExceptionDate());
            $currentDate = $this->dateTimeUtility->standardizeDateTimeObject($date);
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
     * Each event can have one or more times for one day
     * This method looks into all time related records and fetches the times with highest priority.
     *
     * @param Event $event
     * @param \DateTime $date
     * @return \SplObjectStorage|Time[]
     */
    public function getTimesForDate(Event $event, \DateTime $date)
    {
        // times from exceptions have priority 1
        // The exceptions of type "Add" were already moved to event->getDays (DayGenerator), but not their time records
        // that's why we collect exceptions of type "Add" and "Time" here
        $timesFromExceptions = $this->getExceptionsForDate($event, $date, 'add, time');
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
        $differentTimes = $this->getDifferentTimesForDate($event, $date);
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
     * Get sorted times for specified date
     *
     * @param Event $event
     * @param \DateTime $date
     * @return \SplObjectStorage
     */
    public function getSortedTimesForDate(Event $event, \DateTime $date)
    {
        $sortedTimes = [];
        $sortedStorage = new \SplObjectStorage();

        $times = $this->getTimesForDate($event, $date);
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
     * You can override the times in an event for a special weekday,
     * so this method checks and returns times, if there are times defined for given date.
     *
     * @param Event $event
     * @param \DateTime $date
     * @return \SplObjectStorage
     */
    protected function getDifferentTimesForDate(Event $event, \DateTime $date)
    {
        $times = new \SplObjectStorage();
        if ($event->getEventType() !== 'single') {
            foreach ($event->getDifferentTimes() as $time) {
                if (strtolower($time->getWeekday()) === strtolower($date->format('l'))) {
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
     * @return \SplObjectStorage
     */
    protected function getTimesFromEvent(Event $event): \SplObjectStorage
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
     * @return Day|false
     */
    public function getNextDayForEvent(int $eventUid)
    {
        /** @var Event $event */
        $event = $this->eventRepository->findByIdentifier($eventUid);
        if (!$event instanceof Event) {
            return false;
        }

        $days = [];

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
     * @return Day|false
     */
    public function getLastDayForEvent(int $eventUid)
    {
        /** @var Event $event */
        $event = $this->eventRepository->findByIdentifier($eventUid);
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
}
