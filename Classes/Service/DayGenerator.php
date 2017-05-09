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
    protected $dayStorage = array();

    /**
     * event record.
     *
     * @var array
     */
    protected $eventRecord = array();

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
     * Getter for event record.
     *
     * @return array
     */
    public function getEventRecord()
    {
        return $this->eventRecord;
    }

    /**
     * Setter for event record.
     *
     * @param $eventRecord
     *
     * @return void
     */
    public function setEventRecord($eventRecord)
    {
        $this->eventRecord = (array) $eventRecord;
    }

    /**
     * initializes this object.
     *
     * @param array $eventRecord
     *
     * @return bool
     */
    public function initialize(array $eventRecord)
    {
        // reset, for previous calls
        $this->setDayStorage(array());
        $this->eventRecord = $eventRecord;

        // check for valid event record.
        // if false dayStorage is empty
        if (!$this->isValidEvent()) {
            return false;
        }

        // check for recurring event
        // do not add event start to recurring events. They will be generated automatically
        // further it could be that event start is not within the generated days
        if ($this->eventRecord['event_type'] === 'recurring') {
            $this->addRecurringEvents();
        } elseif (
            $this->eventRecord['event_type'] === 'duration' &&
            $this->eventRecord['event_end']
        ) {
            // if we have no recurring defined but event_end, this is also a recurring event and we have to add all days in between
            $eventBegin = $this->getEarliestDateForGeneratedDays($this->getEventBegin());
            $eventEnd = $this->getMaxDateForGeneratedDays($this->getEventBegin());
            while ($eventBegin <= $eventEnd) {
                $this->addDayToStorage($eventBegin);
                $eventBegin->modify('+1 day');
            }
        } else {
            // add start day
            $this->addDayToStorage($this->getEventBegin());
        }

        // exclude or include further days if exceptions are activated
        if (is_array($this->eventRecord['exceptions']) && count($this->eventRecord['exceptions'])) {
            $this->addExceptions();
        }

        return true;
    }

    /**
     * getter for day storage.
     *
     * @return array
     */
    public function getDayStorage()
    {
        ksort($this->dayStorage);

        return $this->dayStorage;
    }

    /**
     * setter for day storage
     * Needed for UnitTests.
     *
     * @param array $dayStorage
     *
     * @return void
     */
    public function setDayStorage(array $dayStorage)
    {
        $this->dayStorage = $dayStorage;
    }

    /**
     * getter for start date of event.
     *
     * @return \DateTime|null
     */
    public function getEventBegin()
    {
        return $this->dateTimeUtility->convert($this->eventRecord['event_begin']);
    }
    
    /**
     * getter for end of recurring.
     *
     * @return \DateTime|null
     */
    public function getRecurringEnd()
    {
        if (!empty($this->eventRecord['recurring_end'])) {
            return $this->dateTimeUtility->convert($this->eventRecord['recurring_end']);
        } else {
            return null;
        }
    }
    
    /**
     * getter for end of event.
     * Needed for event wof type duration
     *
     * @return \DateTime|null
     */
    public function getEventEnd()
    {
        if (!empty($this->eventRecord['event_end'])) {
            return $this->dateTimeUtility->convert($this->eventRecord['event_end']);
        } else {
            return null;
        }
    }
    
    /**
     * check if event record is a valid event.
     *
     * @return bool
     */
    protected function isValidEvent()
    {
        $valid = true;

        // we need a filled event record
        if (empty($this->eventRecord)) {
            $valid = false;
        }

        // some special fields must be set
        if (
            !isset($this->eventRecord['event_type']) ||
            !isset($this->eventRecord['event_begin']) ||
            !isset($this->eventRecord['xth']) ||
            !isset($this->eventRecord['weekday']) ||
            !isset($this->eventRecord['each_weeks']) ||
            !isset($this->eventRecord['exceptions'])
        ) {
            $valid = false;
        }

        // event_begin can not be empty
        if (empty($this->eventRecord['event_begin'])) {
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
        $this->dayStorage[$day->format('U')] = clone $day;
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
        unset($this->dayStorage[$day->format('U')]);
    }

    /**
     * get max date for generated day records in future.
     *
     * @param \DateTime $eventBegin Day to start with generation
     *
     * @return \DateTime
     */
    protected function getMaxDateForGeneratedDays(\DateTime $eventBegin)
    {
        $today = clone $this->dateTimeUtility->convert('today');
        $maxEventEnd = clone $eventBegin;

        // check, what is more current
        if ($today > $maxEventEnd) {
            $maxEventEnd = $today;
        }
        $maxEventEnd->modify(sprintf(
            '+%d months',
            $this->extConf->getRecurringFuture()
        ));
        
        if ($this->eventRecord['event_type'] === 'duration') {
            $recurringEnd = $this->getEventEnd();
        } else {
            $recurringEnd = $this->getRecurringEnd();
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
     * @return array $xth
     */
    protected function getXth()
    {
        $result = array();
        foreach ($this->getItemsFromTca('xth') as $key => $item) {
            $value = (bool)($this->eventRecord['xth'] & pow(2, $key));
            $result[$item[1]] = $value;
        }
        
        return $result;
    }

    /**
     * Getter for weekday.
     *
     * @return array $weekday
     */
    protected function getWeekday()
    {
        $result = array();
        foreach ($this->getItemsFromTca('weekday') as $key => $item) {
            $value = (bool)($this->eventRecord['weekday'] & pow(2, $key));
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
     * @return void
     */
    protected function addRecurringEvents()
    {
        if ($this->eventRecord['each_weeks']) {
            // add days for each week(s)
            $this->addRecurringWeeks();
        } else {
            // add days for xth recurring event
            $startDate = $this->getEarliestDateForGeneratedDays($this->getEventBegin());
            // We need the first day, because January the 30th +1 month results in 02.03.
            // At that point it is no problem to set the date to the first, because we only need month and year.
            // You will find the check for the correct date in addDaysForMonth().
            $startDate->modify('first day of this month');

            $maxDate = $this->getMaxDateForGeneratedDays($this->getEventBegin());
            while ($startDate <= $maxDate) {
                $this->addDaysForMonth($startDate->format('F'), $startDate->format('Y'));
                $startDate->modify('next month');
            }
        }
    }

    /**
     * add days for recurring weeks.
     *
     * @return void
     */
    protected function addRecurringWeeks()
    {
        // start with today midnight
        $startCalculatingDaysAtDate = clone $this->dateTimeUtility->convert('today');
        // subtract configured amount of month
        $startCalculatingDaysAtDate->modify('-' . $this->extConf->getRecurringPast() . ' months');

        // Can be somewhere in past
        $earliestDay = clone $this->getEventBegin();
        while ($earliestDay <= $startCalculatingDaysAtDate) {
            // add interval as long $earliest day is less than $startCalculatingDaysAtDate
            $earliestDay->modify('+' . $this->eventRecord['each_weeks'] . ' weeks');
        }
        
        // now we can be sure that $earliestDay is in sync with $this->getEventBegin()
        $this->addDayToStorage($earliestDay);

        $maxDate = $this->getMaxDateForGeneratedDays($this->getEventBegin());
        $interval = $earliestDay->diff($maxDate); // generates an interval object
        $diffDays = (int)$interval->format('%a'); // returns the difference in days
        $daysToGenerate = ceil($diffDays / ($this->eventRecord['each_weeks'] * 7)); // diff in days / weeks in days ==> rounded up to next int
        for ($week = 0; $week < $daysToGenerate; ++$week) {
            $earliestDay->modify('+' . $this->eventRecord['each_weeks'] . ' weeks');
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
     *
     * @return void
     */
    protected function addDaysForMonth($month, $year)
    {
        // we need this to have a date where time is set to 00:00:00
        $day = $this->dateTimeUtility->convert('today');
        $lastDayOfMonth = $this->dateTimeUtility->convert('today');
        $lastDayOfMonth->modify('last day of ' . $month . ' ' . $year . '23:59:59');
        $eventBegin = $this->getEarliestDateForGeneratedDays($this->getEventBegin()); // prevent from calling it multiple times in foreach
        $maxDate = $this->getMaxDateForGeneratedDays($this->getEventBegin()); // prevent from calling it multiple times in foreach

        foreach ($this->getXth() as $xthIndex => $xth) {
            foreach ($this->getWeekday() as $weekdayIndex => $weekday) {
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
     * @return void
     *
     * @throws \Exception
     */
    protected function addExceptions()
    {
        foreach ($this->eventRecord['exceptions'] as $exception) {
            switch ($exception['exception_type']) {
                case 'Add':
                    $this->addDayToStorage($this->dateTimeUtility->convert($exception['exception_date']));
                    break;
                case 'Remove':
                    $this->removeDayFromStorage($this->dateTimeUtility->convert($exception['exception_date']));
                    break;
                case 'Time':
                    break;
                case 'Info':
                    break;
                default:
                    throw new \Exception('"'.$exception['exception_type'].'" is no valid exception type', 1370003254);
            }
        }
    }
}
