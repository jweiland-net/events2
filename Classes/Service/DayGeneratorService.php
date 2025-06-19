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
use JWeiland\Events2\Event\PostGenerateDaysEvent;
use JWeiland\Events2\Service\Result\DateTimeResult;
use JWeiland\Events2\Service\Result\DayGeneratorResult;
use JWeiland\Events2\Tca\BitMask\WeekDayBitMask;
use JWeiland\Events2\Tca\BitMask\XthBitMask;
use JWeiland\Events2\Utility\DateTimeUtility;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class to generate all-day records for an event within a configured range (ExtensionManager).
 * It does now respect time information, just days. The time records will be processed later.
 */
class DayGeneratorService
{
    private const EVENT_TABLE = 'tx_events2_domain_model_event';

    private const EXCEPTION_TABLE = 'tx_events2_domain_model_exception';

    private const REQUIRED_EVENT_COLUMN = [
        'event_type',
        'event_begin',
        'event_end',
        'recurring_end',
        'each_weeks',
        'each_months',
        'xth',
        'weekday',
        'recurring_end',
        'exceptions',
    ];

    public function __construct(
        protected readonly EventDispatcher $eventDispatcher,
        protected readonly ExtConf $extConf,
        protected readonly DateTimeUtility $dateTimeUtility,
        protected readonly TimeService $timeService,
        protected readonly LoggerInterface $logger,
    ) {}

    public function getDateTimeStorageForEventRecord(array $eventRecord): DayGeneratorResult
    {
        $dayGeneratorResult = new DayGeneratorResult($eventRecord);

        try {
            $this->addDateTimeObjectsToRecord($eventRecord, self::EVENT_TABLE);
            $this->checkEventRecord($eventRecord);

            switch ($eventRecord['event_type']) {
                case 'recurring':
                    if ($eventRecord['each_weeks'] !== 0 || $eventRecord['each_months'] !== 0) {
                        $this->addDaysForWeeklyAndMonthlyEvent($dayGeneratorResult, $eventRecord);
                    } else {
                        $this->addDaysForRecurringEvent($dayGeneratorResult, $eventRecord);
                    }
                    $this->addEventExceptions($dayGeneratorResult, $eventRecord);
                    break;
                case 'duration':
                    $this->addDaysForDurationalEvent($dayGeneratorResult, $eventRecord);
                    $this->addEventExceptions($dayGeneratorResult, $eventRecord);
                    break;
                case 'single':
                    $this->addDaysForSingleEvent($dayGeneratorResult, $eventRecord);
                    break;
                default:
            }

            $this->eventDispatcher->dispatch(
                new PostGenerateDaysEvent($dayGeneratorResult, $eventRecord),
            );
        } catch (\Exception $exception) {
            $this->logger->error(sprintf(
                'Error occurred while building DateTime objects in DayGeneratorService at line %d: %s: %d',
                $exception->getLine(),
                $exception->getMessage(),
                $exception->getCode(),
            ));

            return new DayGeneratorResult($eventRecord);
        }

        $this->timeService->enrichWithTimeAndBuildDayRecords($dayGeneratorResult);

        return $dayGeneratorResult;
    }

    /**
     * @throws \Exception
     */
    protected function addDateTimeObjectsToRecord(array &$record, string $table): void
    {
        foreach ($record as $column => $value) {
            if (($GLOBALS['TCA'][$table]['columns'][$column]['config']['type'] ?? '') === 'datetime') {
                $record[$column] = $this->dateTimeUtility->convert($value);
            }
        }

        if ($table === self::EVENT_TABLE) {
            foreach ($record['exceptions'] as &$exceptionRecord) {
                $this->addDateTimeObjectsToRecord(
                    $exceptionRecord,
                    self::EXCEPTION_TABLE,
                );
            }
        }
    }

