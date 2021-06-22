<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Event\PostGenerateDaysEvent;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

/*
 * Class to generate all day records for an event within configured range (ExtensionManager)
 */
class DayGenerator
{
    /**
     * @var array
     */
    protected $dateTimeStorage = [];

    /**
     * @var ExtConf
     */
    protected $extConf;

    /**
     * @var DateTimeUtility
     */
    protected $dateTimeUtility;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    public function __construct(
        EventDispatcher $eventDispatcher,
        ExtConf $extConf,
        DateTimeUtility $dateTimeUtility
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->extConf = $extConf;
        $this->dateTimeUtility = $dateTimeUtility;
    }

    /**
     * This method will not only initialize this object, it further will start generating all days for given event.
     *
     * @param array $eventRecord
     * @return bool
     * @throws \Exception
     */
    public function initialize(array $eventRecord): bool
    {
        // reset, because of previous calls
        $this->reset();

        // check for valid event record.
        // if false dateTimeStorage keep empty
        if (!$this->isValidEvent($eventRecord)) {
            return false;
        }

        // check for recurring event
        // do not add event start to recurring events. They will be generated automatically
        // further it could be that event start is not within the generated days
        if ((string)$eventRecord['event_type'] === 'recurring') {
            $this->addRecurringEvents($eventRecord);
        } elseif (
            (string)$eventRecord['event_type'] === 'duration' &&
            $eventRecord['event_end'] instanceof \DateTime
        ) {
            // if we have no recurring defined but event_end, this is also a recurring event and we have to add all days in between
            $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($eventRecord);
            $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($eventRecord);
            while ($dateToStartCalculatingFrom <= $dateToStopCalculatingTo) {
                $this->addDayToStorage($dateToStartCalculatingFrom);
                $dateToStartCalculatingFrom->modify('+1 day');
            }
        } else {
            // add start day
            $this->addDayToStorage($eventRecord['event_begin']);
        }

        // exclude or include further days if exceptions are activated
        if (is_array($eventRecord['exceptions'])) {
            $this->addExceptions($eventRecord);
        }

        $this->eventDispatcher->dispatch(
            new PostGenerateDaysEvent($eventRecord)
        );

        return true;
    }

    /**
     * Reset dateTimeStorage
     */
    protected function reset(): void
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
    public function setDateTimeStorage(array $dateTimeStorage): void
    {
        $this->dateTimeStorage = $dateTimeStorage;
    }

