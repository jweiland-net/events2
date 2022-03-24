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
use JWeiland\Events2\Tca\BitMask\WeekDayBitMask;
use JWeiland\Events2\Tca\BitMask\XthBitMask;
use JWeiland\Events2\Utility\DateTimeUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * Class to generate all day records for an event within a configured range (ExtensionManager).
 * It does now respect time information, just days. The time records will be processed later.
 */
class DayGenerator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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
     * @throws \Exception
     */
    public function getDateTimeStorageForEvent(array $eventRecord): array
    {
        // Reset, because of recurring calls
        $this->dateTimeStorage = [];

        try {
            if (!$this->isValidEventRecord($eventRecord)) {
                return [];
            }

            if ($eventRecord['event_type'] === 'recurring') {
                $this->addRecurringEvents($eventRecord);
            } elseif (
                $eventRecord['event_type'] === 'duration'
                && $eventRecord['event_end'] > 0
            ) {
                // if we have no recurring defined, but event_end, this is also a recurring event, and we have to add all days in between
                $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($eventRecord);
                $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($eventRecord);
                if (
                    $dateToStartCalculatingFrom instanceof \DateTimeImmutable
                    && $dateToStopCalculatingTo instanceof \DateTimeImmutable
                ) {
                    while ($dateToStartCalculatingFrom <= $dateToStopCalculatingTo) {
                        $this->addDateTimeToStorage($dateToStartCalculatingFrom);
                        $dateToStartCalculatingFrom = $dateToStartCalculatingFrom->modify('+1 day');
                    }
                }
            } elseif (
                ($eventBegin = $this->dateTimeUtility->convert($eventRecord['event_begin']))
                && $eventBegin instanceof \DateTimeImmutable
            ) {
                // Just add the event_begin to DateTimeStorage
                $this->addDateTimeToStorage($eventBegin);
            }

            $this->addEventExceptions($eventRecord);

            $this->eventDispatcher->dispatch(
                new PostGenerateDaysEvent($eventRecord)
            );
        } catch (\Exception $exception) {
            $this->logger->error(sprintf(
                'Error occurred in DayGenerator at line %d: %s',
                $exception->getLine(),
                $exception->getMessage()
            ));
        }

        ksort($this->dateTimeStorage);

        return $this->dateTimeStorage;
    }

    /**
     * Check, if event record contains all needed properties to process.
     */
    protected function isValidEventRecord(array $eventRecord): bool
    {
        $neededProperties = [
            'event_type',
            'event_begin',
            'event_end',
            'recurring_end',
            'each_weeks',
            'each_months',
            'xth',
            'weekday',
            'recurring_end',
            'exceptions'
        ];

        foreach ($neededProperties as $neededProperty) {
            if (!array_key_exists($neededProperty, $eventRecord)) {
                $this->logger->error(
                    sprintf(
                        '$eventRecord does not contain all needed properties. Missing %s in DayGenerator.',
                        $neededProperty
                    )
                );
                return false;
            }
        }

        if ($eventRecord['event_type'] === '') {
            return false;
        }

        if ($eventRecord['event_begin'] === 0) {
            return false;
        }

        return true;
    }

    protected function addDateTimeToStorage(\DateTimeImmutable $dateTime): void
    {
        // To prevent adding multiple day records for ONE day we set them all to midnight 00:00:00
        $dateTime = $this->dateTimeUtility->standardizeDateTimeObject($dateTime);

        // group days to make them unique
        $this->dateTimeStorage[$dateTime->format('U')] = $dateTime;
    }

    protected function removeDayFromStorage(\DateTimeImmutable $day): void
    {
        unset($this->dateTimeStorage[$day->format('U')]);
    }

    /**
     * Returns current date added with configured "recurring future" configuration in ExtensionManager.
     * If calculated date is older than eventEnd, it does not make sense to use that date, and we
     * will return eventEnd instead.
     */
    protected function getDateToStopCalculatingTo(array $eventRecord): ?\DateTimeImmutable
    {
        try {
            // Do not use DateTimeUtility here, because date will be reset to midnight.
            $today = new \DateTimeImmutable(
                'today 23:59:59',
                new \DateTimeZone(date_default_timezone_get())
            );
        } catch (\Exception $exception) {
            return null;
        }

        $eventBegin = $this->dateTimeUtility->convert($eventRecord['event_begin']);
        if (!$eventBegin instanceof \DateTimeImmutable) {
            return null;
        }

        // Use most current value as event_begin
        if ($today > $eventBegin) {
            $eventBegin = $today;
        }

        $maxEventEnd = $eventBegin->modify(sprintf(
            '+%d months',
            $this->extConf->getRecurringFuture()
        ));

        $recurringEnd = $this->dateTimeUtility->convert(
            $eventRecord['event_type'] === 'duration'
                ? $eventRecord['event_end']
                : $eventRecord['recurring_end']
        );

        if (
            $recurringEnd instanceof \DateTimeImmutable
            && $recurringEnd < $maxEventEnd
        ) {
            return $this->dateTimeUtility->standardizeDateTimeObject($recurringEnd);
        }

        return $this->dateTimeUtility->standardizeDateTimeObject($maxEventEnd);
    }

    /**
     * Returns current date subtracted with configured "recurring past" configuration in ExtensionManager.
     * If calculated date is older than eventBegin, it does not make sense to use that date and we
     * will return eventBegin instead.
     */
    protected function getDateToStartCalculatingFrom(array $eventRecord): ?\DateTimeImmutable
    {
        $today = $this->dateTimeUtility->convert('today');
        if (!$today instanceof \DateTimeImmutable) {
            return null;
        }

        $earliestDateToStartCalculatingFrom = $today->modify(
            '-' . $this->extConf->getRecurringPast() . ' months'
        );

        $eventBegin = $this->dateTimeUtility->convert($eventRecord['event_begin']);
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
        if ($eventRecord['each_weeks'] !== 0 || $eventRecord['each_months'] !== 0) {
            while ($eventBegin < $dateToStartCalculatingFrom) {
                $eventBegin = $eventBegin->modify('+' . $eventRecord['each_months'] . ' months');
                $eventBegin = $eventBegin->modify('+' . $eventRecord['each_weeks'] . ' weeks');
            }

            $dateToStartCalculatingFrom = $eventBegin;
        }

        return $this->dateTimeUtility->standardizeDateTimeObject($dateToStartCalculatingFrom);
    }

    protected function addRecurringEvents(array $eventRecord): void
    {
        if ($eventRecord['each_weeks'] !== 0 || $eventRecord['each_months'] !== 0) {
            // add days for each week(s) and/or months
            $this->addRecurringDays($eventRecord);
        } else {
            // add days for xth recurring event
            $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($eventRecord);
            $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($eventRecord);
            if (
                !$dateToStartCalculatingFrom instanceof \DateTimeImmutable
                || !$dateToStopCalculatingTo instanceof \DateTimeImmutable
            ) {
                return;
            }

            // We need the first day, because January the 30th +1 month results in 02.03.
            // At that point it is no problem to set the date to the first, because we only need month and year.
            // You will find the check for the correct date in addDaysForMonth().
            $firstDayOfMonth = $dateToStartCalculatingFrom->modify('first day of this month');

            while ($firstDayOfMonth <= $dateToStopCalculatingTo) {
                $this->addDaysForMonth(
                    $firstDayOfMonth->format('F'),
                    (int)$firstDayOfMonth->format('Y'),
                    $eventRecord
                );
                $firstDayOfMonth = $firstDayOfMonth->modify('next month');
            }
        }
    }

    /**
     * Add days for recurring weeks and/or months.
     */
    protected function addRecurringDays(array $eventRecord): void
    {
        $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($eventRecord);
        $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($eventRecord);

        if (
            $dateToStartCalculatingFrom instanceof \DateTimeImmutable
            && $dateToStopCalculatingTo instanceof \DateTimeImmutable
        ) {
            while ($dateToStartCalculatingFrom <= $dateToStopCalculatingTo) {
                $this->addDateTimeToStorage($dateToStartCalculatingFrom);
                $dateToStartCalculatingFrom = $dateToStartCalculatingFrom->modify(
                    '+' . $eventRecord['each_months'] . ' months'
                );
                $dateToStartCalculatingFrom = $dateToStartCalculatingFrom->modify(
                    '+' . $eventRecord['each_weeks'] . ' weeks'
                );
            }
        }
    }

    protected function addDaysForMonth(string $month, int $year, array $eventRecord): void
    {
        $dynamicDateTimeAtMidnight = $this->dateTimeUtility->convert('today');
        if (!$dynamicDateTimeAtMidnight instanceof \DateTimeImmutable) {
            return;
        }

        try {
            // Do not use DateTimeUtility here, because date will be reset to midnight.
            $lastDayOfMonth = new \DateTimeImmutable(
                'last day of ' . $month . ' ' . $year . ' 23:59:59',
                new \DateTimeZone(date_default_timezone_get())
            );
        } catch (\Exception $exception) {
            return;
        }

        $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($eventRecord); // prevent from calling it multiple times in foreach
        $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($eventRecord); // prevent from calling it multiple times in foreach

        if (
            !$dateToStartCalculatingFrom instanceof \DateTimeImmutable
            || !$dateToStopCalculatingTo instanceof \DateTimeImmutable
        ) {
            return;
        }

        $xthBitMask = GeneralUtility::makeInstance(XthBitMask::class, $eventRecord['xth']);
        $weekDayBitMask = GeneralUtility::makeInstance(WeekDayBitMask::class, $eventRecord['weekday']);

        foreach ($xthBitMask->getSelectedWeeks() as $xthIndex => $xth) {
            foreach ($weekDayBitMask->getSelectedWeekdays() as $weekdayIndex => $weekday) {
                // example: 'second wednesday of March 2013'
                $modifyString = $xthIndex . ' ' . $weekdayIndex . ' of ' . $month . ' ' . $year;
                $dynamicDateTimeAtMidnight = $dynamicDateTimeAtMidnight->modify($modifyString);

                if (!$dynamicDateTimeAtMidnight instanceof \DateTimeImmutable) {
                    $this->logger->error(sprintf(
                        'Invalid DateTime object after "->modify" with "%s"',
                            $modifyString
                        )
                    );
                    break 2;
                }

                if (
                    $dynamicDateTimeAtMidnight >= $dateToStartCalculatingFrom
                    && $dynamicDateTimeAtMidnight < $lastDayOfMonth
                    && $dynamicDateTimeAtMidnight <= $dateToStopCalculatingTo
                ) {
                    $this->addDateTimeToStorage($dynamicDateTimeAtMidnight);
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function addEventExceptions(array $eventRecord): void
    {
        if (!$this->hasEventExceptions($eventRecord)) {
            return;
        }

        foreach ($eventRecord['exceptions'] as $exceptionRecord) {
            switch ($exceptionRecord['exception_type']) {
                case 'Add':
                    $this->addEventException($eventRecord, $exceptionRecord);
                    break;
                case 'Remove':
                    $this->removeDayFromStorage(
                        $this->dateTimeUtility->convert(
                            $exceptionRecord['exception_date']
                        )
                    );
                    break;
                case 'Info':
                case 'Time':
                    break;
                default:
                    throw new \Exception(
                        sprintf(
                            'Type "%s" is no valid exception type',
                            $exceptionRecord['exception_type']
                        ),
                        1370003254
                    );
            }
        }
    }

    protected function addEventException(array $eventRecord, Exception $exception): void
    {
        $dateToStartCalculatingFrom = $this->getDateToStartCalculatingFrom($eventRecord);
        $dateToStopCalculatingTo = $this->getDateToStopCalculatingTo($eventRecord);
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
            $this->addDateTimeToStorage($day);
        }
    }

    protected function hasEventExceptions(array $eventRecord): bool
    {
        return is_array($eventRecord['exceptions'])
            && $eventRecord['exceptions'] !== []
            && in_array($eventRecord['event_type'], ['recurring', 'duration']);
    }
}
