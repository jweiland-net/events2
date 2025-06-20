<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use JWeiland\Events2\Service\Record\TimeRecordService;
use JWeiland\Events2\Service\Result\DateTimeResult;
use JWeiland\Events2\Service\Result\DayGeneratorResult;
use JWeiland\Events2\Service\Result\TimeResult;
use JWeiland\Events2\Utility\DateTimeUtility;

readonly class TimeService
{
    private const TYPE_EXCEPTION_TIME = 'exception_time';
    private const TYPE_DIFFERENT_TIMES = 'different_times';
    private const TYPE_EVENT_TIME = 'event_time';
    private const TYPE_MULTIPLE_TIMES = 'multiple_times';

    public function __construct(
        private TimeRecordService $timeRecordService,
        private DayRecordBuilderService $dayRecordBuilderService,
        private DateTimeUtility $dateTimeUtility,
    ) {}

    public function enrichWithTimeAndBuildDayRecords(DayGeneratorResult $dayGeneratorResult): void
    {
        foreach ($dayGeneratorResult->getDateTimeResultStorageSorted() as $dateTimeResult) {
            $this->applyTimeResultsToDateTimeResult($dateTimeResult, $dayGeneratorResult->getEventRecord());
        }

        $this->dayRecordBuilderService->buildDayRecordsFor($dayGeneratorResult);
    }

    protected function applyTimeResultsToDateTimeResult(DateTimeResult $dateTimeResult, array $eventRecord): void
    {
        $allTimeRecords = $this->timeRecordService->getAllByEventRecord($eventRecord, true);

        $matchingTimeRecords = $this->getMatchingTimeRecords($allTimeRecords, $eventRecord, $dateTimeResult);

        foreach ($matchingTimeRecords as $timeRecord) {
            $dateTimeResult->addTimeResult(new TimeResult($timeRecord));
        }
    }

    /**
     * Selects the best matching time records by priority.
     */
    protected function getMatchingTimeRecords(
        array $allTimeRecords,
        array $eventRecord,
        DateTimeResult $dateTimeResult,
    ): array {
        // Priority 1: Exception time
        $timeRecords = $this->filterTimeRecords(
            $allTimeRecords,
            $eventRecord,
            self::TYPE_EXCEPTION_TIME,
            $dateTimeResult,
        );

        // Priority 2: Different weekday times
        if ($timeRecords === []) {
            $timeRecords = $this->filterTimeRecords(
                $allTimeRecords,
                $eventRecord,
                self::TYPE_DIFFERENT_TIMES,
                $dateTimeResult,
            );
        }

        // Priority 3: Merge event_time + multiple_times
        if ($timeRecords === []) {
            $eventTimes = $this->filterTimeRecords(
                $allTimeRecords,
                $eventRecord,
                self::TYPE_EVENT_TIME,
                $dateTimeResult,
            );
            $multipleTimes = $this->filterTimeRecords(
                $allTimeRecords,
                $eventRecord,
                self::TYPE_MULTIPLE_TIMES,
                $dateTimeResult,
            );
            $timeRecords = [...$eventTimes, ...$multipleTimes];
        }

        return $timeRecords;
    }

    /**
     * Filters the given time records by type and date/time logic.
     */
    protected function filterTimeRecords(
        array $allTimeRecords,
        array $eventRecord,
        string $recordType,
        DateTimeResult $dateTimeResult,
    ): array {
        return array_values(array_filter($allTimeRecords, function (array $timeRecord) use (
            $eventRecord,
            $recordType,
            $dateTimeResult
        ): bool {
            if (
                !isset($timeRecord['type'], $timeRecord['hidden'], $timeRecord['event'], $timeRecord['exception'], $timeRecord['weekday'])
                || $timeRecord['hidden']
                || !is_array($timeRecord['event'])
                || !is_array($timeRecord['exception'])
                || $timeRecord['type'] !== $recordType
            ) {
                return false;
            }

            return match ($recordType) {
                self::TYPE_EXCEPTION_TIME => $this->isMatchingExceptionTime($eventRecord, $timeRecord, $dateTimeResult),
                self::TYPE_DIFFERENT_TIMES => $this->isMatchingWeekday($timeRecord, $dateTimeResult),
                default => true,
            };
        }));
    }

    /**
     * Check if exception time matches the given date.
     */
    protected function isMatchingExceptionTime(
        array $eventRecord,
        array $timeRecord,
        DateTimeResult $dateTimeResult,
    ): bool {
        $exceptionUid = $timeRecord['exception']['uid'] ?? 0;
        $exceptionRecord = $eventRecord['exceptions'][$exceptionUid] ?? [];

        if (
            isset($exceptionRecord['hidden'])
            && (int)$exceptionRecord['hidden'] === 0
            && in_array($exceptionRecord['exception_type'] ?? '', ['Add', 'Time'], true)
        ) {
            $exceptionDate = $this->dateTimeUtility->convert($exceptionRecord['exception_date'] ?? 0);
            return $exceptionDate != null && $exceptionDate == $dateTimeResult->getDate(); // comparing object values
        }

        return false;
    }

    /**
     * Check if weekday matches for different_times.
     */
    protected function isMatchingWeekday(array $timeRecord, DateTimeResult $dateTimeResult): bool
    {
        return strtolower((string)($timeRecord['weekday'] ?? '')) === strtolower($dateTimeResult->getDate()->format('l'));
    }
}
