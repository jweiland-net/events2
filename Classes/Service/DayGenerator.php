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
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Utility\DateTimeUtility;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DayGenerator
{
    /**
     * day storage.
     *
     * @var array
     */
    protected $dateTimeStorage = array();

    /**
     * @var \JWeiland\Events2\Configuration\ExtConf
     */
    protected $extConf;

    /**
     * @var \JWeiland\Events2\Utility\DateTimeUtility
     */
    protected $dateTimeUtility;

    /**
     * injects extConf.
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
     * initializes this object.
     *
     * @param Event $event
     *
     * @return bool
     */
    public function initialize(Event $event)
    {
        // reset, because of previous calls
        $this->reset();

        // check for valid event record.
        // if false dateTimeStorage is empty
        if (!$this->isValidEvent($event)) {
            return false;
        }

        // check for recurring event
        // do not add event start to recurring events. They will be generated automatically
        // further it could be that event start is not within the generated days
        if ($event->getEventType() === 'recurring') {
            $this->addRecurringEvents($event);
        } elseif (
            $event->getEventType() === 'duration' &&
            $event->getEventEnd() instanceof \DateTime
        ) {
            // if we have no recurring defined but event_end, this is also a recurring event and we have to add all days in between
            $eventBegin = $this->getEarliestDateForGeneratedDays($event->getEventBegin());
            $eventEnd = $this->getMaxDateForGeneratedDays($event);
            while ($eventBegin <= $eventEnd) {
                $this->addDayToStorage($eventBegin);
                $eventBegin->modify('+1 day');
            }
        } else {
            // add start day
            $this->addDayToStorage($event->getEventBegin());
        }

        // exclude or include further days if exceptions are activated
        if ($event->getExceptions()->count()) {
            $this->addExceptions($event);
        }

        return true;
    }

    /**
     * Reset dateTimeStorage
     *
     * @return void
     */
    protected function reset()
    {
        $this->dateTimeStorage = array();
    }

    /**
     * Getter for DateTime Storage
     *
     * @return \DateTime[]
     */
    public function getDateTimeStorage()
    {
        ksort($this->dateTimeStorage);

        return $this->dateTimeStorage;
    }

    /**
     * setter for day storage
     * Needed for UnitTests.
     *
     * @param array $dateTimeStorage
     * @return void
     */
    public function setDateTimeStorage(array $dateTimeStorage)
    {
        $this->dateTimeStorage = $dateTimeStorage;
    }

    /**
     * check if event record is a valid event.
     *
     * @param Event $event
     *
     * @return bool
     */
    protected function isValidEvent(Event $event)
    {
        $valid = true;

        // some special fields must be set
        if (
            empty($event->getEventType()) ||
            !$event->getEventBegin() instanceof \DateTime
        ) {
            $valid = false;
        }

        return $valid;
    }

    /**
     * add day to day storage.
     *
     * @param \DateTime $day Day to add
     *
     * @return void
     */
    protected function addDayToStorage(\DateTime $day)
    {
        // group days to make them unique
        // I don't know why, but $day is a reference, so I clone it here to have individual dates in this array
        $this->dateTimeStorage[$day->format('U')] = clone $day;
    }

    /**
     * remove day to day storage.
     *
     * @param \DateTime $day Day to remove
     *
     * @return void
     */
    protected function removeDayFromStorage(\DateTime $day)
    {
        unset($this->dateTimeStorage[$day->format('U')]);
    }

    /**
     * get max date for generated day records in future.
     *
     * @param Event $event
     *
     * @return \DateTime
     */
    protected function getMaxDateForGeneratedDays(Event $event)
    {
        $today = clone $this->dateTimeUtility->convert('today');
        $maxEventEnd = $event->getEventBegin();

        // check, what is more current
        if ($today > $maxEventEnd) {
            $maxEventEnd = $today;
        }
        $maxEventEnd->modify(sprintf(
            '+%d months',
            $this->extConf->getRecurringFuture()
        ));

        if ($event->getEventType() === 'duration') {
            $recurringEnd = $event->getEventEnd();
        } else {
            $recurringEnd = $event->getRecurringEnd();
        }

        if ($recurringEnd instanceof \DateTime && $recurringEnd < $maxEventEnd) {
            return $recurringEnd;
        } else {
            return $maxEventEnd;
        }
    }

    /**
     * get earliest date for generated day records in past.
     *
     * @param \DateTime $eventBegin Event begin
     *
     * @return \DateTime
     */
    protected function getEarliestDateForGeneratedDays(\DateTime $eventBegin)
    {
        $earliestEventBegin = clone $this->dateTimeUtility->convert('today');
        $earliestEventBegin->modify('-' . $this->extConf->getRecurringPast() . ' months');

        if ($earliestEventBegin > $eventBegin) {
            return $earliestEventBegin;
        } else {
            return $eventBegin;
        }
    }

    /**
     * Getter for xth.
     *
     * @param Event $event
     *
     * @return array $xth
     */
    protected function getXth(Event $event)
    {
        $result = array();
        foreach ($this->getItemsFromTca('xth') as $key => $item) {
            $value = (bool)($event->getXth() & pow(2, $key));
            $result[$item[1]] = $value;
        }

        return $result;
    }

    /**
     * Getter for weekday.
     *
     * @param Event $event
     *
     * @return array $weekday
     */
    protected function getWeekday(Event $event)
    {
        $result = array();
        foreach ($this->getItemsFromTca('weekday') as $key => $item) {
            $value = (bool)($event->getWeekday() & pow(2, $key));
            $result[$item[1]] = $value;
        }

        return $result;
    }

    /**
     * get items from TCA.
     *
     * @param string $field
     *
     * @return array
     */
    protected function getItemsFromTca($field)
    {
        if (
            isset($GLOBALS['TCA']['tx_events2_domain_model_event']['columns'][$field]['config']['items']) &&
            is_array($GLOBALS['TCA']['tx_events2_domain_model_event']['columns'][$field]['config']['items'])
        ) {
            return $GLOBALS['TCA']['tx_events2_domain_model_event']['columns'][$field]['config']['items'];
        } else {
            return array();
        }
    }

    /**
     * add days for recurring events.
     *
     * @param Event $event
     *
     * @return void
     */
    protected function addRecurringEvents(Event $event)
    {
        if ($event->getEachWeeks()) {
            // add days for each week(s)
            $this->addRecurringWeeks($event);
        } else {
            // add days for xth recurring event
            $startDate = $this->getEarliestDateForGeneratedDays($event->getEventBegin());
            // We need the first day, because January the 30th +1 month results in 02.03.
            // At that point it is no problem to set the date to the first, because we only need month and year.
            // You will find the check for the correct date in addDaysForMonth().
            $startDate->modify('first day of this month');

            $maxDate = $this->getMaxDateForGeneratedDays($event);
            while ($startDate <= $maxDate) {
                $this->addDaysForMonth($startDate->format('F'), $startDate->format('Y'), $event);
                $startDate->modify('next month');
            }
        }
    }

    /**
     * add days for recurring weeks.
     *
     * @param Event $event
     *
     * @return void
     */
    protected function addRecurringWeeks(Event $event)
    {
        // start with today midnight
        $startCalculatingDaysAtDate = clone $this->dateTimeUtility->convert('today');
        // subtract configured amount of month
        $startCalculatingDaysAtDate->modify('-' . $this->extConf->getRecurringPast() . ' months');

        // Can be somewhere in past
        $earliestDay = clone $event->getEventBegin();
        while ($earliestDay < $startCalculatingDaysAtDate) {
            // add interval as long $earliest day is less than $startCalculatingDaysAtDate
            $earliestDay->modify('+' . $event->getEachWeeks() . ' weeks');
        }

        // now we can be sure that $earliestDay is in sync with $this->getEventBegin()
        $maxDate = $this->getMaxDateForGeneratedDays($event);
        if ($earliestDay <= $maxDate) {
            // add earliest day, if date is not already over
            $this->addDayToStorage($earliestDay);
        }

        $interval = $earliestDay->diff($maxDate); // generates an interval object
        $diffDays = (int)$interval->format('%a'); // returns the difference in days
        $daysToGenerate = ceil($diffDays / ($event->getEachWeeks() * 7)); // diff in days / weeks in days ==> rounded up to next int
        for ($week = 0; $week < $daysToGenerate; ++$week) {
            $earliestDay->modify('+' . $event->getEachWeeks() . ' weeks');
            if ($earliestDay <= $maxDate) {
                $this->addDayToStorage($earliestDay);
            }
        }
    }

    /**
     * add days for given month.
     *
     * @param string $month
     * @param int $year
     * @param Event $event
     *
     * @return void
     */
    protected function addDaysForMonth($month, $year, Event $event)
    {
        // we need this to have a date where time is set to 00:00:00
        $day = $this->dateTimeUtility->convert('today');
        $lastDayOfMonth = $this->dateTimeUtility->convert('today');
        $lastDayOfMonth->modify('last day of ' . $month . ' ' . $year . '23:59:59');
        $eventBegin = $this->getEarliestDateForGeneratedDays($event->getEventBegin()); // prevent from calling it multiple times in foreach
        $maxDate = $this->getMaxDateForGeneratedDays($event); // prevent from calling it multiple times in foreach

        foreach ($event->getXth() as $xthIndex => $xth) {
            foreach ($event->getWeekday() as $weekdayIndex => $weekday) {
                if ($xth && $weekday) {
                    // example: 'second wednesday of March 2013'
                    $modifyString = $xthIndex . ' ' . $weekdayIndex . ' of ' . $month . ' ' . $year;
                    $day->modify($modifyString);
                    if ($day >= $eventBegin && $day < $lastDayOfMonth && $day <= $maxDate) {
                        $this->addDayToStorage($day);
                    }
                }
            }
        }
    }

    /**
     * add event exceptions.
     *
     * @param Event $event
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function addExceptions(Event $event)
    {
        /** @var Exception $exception */
        foreach ($event->getExceptions() as $exception) {
            switch ($exception->getExceptionType()) {
                case 'Add':
                    $this->addDayToStorage($exception->getExceptionDate());
                    break;
                case 'Remove':
                    $this->removeDayFromStorage($exception->getExceptionDate());
                    break;
                case 'Time':
                    break;
                case 'Info':
                    break;
                default:
                    throw new \Exception('"' . $exception->getExceptionType() . '" is no valid exception type', 1370003254);
            }
        }
    }
}