    protected function isValidEvent(array $eventRecord): bool
    {
        $valid = true;

        // some special fields must be set
        if (
            empty($eventRecord['event_type']) ||
            !$eventRecord['event_begin'] instanceof \DateTime
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
    protected function addDayToStorage(\DateTime $day): void
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
    protected function removeDayFromStorage(\DateTime $day): void
    {
        unset($this->dateTimeStorage[$day->format('U')]);
    }

    /**
     * Returns current date added with configured "recurring future" configuration in ExtensionManager.
     * If calculated date is older than eventEnd, it does not make sense to use that date and we
     * will return eventEnd instead.
     *
     * @param array $eventRecord
     * @return \DateTime
     * @throws \Exception
     */
    protected function getDateToStopCalculatingTo(array $eventRecord): \DateTime
    {
        $today = clone $this->dateTimeUtility->convert('today');
        $maxEventEnd = clone $eventRecord['event_begin'];

        if (!$today instanceof \DateTime || !$maxEventEnd instanceof \DateTime) {
            throw new \Exception('DayGenerator: event_begin is not of type DateTime', 1624290402);
        }

        // check, what is more current
        if ($today > $maxEventEnd) {
            $maxEventEnd = $today;
        }

        $maxEventEnd->modify(sprintf(
            '+%d months',
            $this->extConf->getRecurringFuture()
        ));

        if ((string)$eventRecord['event_type'] === 'duration') {
            $recurringEnd = $eventRecord['event_end'];
        } else {
            $recurringEnd = $eventRecord['recurring_end'];
        }

        if ($recurringEnd instanceof \DateTime && $recurringEnd < $maxEventEnd) {
            return $this->dateTimeUtility->standardizeDateTimeObject(clone $recurringEnd);
        }
        return $this->dateTimeUtility->standardizeDateTimeObject($maxEventEnd);
    }

    /**
     * Returns current date subtracted with configured "recurring past" configuration in ExtensionManager.
     * If calculated date is older than eventBegin, it does not make sense to use that date and we
     * will return eventBegin instead.
     *
     * @param array $eventRecord
     * @return \DateTime
     */
    protected function getDateToStartCalculatingFrom(array $eventRecord): \DateTime
    {
        $earliestDateToStartCalculatingFrom = clone $this->dateTimeUtility->convert('today');
        $earliestDateToStartCalculatingFrom->modify('-' . $this->extConf->getRecurringPast() . ' months');
        $eventBegin = clone $eventRecord['event_begin'];
        if (
            $eventBegin instanceof \DateTime
            && $earliestDateToStartCalculatingFrom > $eventBegin
        ) {
            $dateToStartCalculatingFrom = $earliestDateToStartCalculatingFrom;
        } else {
            $dateToStartCalculatingFrom = $eventBegin;
        }

        // In case of eachWeeks and eachMonth $dateToStartCalculatingFrom has to be
        // exactly in sync with eventBegin
        if ((int)$eventRecord['each_weeks'] || (int)$eventRecord['each_months']) {
            $eventBegin = clone $eventRecord['event_begin'];
            while ($eventBegin < $dateToStartCalculatingFrom) {
                $eventBegin->modify('+' . (int)$eventRecord['each_months'] . ' months');
                $eventBegin->modify('+' . (int)$eventRecord['each_weeks'] . ' weeks');
            }
            $dateToStartCalculatingFrom = $eventBegin;
        }

        return $this->dateTimeUtility->standardizeDateTimeObject($dateToStartCalculatingFrom);
    }

    /**
     * Getter for xth.
     *
     * @param array $eventRecord
     * @return array
     */
    protected function getXth(array $eventRecord): array
    {
        $result = [];
        foreach ($this->getItemsFromTca('xth') as $key => $item) {
            $value = (bool)((int)$eventRecord['xth'] & (2 ** $key));
            $result[$item[1]] = $value;
        }

        return $result;
    }

    /**
     * Getter for weekday.
     *
     * @param array $eventRecord
     * @return array
     */
    protected function getWeekday(array $eventRecord): array
    {
        $result = [];
        foreach ($this->getItemsFromTca('weekday') as $key => $item) {
            $value = (bool)((int)$eventRecord['weekday'] & (2 ** $key));
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
        }
        return [];
    }

    /**
     * Add days for recurring events.
     *
     * @param array $eventRecord
     */
    protected function addRecurringEvents(array $eventRecord): void
    {
        if ((int)$eventRecord['each_weeks'] || (int)$eventRecord['each_months']) {
            // add days for each week(s) and/or months
            $this->addRecurrings($eventRecord);
        } else {
            // add days for xth recurring event
            $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($eventRecord);
            // We need the first day, because January the 30th +1 month results in 02.03.
            // At that point it is no problem to set the date to the first, because we only need month and year.
            // You will find the check for the correct date in addDaysForMonth().
            $dateToStartCalculatingFrom->modify('first day of this month');
            $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($eventRecord);
            while ($dateToStartCalculatingFrom <= $dateToStopCalculatingTo) {
                $this->addDaysForMonth(
                    $dateToStartCalculatingFrom->format('F'),
                    (int)$dateToStartCalculatingFrom->format('Y'),
                    $eventRecord
                );
                $dateToStartCalculatingFrom->modify('next month');
            }
        }
    }

    /**
     * Add days for recurring weeks and/or months.
     *
     * @param array $eventRecord
     */
    protected function addRecurrings(array $eventRecord): void
    {
        $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($eventRecord);
        $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($eventRecord);
        while ($dateToStartCalculatingFrom <= $dateToStopCalculatingTo) {
            $this->addDayToStorage($dateToStartCalculatingFrom);
            $dateToStartCalculatingFrom->modify('+' . (int)$eventRecord['each_months'] . ' months');
            $dateToStartCalculatingFrom->modify('+' . (int)$eventRecord['each_weeks'] . ' weeks');
        }
    }

    protected function addDaysForMonth(string $month, int $year, array $eventRecord): void
    {
        // we need this to have a date where time is set to 00:00:00
        $day = $this->dateTimeUtility->convert('today');
        $lastDayOfMonth = $this->dateTimeUtility->convert('today');
        $lastDayOfMonth->modify('last day of ' . $month . ' ' . $year . ' 23:59:59');
        $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($eventRecord);
        $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($eventRecord);

        foreach ($this->getXth($eventRecord) as $xthIndex => $xth) {
            foreach ($this->getWeekday($eventRecord) as $weekdayIndex => $weekday) {
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
     * @param array $eventRecord
     * @throws \Exception
     */
    protected function addExceptions(array $eventRecord): void
    {
        foreach ($eventRecord['exceptions'] as $exceptionRecord) {
            switch ($exceptionRecord['exception_type']) {
                case 'Add':
                    $this->addException($eventRecord, $exceptionRecord);
                    break;
                case 'Remove':
                    $this->removeDayFromStorage(
                        $this->dateTimeUtility->standardizeDateTimeObject(
                            clone $exceptionRecord['exception_date']
                        )
                    );
                    break;
                case 'Info':
                case 'Time':
                    break;
                default:
                    throw new \Exception('"' . $exceptionRecord['exception_type'] . '" is no valid exception type', 1370003254);
            }
        }
    }

    /**
     * Add exception to dayStorage, if day matches range
     *
     * @param array $eventRecord
     * @param array $exceptionRecord
     */
    protected function addException(array $eventRecord, array $exceptionRecord): void
    {
        $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($eventRecord);
        $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($eventRecord);
        $day = $this->dateTimeUtility->standardizeDateTimeObject(clone $exceptionRecord['exception_date']);

        if ($day instanceof \DateTime && $day >= $dateToStartCalculatingFrom && $day <= $dateToStopCalculatingTo) {
            $this->addDayToStorage($day);
        }
    }
}
