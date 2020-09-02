<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Factory;

use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * Use this factory to get an ObjectStorage of Time Models valid for a given event and date
 */
class TimeFactory
{
    /**
     * The Date to retrieve the Time records for.
     *
     * @var \DateTime
     */
    protected $date;

    /**
     * If true and URI parameter "timestamp" matches, this time will not be included.
     *
     * @var bool
     */
    protected $removeCurrentDay = false;

    public function getSortedTimesForDate(
        Event $event,
        \DateTime $date,
        bool $removeCurrentDay = false
    ): \SplObjectStorage {
        $this->date = $date;
        $this->removeCurrentDay = $removeCurrentDay;

        $sortedTimes = [];
        foreach ($this->getTimesForDate($event, $date, $removeCurrentDay) as $time) {
            $sortedTimes[$time->getTimeBegin()] = $time;
        }
        ksort($sortedTimes);

        $sortedTimeStorage = new \SplObjectStorage();
        foreach ($sortedTimes as $time) {
            $sortedTimeStorage->attach($time);
        }
        return $sortedTimeStorage;
    }

    /**
     * Each event can have one or more times for one day
     * This method looks into all time related records and fetches the times with highest priority.
     *
     * @param Event $event
     * @param \DateTime $date
     * @param bool $removeCurrentDay If true and URI parameter "timestamp" matches, this time will not be included.
     * @return \SplObjectStorage|Time[]
     */
    public function getTimesForDate(Event $event, \DateTime $date, bool $removeCurrentDay = false): \SplObjectStorage
    {
        $this->date = $date;
        $this->removeCurrentDay = $removeCurrentDay;

        /** @var \SplObjectStorage|Time[] $timesForDate */
        $timesForDate = new \SplObjectStorage();

        $this->addExceptionTimes($timesForDate, $event);
        $this->addDifferentTimes($timesForDate, $event);
        $this->addEventTimes($timesForDate, $event);

        foreach ($timesForDate as $timeForDate) {
            $this->addDateTimeObjectsToTime($date, $timeForDate);
        }

        return $timesForDate;
    }

    /**
     * Add exception times. This has highest priority
     * The exceptions of type "Add" were already moved to event->getDays (DayGenerator),
     * but not their time records. That's why we collect exceptions of
     * type "Add" and "Time" here
     *
     * @param \SplObjectStorage $timesForDate
     * @param Event $event
     */
    protected function addExceptionTimes(
        \SplObjectStorage $timesForDate,
        Event $event
    ): void {
        $timesFromExceptions = $event->getExceptionsForDate($this->date, 'add, time');
        foreach ($timesFromExceptions as $exception) {
            $time = $exception->getExceptionTime();
            if ($time instanceof Time) {
                $this->addTimeToObjectStorage($timesForDate, $exception->getExceptionTime());
            }
        }
    }

    /**
     * Different Times has 2nd highest priority.
     * You can override event times for a special weekday like 'monday'.
     * If different times are defined they override global event times.
     *
     * @param \SplObjectStorage $timesForDate
     * @param Event $event
     * @param \DateTime $date
     */
    protected function addDifferentTimes(
        \SplObjectStorage $timesForDate,
        Event $event
    ): void {
        if (
            $timesForDate->count() === 0
            && $event->getEventType() !== 'single'
            && $event->getDifferentTimes()->count()
        ) {
            foreach ($event->getDifferentTimes() as $time) {
                if (strtolower($time->getWeekday()) === strtolower($this->date->format('l'))) {
                    $this->addTimeToObjectStorage($timesForDate, $time);
                }
            }
        }
    }

    /**
     * Add global event times. This has most less priority
     *
     * @param \SplObjectStorage $timesForDate
     * @param Event $event
     */
    protected function addEventTimes(
        \SplObjectStorage $timesForDate,
        Event $event
    ): void {
        if ($timesForDate->count() === 0) {
            $eventTime = $event->getEventTime();
            if ($eventTime instanceof Time) {
                $this->addTimeToObjectStorage($timesForDate, $eventTime);
            }
            $this->addMultipleTimes($timesForDate, $event);
        }
    }

    /**
     * If checkbox "same day" was checked, you can add additional times for one specific day.
     * This method adds the additional times for one day to $timesForDate.
     * It must be called from within addEventTimes to prevent adding times for exceptions or different times
     *
     * @param \SplObjectStorage $timesForDate
     * @param Event $event
     */
    protected function addMultipleTimes(
        \SplObjectStorage $timesForDate,
        Event $event
    ): void {
        if (
            $event->getSameDay()
            && $event->getEventType() !== 'single'
        ) {
            foreach ($event->getMultipleTimes() as $multipleTime) {
                $this->addTimeToObjectStorage($timesForDate, $multipleTime);
            }
        }
    }

    protected function addTimeToObjectStorage(\SplObjectStorage $timesForDate, Time $time)
    {
        if ($this->removeCurrentDay) {
            // If activated we have to extract the time information from URI parameter "timestamp".
            // We will remove ALL time records of current day, as ALL time records for current day are
            // already visible in event show action. So no need to remove individual time records.
            $uriParameters = GeneralUtility::_GET('tx_events2_events');
            $dateTimeUtility = GeneralUtility::makeInstance(DateTimeUtility::class);
            $currentDateMidnight = $dateTimeUtility->convert($uriParameters['timestamp']);
            if ($currentDateMidnight instanceof \DateTime) {
                $currentDateMidnight = $dateTimeUtility->standardizeDateTimeObject($currentDateMidnight);
            }

            if ($this->date != $currentDateMidnight) {
                $timesForDate->attach($time);
            }
        } else {
            $timesForDate->attach($time);
        }
    }

    /**
     * Time record does only contain time entries in the form 20:15.
     * This method uses the given $date (midnight) and adds the time (20:15) to it.
     * That way we get DateTime objects with correct time which can be used in Fluid.
     *
     * @param \DateTime $date
     * @param Time $time
     */
    protected function addDateTimeObjectsToTime(\DateTime $date, Time $time): void
    {
        foreach (['TimeEntry', 'TimeBegin', 'TimeEnd'] as $property) {
            $setter = 'set' . $property . 'AsDateTime';
            $getter = 'get' . $property;
            if ($time->{$getter}() === '') {
                continue;
            }
            $time->{$setter}(
                $this->getDateTimeByDateAndTime($date, $time->{$getter}())
            );
        }
    }

    /**
     * Merge $date (midnight) with time (20:15) to a new DateTime containing the time
     *
     * @param \DateTime $date
     * @param string $time
     * @return \DateTime|false
     */
    protected function getDateTimeByDateAndTime(\DateTime $date, string $time): \DateTime
    {
        return \DateTime::createFromFormat(
            'd.m.Y H:i:s',
            sprintf(
                '%s.%s.%s %s:00',
                $date->format('d'),
                $date->format('m'),
                $date->format('Y'),
                $time
            )
        );
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
}
