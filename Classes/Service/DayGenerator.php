<?php
declare(strict_types = 1);
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
 * Class to generate all day records for an event within configured range (ExtensionManager)
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
     * @param ExtConf $extConf
     */
    public function injectExtConf(ExtConf $extConf)
    {
        $this->extConf = $extConf;
    }

    /**
     * @param DateTimeUtility $dateTimeUtility
     */
    public function injectDateTimeUtility(DateTimeUtility $dateTimeUtility)
    {
        $this->dateTimeUtility = $dateTimeUtility;
    }

    /**
     * Initializes this object.
     *
     * @param Event $event
     * @return bool
     * @throws \Exception
     */
    public function initialize(Event $event): bool
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
     */
    protected function reset()
    {
        $this->dateTimeStorage = [];
    }

    /**
     * Return sorted Storage of DateTime objects
     *
     * @return \DateTime[]
     */
    public function getDateTimeStorage(): array
    {
        ksort($this->dateTimeStorage);

        return $this->dateTimeStorage;
    }

    /**
     * Setter for day storage
     * Needed for UnitTests.
     *
     * @param array $dateTimeStorage
     */
    public function setDateTimeStorage(array $dateTimeStorage)
    {
        $this->dateTimeStorage = $dateTimeStorage;
    }

    /**
     * Check, if event record is a valid event.
     *
     * @param Event $event
     * @return bool
     */
    protected function isValidEvent(Event $event): bool
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
     * Add day to day storage.
     *
     * @param \DateTime $day Day to add
     */
    protected function addDayToStorage(\DateTime $day)
    {
        // group days to make them unique
        // I don't know why, but $day is a reference, so I clone it here to have individual dates in this array
        $this->dateTimeStorage[$day->format('U')] = clone $day;
    }

    /**
     * Remove day from day storage.
     *
     * @param \DateTime $day Day to remove
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
    protected function getDateToStopCalculatingTo(Event $event): \DateTime
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
            return $this->dateTimeUtility->standardizeDateTimeObject($recurringEnd);
        } else {
            return $this->dateTimeUtility->standardizeDateTimeObject($maxEventEnd);
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
    protected function getDateToStartCalculatingFrom(Event $event): \DateTime
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

        return $this->dateTimeUtility->standardizeDateTimeObject($dateToStartCalculatingFrom);
    }

    /**
     * Getter for xth.
     *
     * @param Event $event
     * @return array
     */
    protected function getXth(Event $event): array
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
     * @return array
     */
    protected function getWeekday(Event $event): array
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
    protected function getItemsFromTca(string $field): array
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
                    (int)$dateToStartCalculatingFrom->format('Y'),
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
     */
    protected function addDaysForMonth(string $month, int $year, Event $event)
    {
        // we need this to have a date where time is set to 00:00:00
        $day = $this->dateTimeUtility->convert('today');
        $lastDayOfMonth = $this->dateTimeUtility->convert('today');
        $lastDayOfMonth->modify('last day of ' . $month . ' ' . (string)$year . ' 23:59:59');
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
     * @throws \Exception
     */
    protected function addExceptions(Event $event)
    {
        foreach ($event->getExceptions() as $exception) {
            switch ($exception->getExceptionType()) {
                case 'Add':
                    $this->addException($event, $exception);
                    break;
                case 'Remove':
                    $this->removeDayFromStorage(
                        $this->dateTimeUtility->standardizeDateTimeObject(
                            $exception->getExceptionDate()
                        )
                    );
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

    /**
     * Add exception to dayStorage, if day matches range
     *
     * @param Event $event
     * @param Exception $exception
     */
    protected function addException(Event $event, Exception $exception)
    {
        $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($event);
        $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($event);
        $day = $this->dateTimeUtility->standardizeDateTimeObject($exception->getExceptionDate());

        var_dump($dateToStartCalculatingFrom);
        var_dump($dateToStopCalculatingTo);
        var_dump($day);

        if ($day >= $dateToStartCalculatingFrom && $day <= $dateToStopCalculatingTo) {
            $this->addDayToStorage($day);
        }
    }
}