    /**
     * Check if the event record contains all necessary properties to process.
     *
     * @throws \Exception
     */
    protected function checkEventRecord(array $eventRecord): void
    {
        foreach (self::REQUIRED_EVENT_COLUMN as $requiredColumn) {
            if (!array_key_exists($requiredColumn, $eventRecord)) {
                throw new \Exception(sprintf(
                    'Invalid event record. It does not contain mandatory property: %s.',
                    $requiredColumn,
                ), 1649074484);
            }
        }

        if ($eventRecord['event_type'] === '') {
            throw new \Exception(
                'Invalid event record: Column "event_type" can not be empty.',
                1649074506,
            );
        }

        if (
            $eventRecord['event_type'] === 'duration'
            && !$eventRecord['event_end'] instanceof \DateTimeImmutable
        ) {
            throw new \Exception(
                'Invalid event record: If event_type is set to "duration" column "event_end" has to be set.',
                1649074514,
            );
        }

        if (!$eventRecord['event_begin'] instanceof \DateTimeImmutable) {
            throw new \Exception(
                'Invalid event record: Column "event_begin" can not be empty.',
                1649074525,
            );
        }

        foreach ($eventRecord['exceptions'] as $exceptionRecord) {
            if (!$exceptionRecord['exception_date'] instanceof \DateTimeImmutable) {
                throw new \Exception(
                    'Invalid exception record: Column "exception_date" can not be empty.',
                    1649139356,
                );
            }
        }

        // As long as these values will be generated by DateTimeUtility the Exceptions should never be called
        if ($eventRecord['event_begin']->format('H:i:s') !== '00:00:00') {
            throw new \Exception('Column "event_begin" was given with a time other than 00:00:00 which is not allowed.');
        }
        if (
            $eventRecord['event_end'] instanceof \DateTimeImmutable
            && $eventRecord['event_end']->format('H:i:s') !== '00:00:00'
        ) {
            throw new \Exception('Column "event_end" was given with a time other than 00:00:00 which is not allowed.');
        }
        if (
            $eventRecord['recurring_end'] instanceof \DateTimeImmutable
            && $eventRecord['recurring_end']->format('H:i:s') !== '00:00:00'
        ) {
            throw new \Exception('Column "recurring_end" was given with a time other than 00:00:00 which is not allowed.');
        }
    }

    /**
     * @throws \Exception
     */
    protected function addDaysForRecurringEvent(DayGeneratorResult $dayGeneratorResult, array $eventRecord): void
    {
        $dateToStopCalculatingTo = $this->getEndDateForCalculation($eventRecord);
        $firstDayOfMonth = $this->resetDateTimeToFirstDayOfMonth(
            $this->getStartDateForCalculation($eventRecord),
        );

        while ($firstDayOfMonth <= $dateToStopCalculatingTo) {
            $this->addDaysForMonth(
                $dayGeneratorResult,
                $firstDayOfMonth->format('F'),
                (int)$firstDayOfMonth->format('Y'),
                $eventRecord,
            );
            $firstDayOfMonth = $this->modifyDateTime($firstDayOfMonth, 'next month');
        }
    }

    /**
     * @throws \Exception
     */
    protected function addDaysForDurationalEvent(DayGeneratorResult $dayGeneratorResult, array $eventRecord): void
    {
        $dateToStartCalculatingFrom = $this->getStartDateForCalculation($eventRecord);
        $dateToStopCalculatingTo = $this->getEndDateForCalculation($eventRecord);

        while ($dateToStartCalculatingFrom <= $dateToStopCalculatingTo) {
            $this->addDateTimeToStorage($dayGeneratorResult, $dateToStartCalculatingFrom);
            $dateToStartCalculatingFrom = $dateToStartCalculatingFrom->modify('+1 day');
        }
    }

    /**
     * @throws \Exception
     */
    protected function addDaysForSingleEvent(DayGeneratorResult $dayGeneratorResult, array $eventRecord): void
    {
        $earliestDateOfTimeFrame = $this->createDateTime(sprintf(
            '-%d months',
            $this->extConf->getRecurringPast(),
        ));

        $latestDateOfTimeFrame = $this->modifyDateTime(
            $eventRecord['event_begin'],
            sprintf(
                '+%d months 23:59:59',
                $this->extConf->getRecurringFuture(),
            ),
        );

        if (
            $eventRecord['event_begin'] > $earliestDateOfTimeFrame
            && $eventRecord['event_begin'] < $latestDateOfTimeFrame
        ) {
            $this->addDateTimeToStorage($dayGeneratorResult, $eventRecord['event_begin']);
        }
    }

    /**
     * @throws \Exception
     */
    protected function addDaysForWeeklyAndMonthlyEvent(DayGeneratorResult $dayGeneratorResult, array $eventRecord): void
    {
        $dateToStartCalculatingFrom = $this->getStartDateForCalculation($eventRecord);
        $dateToStopCalculatingTo = $this->getEndDateForCalculation($eventRecord);

        while ($dateToStartCalculatingFrom <= $dateToStopCalculatingTo) {
            $this->addDateTimeToStorage($dayGeneratorResult, $dateToStartCalculatingFrom);
            $dateToStartCalculatingFrom = $dateToStartCalculatingFrom->modify(
                '+' . $eventRecord['each_months'] . ' months',
            );
            $dateToStartCalculatingFrom = $dateToStartCalculatingFrom->modify(
                '+' . $eventRecord['each_weeks'] . ' weeks',
            );
        }
    }

