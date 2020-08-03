<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
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

    public function injectExtConf(ExtConf $extConf)
    {
        $this->extConf = $extConf;
    }

    public function injectDateTimeUtility(DateTimeUtility $dateTimeUtility)
    {
        $this->dateTimeUtility = $dateTimeUtility;
    }

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
     * @param string $commaSeparatedExtensionTypes Type like Add, Remove, Time or Info. If empty add all exceptions
     * @return \SplObjectStorage|Exception[]
     */
    public function getExceptionsForDate(
        Event $event,
        \DateTime $date,
        string $commaSeparatedExtensionTypes = ''
    ): \SplObjectStorage {
        $exceptionTypes = GeneralUtility::trimExplode(',', strtolower($commaSeparatedExtensionTypes), true);
        $exceptions = new \SplObjectStorage();
        foreach ($event->getExceptions() as $exception) {
            $exceptionDate = $this->dateTimeUtility->standardizeDateTimeObject($exception->getExceptionDate());
            $currentDate = $this->dateTimeUtility->standardizeDateTimeObject($date);
            // we compare objects here so no === possible
            if ($exceptionDate == $currentDate) {
                if (empty($exceptionTypes) || in_array(strtolower($exception->getExceptionType()), $exceptionTypes)) {
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
    public function getTimesForDate(Event $event, \DateTime $date): \SplObjectStorage
    {
        /** @var \SplObjectStorage|Time[] $timesForDate */
        $timesForDate = new \SplObjectStorage();

        // times from event have priority 3
        $eventTimes = $this->getTimesFromEvent($event);
        if ($eventTimes->count()) {
            $timesForDate = $eventTimes;
        }

        // times from event->differentTimes have priority 2
        $differentTimes = $this->getDifferentTimesForDate($event, $date);
        if ($differentTimes->count()) {
            $timesForDate = $differentTimes;
        }

        // times from exceptions have priority 1
        // The exceptions of type "Add" were already moved to event->getDays (DayGenerator), but not their time records
        // that's why we collect exceptions of type "Add" and "Time" here
        $timesFromExceptions = $this->getExceptionsForDate($event, $date, 'add, time');
        if ($timesFromExceptions->count()) {
            /** @var \SplObjectStorage|Time[] $times */
            $times = new \SplObjectStorage();
            /** @var Exception $exception */
            foreach ($timesFromExceptions as $exception) {
                $time = $exception->getExceptionTime();
                if ($time instanceof Time) {
                    $times->attach($exception->getExceptionTime());
                }
            }
            if ($times->count()) {
                $timesForDate = $times;
            }
        }

        foreach ($timesForDate as $timeForDate) {
            $this->addDateTimeObjectsToTime($date, $timeForDate);
        }

        return $timesForDate;
    }

    /**
     * Get sorted times for specified date
     *
     * @param Event $event
     * @param \DateTime $date
     * @return \SplObjectStorage|Time[]
     */
    public function getSortedTimesForDate(Event $event, \DateTime $date): \SplObjectStorage
    {
        $sortedTimes = [];
        $sortedTimeStorage = new \SplObjectStorage();

        $times = $this->getTimesForDate($event, $date);
        foreach ($times as $time) {
            $sortedTimes[$time->getTimeBegin()] = $time;
        }

        ksort($sortedTimes);

        foreach ($sortedTimes as $time) {
            $this->addDateTimeObjectsToTime($date, $time);
            $sortedTimeStorage->attach($time);
        }
        return $sortedTimeStorage;
    }

    /**
     * Get Time object for a given day record, if exists
     *
     * @param Day $day
     * @return Time|null
     */
    public function getTimeForDay(Day $day): ?Time
    {
        $times = $this->getTimesForDate($day->getEvent(), $day->getDay());
        if ($times->count()) {
            foreach ($times as $time) {
                if ($time->getTimeBeginAsDateTime() == $day->getDayTime()) {
                    return $time;
                }
            }
        }
        return null;
    }

    /**
     * This method merges given date (midnight) with hour and minute.
     * Useful for fluid f:format.date()
     *
     * @param \DateTime $date
     * @param Time $time
     */
    protected function addDateTimeObjectsToTime(\DateTime $date, Time $time): Time
    {
        $format = 'd.m.Y H:i:s';
        foreach (['TimeEntry', 'TimeBegin', 'TimeEnd'] as $property) {
            $setter = 'set' . $property . 'AsDateTime';
            $getter = 'get' . $property;
            if ($time->{$getter}() === '') {
                continue;
            }
            $timeString = sprintf(
                '%s.%s.%s %s:00',
                $date->format('d'),
                $date->format('m'),
                $date->format('Y'),
                $time->{$getter}()
            );
            $time->{$setter}(
                \DateTime::createFromFormat(
                    $format,
                    $timeString
                )
            );
        }
    }

    /**
     * You can override the times in an event for a special weekday,
     * so this method checks and returns times, if there are times defined for given date.
     *
     * @param Event $event
     * @param \DateTime $date
     * @return \SplObjectStorage|Time[]
     */
    protected function getDifferentTimesForDate(Event $event, \DateTime $date): \SplObjectStorage
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
     * @return \SplObjectStorage|Time[]
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
            /* @var Time $time */
            foreach ($multipleTimes as $multipleTime) {
                $times->attach($multipleTime);
            }
        }

        return $times;
    }

    public function getNextDayForEvent(int $eventUid): ?\DateTime
    {
        /** @var Event $event */
        $event = $this->eventRepository->findByIdentifier($eventUid);
        if (!$event instanceof Event) {
            return null;
        }

        $days = $event->getFutureDatesGroupedAndSorted();
        if (!empty($days)) {
            return current($days);
        }

        return null;
    }

    /**
     * Get last day for event
     * Useful to check, if an event is over.
     * Needed by SolrIndexer, as we can't create JOIN Queries in Solr configuration
     *
     * @param int $eventUid
     * @return Day|null
     */
    public function getLastDayForEvent(int $eventUid): ?Day
    {
        /** @var Event $event */
        $event = $this->eventRepository->findByIdentifier($eventUid);
        $days = $event->getFutureDatesGroupedAndSorted();
        krsort($days);
        reset($days);

        /** @var Day $day */
        $day = current($days);
        return $day ?: null;
    }
}
