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
use JWeiland\Events2\Domain\Model\Event;
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
     * day storage.
     */
    protected array $dateTimeStorage = [];

    protected ExtConf $extConf;

    protected DateTimeUtility $dateTimeUtility;

    protected EventDispatcher $eventDispatcher;

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
            $event->getEventEnd() instanceof \DateTimeImmutable
        ) {
            // if we have no recurring defined, but event_end, this is also a recurring event, and we have to add all days in between
            $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($event);
            $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($event);
            if (
                $dateToStartCalculatingFrom instanceof \DateTimeImmutable
                && $dateToStopCalculatingTo instanceof \DateTimeImmutable
            ) {
                while ($dateToStartCalculatingFrom <= $dateToStopCalculatingTo) {
                    $this->addDayToStorage($dateToStartCalculatingFrom);
                    $dateToStartCalculatingFrom = $dateToStartCalculatingFrom->modify('+1 day');
                }
            }
        } else {
            // add start day
            $this->addDayToStorage($event->getEventBegin());
        }

        // exclude or include further days for events of type recurring or duration
        if (in_array($event->getEventType(), ['recurring', 'duration']) && $event->getExceptions()->count()) {
            $this->addExceptions($event);
        }

        $this->eventDispatcher->dispatch(
            new PostGenerateDaysEvent($event)
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
     * @return \DateTimeImmutable[]
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
     * @param \DateTimeImmutable[] $dateTimeStorage
     */
    public function setDateTimeStorage(array $dateTimeStorage): void
    {
        $this->dateTimeStorage = $dateTimeStorage;
    }

    /**
     * Check, if event record is a valid event.
     */
    protected function isValidEvent(Event $event): bool
    {
        // some special fields must be set
        return !empty($event->getEventType()) && $event->getEventBegin() instanceof \DateTimeImmutable;
    }

    protected function addDayToStorage(\DateTimeImmutable $day): void
    {
        // group days to make them unique
        $this->dateTimeStorage[$day->format('U')] = $day;
    }

    protected function removeDayFromStorage(\DateTimeImmutable $day): void
    {
        unset($this->dateTimeStorage[$day->format('U')]);
    }

    /**
     * Returns current date added with configured "recurring future" configuration in ExtensionManager.
     * If calculated date is older than eventEnd, it does not make sense to use that date and we
     * will return eventEnd instead.
     */
    protected function getDateToStopCalculatingTo(Event $event): \DateTimeImmutable
    {
        $today = clone $this->dateTimeUtility->convert('today');
        $eventBegin = $event->getEventBegin();

        // check, what is more current
        if ($today > $eventBegin) {
            $eventBegin = $today;
        }

        $maxEventEnd = $eventBegin->modify(sprintf(
            '+%d months',
            $this->extConf->getRecurringFuture()
        ));

        $recurringEnd = $event->getEventType() === 'duration' ? $event->getEventEnd() : $event->getRecurringEnd();

        if ($recurringEnd instanceof \DateTimeImmutable && $recurringEnd < $maxEventEnd) {
            return $this->dateTimeUtility->standardizeDateTimeObject($recurringEnd);
        }

        return $this->dateTimeUtility->standardizeDateTimeObject($maxEventEnd);
    }

    /**
     * Returns current date subtracted with configured "recurring past" configuration in ExtensionManager.
     * If calculated date is older than eventBegin, it does not make sense to use that date and we
     * will return eventBegin instead.
     */
    protected function getDateToStartCalculatingFrom(Event $event): ?\DateTimeImmutable
    {
        $today = $this->dateTimeUtility->convert('today');
        if ($today === null) {
            return null;
        }

        $earliestDateToStartCalculatingFrom = $today->modify('-' . $this->extConf->getRecurringPast() . ' months');

        $eventBegin = $event->getEventBegin();
        if (
            $eventBegin instanceof \DateTimeImmutable
            && $earliestDateToStartCalculatingFrom > $eventBegin
        ) {
            $dateToStartCalculatingFrom = $earliestDateToStartCalculatingFrom;
        } else {
            $dateToStartCalculatingFrom = $eventBegin;
        }

        // In case of eachWeeks and eachMonth $dateToStartCalculatingFrom has to be
        // exactly in sync with eventBegin
        if ($event->getEachWeeks() || $event->getEachMonths()) {
            $eventBegin = clone $event->getEventBegin();
            while ($eventBegin < $dateToStartCalculatingFrom) {
                $eventBegin = $eventBegin->modify('+' . $event->getEachMonths() . ' months');
                $eventBegin = $eventBegin->modify('+' . $event->getEachWeeks() . ' weeks');
            }

            $dateToStartCalculatingFrom = $eventBegin;
        }

        return $this->dateTimeUtility->standardizeDateTimeObject($dateToStartCalculatingFrom);
    }

    protected function addRecurringEvents(Event $event): void
    {
        if ($event->getEachWeeks() || $event->getEachMonths()) {
            // add days for each week(s) and/or months
            $this->addRecurringDays($event);
        } else {
            // add days for xth recurring event
            $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($event);

            $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($event);
            if (
                $dateToStartCalculatingFrom instanceof \DateTimeImmutable
                && $dateToStopCalculatingTo instanceof \DateTimeImmutable
            ) {
                // We need the first day, because January the 30th +1 month results in 02.03.
                // At that point it is no problem to set the date to the first, because we only need month and year.
                // You will find the check for the correct date in addDaysForMonth().
                $firstDayOfMonth = $dateToStartCalculatingFrom->modify('first day of this month');

                while ($firstDayOfMonth <= $dateToStopCalculatingTo) {
                    $this->addDaysForMonth(
                        $firstDayOfMonth->format('F'),
                        (int)$firstDayOfMonth->format('Y'),
                        $event
                    );
                    $firstDayOfMonth = $firstDayOfMonth->modify('next month');
                }
            }
        }
    }

    /**
     * Add days for recurring weeks and/or months.
     */
    protected function addRecurringDays(Event $event): void
    {
        $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($event);
        $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($event);

        if (
            $dateToStartCalculatingFrom instanceof \DateTimeImmutable
            && $dateToStopCalculatingTo instanceof \DateTimeImmutable
        ) {
            while ($dateToStartCalculatingFrom <= $dateToStopCalculatingTo) {
                $this->addDayToStorage($dateToStartCalculatingFrom);
                $dateToStartCalculatingFrom = $dateToStartCalculatingFrom->modify(
                    '+' . $event->getEachMonths() . ' months'
                );
                $dateToStartCalculatingFrom = $dateToStartCalculatingFrom->modify(
                    '+' . $event->getEachWeeks() . ' weeks'
                );
            }
        }
    }

    protected function addDaysForMonth(string $month, int $year, Event $event): void
    {
        $dynamicDateTimeAtMidnight = $this->dateTimeUtility->convert('today');
        if ($dynamicDateTimeAtMidnight === null) {
            return;
        }

        $lastDayOfMonth = $this->dateTimeUtility->convert('last day of ' . $month . ' ' . $year . ' 23:59:59');
        if ($lastDayOfMonth === null) {
            return;
        }

        $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($event); // prevent from calling it multiple times in foreach
        $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($event); // prevent from calling it multiple times in foreach

        if (
            $dateToStartCalculatingFrom instanceof \DateTimeImmutable
            && $dateToStopCalculatingTo instanceof \DateTimeImmutable
        ) {
            foreach ($event->getXth() as $xthIndex => $xth) {
                foreach ($event->getWeekday() as $weekdayIndex => $weekday) {
                    if ($xth && $weekday) {
                        // example: 'second wednesday of March 2013'
                        $modifyString = $xthIndex . ' ' . $weekdayIndex . ' of ' . $month . ' ' . $year;
                        $dynamicDateTimeAtMidnight = $dynamicDateTimeAtMidnight->modify($modifyString);
                        if (
                            $dynamicDateTimeAtMidnight >= $dateToStartCalculatingFrom
                            && $dynamicDateTimeAtMidnight < $lastDayOfMonth
                            && $dynamicDateTimeAtMidnight <= $dateToStopCalculatingTo
                        ) {
                            $this->addDayToStorage($dynamicDateTimeAtMidnight);
                        }
                    }
                }
            }
        }
    }

    /**
     * Add event exceptions.
     *
     * @throws \Exception
     */
    protected function addExceptions(Event $event): void
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
                case 'Info':
                case 'Time':
                    break;
                default:
                    throw new \Exception('"' . $exception->getExceptionType() . '" is no valid exception type', 1370003254);
            }
        }
    }

    /**
     * Add exception to dayStorage, if day matches range
     */
    protected function addException(Event $event, Exception $exception): void
    {
        $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($event);
        $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($event);
        $day = $this->dateTimeUtility->standardizeDateTimeObject($exception->getExceptionDate());

        if (
            $dateToStartCalculatingFrom instanceof \DateTimeImmutable
            && $day < $dateToStartCalculatingFrom
        ) {
            return;
        }

        if (
            $dateToStopCalculatingTo instanceof \DateTimeImmutable
            && $day > $dateToStopCalculatingTo
        ) {
            return;
        }

        if ($day instanceof \DateTimeImmutable) {
            $this->addDayToStorage($day);
        }
    }
}
