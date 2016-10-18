<?php

namespace JWeiland\Events2\Utility;

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
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Time;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * f.e. the time domain model can be connected 4 times with an event (exceptionTimes, differentTimes, MultipleTimes and eventTime)
 * So which time has priority, which time has to be merged
 * This Utility tries to help you/me to make live easier with events2.
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class EventUtility
{
    /**
     * @var \JWeiland\Events2\Utility\DateTimeUtility
     */
    protected $dateTimeUtility = null;

    /**
     * inject DateTime Utility.
     *
     * @param DateTimeUtility $dateTimeUtility
     */
    public function injectDateTimeUtility(DateTimeUtility $dateTimeUtility)
    {
        $this->dateTimeUtility = $dateTimeUtility;
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
                if ($type === array() || in_array(strtolower($exception->getExceptionType()), $type)) {
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
        /** @var \JWeiland\Events2\Domain\Model\Time $time */
        foreach ($event->getDifferentTimes() as $time) {
            if (strtolower($time->getWeekday()) === strtolower($day->getDay()->format('l'))) {
                $times->attach($time);
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
        if ($event->getSameDay()) {
            $multipleTimes = $event->getMultipleTimes();
            /* @var \JWeiland\Events2\Domain\Model\Time $time */
            foreach ($multipleTimes as $multipleTime) {
                $times->attach($multipleTime);
            }
        }

        return $times;
    }
}
