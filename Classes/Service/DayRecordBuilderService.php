<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use JWeiland\Events2\Service\Result\DateTimeResult;
use JWeiland\Events2\Service\Result\DayGeneratorResult;
use JWeiland\Events2\Service\Result\TimeResult;

/**
 * This service is part of the TYPO3 extension "events2" and is responsible for constructing preliminary day records
 * for event days to be persisted in the database table `tx_events2_domain_model_day`. It receives a {@see DayGeneratorResult}
 * from the {@see DayGeneratorService}, iterates over its internal storage of event day objects, and augments each with
 * precise time information based on the attached {@see TimeResult} objects.
 * The resulting day record includes standard information such as storage PID, hidden/visible flag, and frontend user
 * group for access control. In addition, four calculated fields are added for each event day:
 *   - `day`: The event date as a \DateTime object at midnight (00:00:00).
 *   - `day_time`: The event date and the exact calculated time as a \DateTime object (i.e., included hour and minute).
 *   - `sort_day_time`: Especially for "duration" events, all affected event days (and their times) are normalized
 *                     to the timestamp of the very first event day. This ensures that, for such events, participants
 *                     cannot join after the start, and sorting reflects the true event begin.
 *   - `same_day_time`: For events with multiple times on the same day, all corresponding \DateTime values are set to
 *                      the earliest time on that day. This allows aggregation (e.g., SQL GROUP BY) of such overlapping events.
 * This service acts as the bridge between abstract event planning results and the actual data structure required by the database,
 * ensuring that all business logic related to time calculation and event structure is centrally encapsulated for maintainability
 * and extensibility.
 * **Note:** The further processing of these day records—such as localization, workspace/versioning handling, and the actual
 * bulk insertion into the database—is handled separately in {@see DayRecordService::bulkInsertAllDayRecords()}.
 */
readonly class DayRecordBuilderService
{
    public function buildDayRecordsFor(DayGeneratorResult $dayGeneratorResult): void
    {
        foreach ($dayGeneratorResult->getDateTimeResultStorageSorted() as $dateTimeResult) {
            $dayGeneratorResult->addDayRecords(
                $this->buildDayRecordsForDateTimeResult($dateTimeResult, $dayGeneratorResult),
            );
        }
    }

    protected function buildDayRecordsForDateTimeResult(
        DateTimeResult $dateTimeResult,
        DayGeneratorResult $dayGeneratorResult,
    ): array {
        if ($dateTimeResult->getTimeResultStorage()->count() === 0) {
            return [$this->buildDayRecord($dateTimeResult, $dayGeneratorResult, new TimeResult())];
        }

        $dayRecords = [];
        foreach ($dateTimeResult->getTimeResultStorage() as $timeResult) {
            $dayRecords[] = $this->buildDayRecord($dateTimeResult, $dayGeneratorResult, $timeResult);
        }

        return $dayRecords;
    }

    protected function buildDayRecord(
        DateTimeResult $dateTimeResult,
        DayGeneratorResult $dayGeneratorResult,
        TimeResult $timeResult,
    ): array {
        $baseDate = $dateTimeResult->getDate();
        $eventRecord = $dayGeneratorResult->getEventRecord();
        $dayTime = $baseDate->modify(
            sprintf(
                '+%d hour +%d minute',
                $timeResult->getHour(),
                $timeResult->getMinute(),
            ),
        );

        return [
            'pid' => (int)($eventRecord['pid'] ?? 0),
            'hidden' => $eventRecord['hidden'] ?? 0,
            'fe_group' => $eventRecord['fe_group'] ?? 0,
            'day' => (int)$baseDate->format('U'),
            'day_time' => (int)$dayTime->format('U'),
            'sort_day_time' => (int)$this
                ->getSortDayTime($dateTimeResult, $dayGeneratorResult, $timeResult)
                ->format('U'),
            'same_day_time' => (int)$this
                ->getSameDayTime($dateTimeResult, $dayGeneratorResult, $timeResult)
                ->format('U'),
            'is_removed_date' => (int)$dateTimeResult->isRemovedDate(),
        ];
    }

    /**
     * Get timestamp which is the same for all event days of type duration
     * Instead of getDayTime this method will return the same timestamp for all days in the event
     * Day: 17.01.2017 00:00:00 + 8h + 30m  = 17.01.2017 08:30:00
     * Day: 18.01.2017 00:00:00 + 10h + 15m = 17.01.2017 08:30:00
     * Day: 19.01.2017 00:00:00 + 9h + 25m  = 17.01.2017 08:30:00
     * Day: 20.01.2017 00:00:00 + 14h + 45m = 17.01.2017 08:30:00
     */
    protected function getSortDayTime(
        DateTimeResult $dateTimeResult,
        DayGeneratorResult $dayGeneratorResult,
        TimeResult $timeResult,
    ): \DateTimeImmutable {
        if ($dayGeneratorResult->getEventRecord()['event_type'] === 'duration') {
            // For duration events, use the FIRST date and its first time record
            // to ensure all days of the duration event have the same sort_day_time.
            // This allows grouping/merging of duration event days in listings.
            $firstDateTimeResult = $dayGeneratorResult->getFirstDateTimeResult();
            if ($firstDateTimeResult !== null) {
                $timeResult = $firstDateTimeResult->getFirstTimeResult() ?? $timeResult;
                return $firstDateTimeResult->getDate()->modify(
                    sprintf(
                        '+%d hour +%d minute',
                        $timeResult->getHour(),
                        $timeResult->getMinute(),
                    ),
                );
            }
        }

        return $dateTimeResult->getDate()->modify(
            sprintf(
                '+%d hour +%d minute',
                $timeResult->getHour(),
                $timeResult->getMinute(),
            ),
        );
    }

    /**
     * Get a timestamp which is the same for all time-records of the same day.
     * This column is only needed if settings.mergeEventsAtSameTime is set.
     * It helps to GROUP BY these records in an SQL statement.
     * Day: 17.01.2017 00:00:00 + 8h + 30m  = 17.01.2017 08:30:00
     * Day: 17.01.2017 00:00:00 + 10h + 15m = 17.01.2017 08:30:00
     * Day: 18.01.2017 00:00:00 + 8h + 30m  = 18.01.2017 08:30:00
     * Day: 18.01.2017 00:00:00 + 10h + 15m = 18.01.2017 08:30:00
     */
    protected function getSameDayTime(
        DateTimeResult $dateTimeResult,
        DayGeneratorResult $dayGeneratorResult,
        TimeResult $timeResult,
    ): \DateTimeImmutable {
        if ($dayGeneratorResult->getEventRecord()['event_type'] !== 'duration') {
            $timeResult = $dateTimeResult->getFirstTimeResult() ?? $timeResult;
        }

        // In case of duration the date is the same for all days.
        return $this->getSortDayTime($dateTimeResult, $dayGeneratorResult, $timeResult);
    }
}
