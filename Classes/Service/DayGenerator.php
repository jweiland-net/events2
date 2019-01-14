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
    protected $dateTimeStorage = [];

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
     * @return bool
     * @throws \Exception
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
            $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($event);
            $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($event);
            while ($dateToStartCalculatingFrom <= $dateToStopCalculatingTo) {
                $this->addDayToStorage($dateToStartCalculatingFrom);
                $dateToStartCalculatingFrom->modify('+1 day');
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
        $this->dateTimeStorage = [];
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
     * @return void
     */
    protected function removeDayFromStorage(\DateTime $day)
    {
        unset($this->dateTimeStorage[$day->format('U')]);
    }

    /**
     * Returns current date added with configured "recurring future" configuration in ExtensionManager.
     * If calculated date is older than eventEnd, it does not make sense to use that date and we
     * will return eventEnd instead.
     *
     * @param Event $event
     * @return \DateTime
     */
    protected function getDateToStopCalculatingTo(Event $event)
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
     * Returns current date subtracted with configured "recurring past" configuration in ExtensionManager.
     * If calculated date is older than eventBegin, it does not make sense to use that date and we
     * will return eventBegin instead.
     *
     * @param Event $event
     * @return \DateTime
     */
    protected function getDateToStartCalculatingFrom(Event $event)
    {
        $earliestDateToStartCalculatingFrom = clone $this->dateTimeUtility->convert('today');
        $earliestDateToStartCalculatingFrom->modify('-' . $this->extConf->getRecurringPast() . ' months');
        if (
            $event->getEventBegin() instanceof \DateTime
            && $earliestDateToStartCalculatingFrom > $event->getEventBegin()
        ) {
            $dateToStartCalculatingFrom = $earliestDateToStartCalculatingFrom;
        } else {
            $dateToStartCalculatingFrom = $event->getEventBegin();
        }

        // In case of eachWeeks and eachMonth $dateToStartCalculatingFrom has to be
        // exactly in sync with eventBegin
        if ($event->getEachWeeks() || $event->getEachMonths()) {
            $eventBegin = clone $event->getEventBegin();
            while ($eventBegin < $dateToStartCalculatingFrom) {
                $eventBegin->modify('+' . $event->getEachMonths() . ' months');
                $eventBegin->modify('+' . $event->getEachWeeks() . ' weeks');
            }
            $dateToStartCalculatingFrom = $eventBegin;
        }

        return $dateToStartCalculatingFrom;
    }

    /**
     * Getter for xth.
     *
     * @param Event $event
     * @return array $xth
     */
    protected function getXth(Event $event)
    {
        $result = [];
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
     * @return array $weekday
     */
    protected function getWeekday(Event $event)
    {
        $result = [];
        foreach ($this->getItemsFromTca('weekday') as $key => $item) {
            $value = (bool)($event->getWeekday() & pow(2, $key));
            $result[$item[1]] = $value;
        }

        return $result;
    }

    /**
     * Get items from TCA.
     *
     * @param string $field
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
            return [];
        }
    }

    /**
     * Add days for recurring events.
     *
     * @param Event $event
     * @return void
     */
    protected function addRecurringEvents(Event $event)
    {
        if ($event->getEachWeeks() || $event->getEachMonths()) {
            // add days for each week(s) and/or months
            $this->addRecurrings($event);
        } else {
            // add days for xth recurring event
            $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($event);
            // We need the first day, because January the 30th +1 month results in 02.03.
            // At that point it is no problem to set the date to the first, because we only need month and year.
            // You will find the check for the correct date in addDaysForMonth().
            $dateToStartCalculatingFrom->modify('first day of this month');

            $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($event);
            while ($dateToStartCalculatingFrom <= $dateToStopCalculatingTo) {
                $this->addDaysForMonth(
                    $dateToStartCalculatingFrom->format('F'),
                    $dateToStartCalculatingFrom->format('Y'),
                    $event
                );
                $dateToStartCalculatingFrom->modify('next month');
            }
        }
    }

    /**
     * Add days for recurring weeks and/or months.
     *
     * @param Event $event
     * @return void
     */
    protected function addRecurrings(Event $event)
    {
        $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($event);
        $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($event);
        while ($dateToStartCalculatingFrom <= $dateToStopCalculatingTo) {
            $this->addDayToStorage($dateToStartCalculatingFrom);
            $dateToStartCalculatingFrom->modify('+' . $event->getEachMonths() . ' months');
            $dateToStartCalculatingFrom->modify('+' . $event->getEachWeeks() . ' weeks');
        }
    }

    /**
     * Add days for given month.
     *
     * @param string $month
     * @param int $year
     * @param Event $event
     * @return void
     */
    protected function addDaysForMonth($month, $year, Event $event)
    {
        // we need this to have a date where time is set to 00:00:00
        $day = $this->dateTimeUtility->convert('today');
        $lastDayOfMonth = $this->dateTimeUtility->convert('today');
        $lastDayOfMonth->modify('last day of ' . $month . ' ' . $year . '23:59:59');
        $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($event); // prevent from calling it multiple times in foreach
        $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($event); // prevent from calling it multiple times in foreach

        foreach ($event->getXth() as $xthIndex => $xth) {
            foreach ($event->getWeekday() as $weekdayIndex => $weekday) {
                if ($xth && $weekday) {
                    // example: 'second wednesday of March 2013'
                    $modifyString = $xthIndex . ' ' . $weekdayIndex . ' of ' . $month . ' ' . $year;
                    $day->modify($modifyString);
                    if ($day >= $dateToStartCalculatingFrom && $day < $lastDayOfMonth && $day <= $dateToStopCalculatingTo) {
                        $this->addDayToStorage($day);
                    }
                }
            }
        }
    }

    /**
     * Add event exceptions.
     *
     * @param Event $event
     * @return void
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