    /**
     * @throws \Exception
     */
    protected function addDaysForMonth(
        DayGeneratorResult $dayGeneratorResult,
        string $month,
        int $year,
        array $eventRecord
    ): void {
        $dynamicDateTimeAtMidnight = $this->getDateTimeForToday();
        $lastDayOfMonth = $this->getLastDateTimeOfMonthAndYear($month, $year);
        $dateToStartCalculatingFrom = $this->getStartDateForCalculation($eventRecord);
        $dateToStopCalculatingTo = $this->getEndDateForCalculation($eventRecord);

        $xthBitMask = GeneralUtility::makeInstance(XthBitMask::class, $eventRecord['xth']);
        $weekDayBitMask = GeneralUtility::makeInstance(WeekDayBitMask::class, $eventRecord['weekday']);

        foreach ($xthBitMask->getSelectedWeeks() as $xthIndex => $xth) {
            foreach ($weekDayBitMask->getSelectedWeekdays() as $weekdayIndex => $weekday) {
                // Example: 'second wednesday of March 2013'
                $modifyString = $xthIndex . ' ' . $weekdayIndex . ' of ' . $month . ' ' . $year;
                $dynamicDateTimeAtMidnight = $this->modifyDateTime($dynamicDateTimeAtMidnight, $modifyString);

                if (
                    $dynamicDateTimeAtMidnight >= $dateToStartCalculatingFrom
                    && $dynamicDateTimeAtMidnight < $lastDayOfMonth
                    && $dynamicDateTimeAtMidnight <= $dateToStopCalculatingTo
                ) {
                    $this->addDateTimeToStorage($dayGeneratorResult, $dynamicDateTimeAtMidnight);
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function getLastDateTimeOfMonthAndYear(string $month, int $year): \DateTimeImmutable
    {
        // Do not reset time to midnight, as we want to keep the given time.
        return $this->createDateTime(
            'last day of ' . $month . ' ' . $year . ' 23:59:59',
            false,
        );
    }

    /**
     * Keep the month of given DateTime, but reset day of month to first of month.
     * 15.03.2020 => 01.03.2020
     *
     * @throws \Exception
     */
    protected function resetDateTimeToFirstDayOfMonth(\DateTimeImmutable $date): \DateTimeImmutable
    {
        return $this->modifyDateTime(
            $date,
            'first day of this month',
        );
    }

    /**
     * @throws \Exception
     */
    protected function createDateTime(string $modifier, bool $resetToMidnight = true): \DateTimeImmutable
    {
        try {
            $newDateTime = new \DateTimeImmutable($modifier);
            $newDateTime = $newDateTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));

            if ($resetToMidnight) {
                $newDateTime = $this->dateTimeUtility->standardizeDateTimeObject($newDateTime);
            }
        } catch (\Exception) {
            throw new \Exception(sprintf(
                'Creation of new DateTime object with modifier "%s" failed.',
                $modifier,
            ), 1649066132);
        }

        return $newDateTime;
    }

    /**
     * @throws \Exception
     */
    protected function modifyDateTime(\DateTimeImmutable $dateTime, string $modifier): \DateTimeImmutable
    {
        try {
            $modifiedDateTime = $dateTime->modify($modifier);
        } catch (\Exception) {
            throw new \Exception(sprintf(
                'Given DateTime object could not be modified with modifier "%s"',
                $modifier,
            ), 1649066006);
        }

        return $modifiedDateTime;
    }

    /**
     * Returns DateTime object for today midnight
     *
     * @throws \Exception
     */
    protected function getDateTimeForToday(): \DateTimeImmutable
    {
        return $this->createDateTime('today');
    }

    /**
     * Returns current date subtracted with configured "recurring past" configuration in ExtensionManager.
     * If the calculated date is older than eventBegin, it does not make sense to use that date, and we
     * will return eventBegin instead.
     *
     * @throws \Exception
     */
    protected function getStartDateForCalculation(array $eventRecord): \DateTimeImmutable
    {
        $eventBegin = $eventRecord['event_begin'];
        $dateToStartCalculatingFrom = $this->getEarliestDateForTimeFrame($eventBegin);

        // In the case of eachWeeks and eachMonth $dateToStartCalculatingFrom has to be
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

    /**
     * This method creates a DateTime object based on recurring_past configuration of Extension Settings.
     * It returns the date which is more current.
     *
     * @throws \Exception
     */
    protected function getEarliestDateForTimeFrame(\DateTimeImmutable $dateForComparison): \DateTimeImmutable
    {
        $earliestDateOfTimeFrame = $this->createDateTime(sprintf(
            '-%d months',
            $this->extConf->getRecurringPast(),
        ));

        if ($earliestDateOfTimeFrame > $dateForComparison) {
            return $earliestDateOfTimeFrame;
        }

        return $dateForComparison;
    }

    /**
     * Returns current date added with configured "recurring future" configuration in ExtensionManager.
     * If the calculated date is older than eventEnd, it does not make sense to use that date, and we
     * will return eventEnd instead.
     *
     * @throws \Exception
     */
    protected function getEndDateForCalculation(array $eventRecord): \DateTimeImmutable
    {
        $today = $this->createDateTime('today');
        $eventBegin = $eventRecord['event_begin'];
        $latestDateOfTimeFrame = $this->modifyDateTime(
            max($today, $eventBegin),
            sprintf(
                '+%d months 23:59:59',
                $this->extConf->getRecurringFuture(),
            ),
        );

        /** @var ?\DateTimeImmutable $latestEventDate recurring_end can be null! */
        $latestEventDate = $eventRecord['event_type'] === 'duration'
            ? $eventRecord['event_end']
            : $eventRecord['recurring_end'];

        if (!$latestEventDate instanceof \DateTimeImmutable) {
            return $latestDateOfTimeFrame;
        }

        return $latestEventDate < $latestDateOfTimeFrame ? $latestEventDate : $latestDateOfTimeFrame;
    }

    protected function addDateTimeToStorage(
        DayGeneratorResult $dayGeneratorResult,
        \DateTimeImmutable $dateTime,
        bool $isRemovedDate = false,
    ): void {
        // To prevent adding multiple day records for ONE day we set them all to midnight 00:00:00
        $dateTime = $this->dateTimeUtility->standardizeDateTimeObject($dateTime);

        // group days to make them unique
        $dayGeneratorResult->addDateTimeResult(new DateTimeResult(
            $dateTime,
            $isRemovedDate,
        ));
    }

    protected function removeDayFromStorage(DayGeneratorResult $dayGeneratorResult, \DateTimeImmutable $day): void
    {
        $dayGeneratorResult->removeDateTimeResultByDate($day);
    }

    /**
     * @throws \Exception
     */
    protected function addEventExceptions(DayGeneratorResult $dayGeneratorResult, array $eventRecord): void
    {
        if (!$this->hasEventExceptions($eventRecord)) {
            return;
        }

        foreach ($eventRecord['exceptions'] as $exceptionRecord) {
            $this->checkExceptionRecord($exceptionRecord);

            if ($exceptionRecord['hidden']) {
                continue;
            }

            switch ($exceptionRecord['exception_type']) {
                case 'Add':
                    $this->addEventException($dayGeneratorResult, $eventRecord, $exceptionRecord);
                    break;
                case 'Remove':
                    // Do not remove DateTime from Storage if exception records of the type "Removed" should be shown in FE anyway
                    if ($exceptionRecord['show_anyway'] === 0) {
                        $this->removeDayFromStorage($dayGeneratorResult, $exceptionRecord['exception_date']);
                    } else {
                        $this->addDateTimeToStorage($dayGeneratorResult, $exceptionRecord['exception_date'], true);
                    }
                    break;
                case 'Info':
                case 'Time':
                    break;
                default:
                    throw new \Exception(
                        sprintf(
                            'Type "%s" is no valid exception type',
                            $exceptionRecord['exception_type'],
                        ),
                        1370003254,
                    );
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function addEventException(DayGeneratorResult $dayGeneratorResult, array $eventRecord, array $exceptionRecord): void
    {
        $dateToStartCalculatingFrom = $this->getStartDateForCalculation($eventRecord);
        $dateToStopCalculatingTo = $this->getEndDateForCalculation($eventRecord);
        $exceptionDate = $exceptionRecord['exception_date'];

        if ($exceptionDate < $dateToStartCalculatingFrom) {
            return;
        }

        if ($exceptionDate > $dateToStopCalculatingTo) {
            return;
        }

        if ($exceptionDate instanceof \DateTimeImmutable) {
            $this->addDateTimeToStorage($dayGeneratorResult, $exceptionDate);
        }
    }

    protected function hasEventExceptions(array $eventRecord): bool
    {
        return is_array($eventRecord['exceptions'])
            && $eventRecord['exceptions'] !== []
            && in_array($eventRecord['event_type'], ['recurring', 'duration']);
    }

    /**
     * @throws \Exception
     */
    protected function checkExceptionRecord(array $exceptionRecord): void
    {
        $isValid = isset(
            $exceptionRecord['hidden'],
            $exceptionRecord['exception_type'],
            $exceptionRecord['exception_date'],
            $exceptionRecord['show_anyway'],
        );

        if (!$isValid) {
            throw new \Exception(
                'Exception record does not contain all needed columns to create DateTime storage',
                1651151453,
            );
        }
    }
}
