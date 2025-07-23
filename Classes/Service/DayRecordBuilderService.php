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
        $dayTime = $baseDate->modify(sprintf(
            '+%d hour +%d minute',
            $timeResult->getHour(),
            $timeResult->getMinute(),
        ));

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
     *
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
            $timeResult = $dateTimeResult->getFirstTimeResult() ?? $timeResult;
        }

        return $dateTimeResult->getDate()->modify(sprintf(
            '+%d hour +%d minute',
            $timeResult->getHour(),
            $timeResult->getMinute(),
        ));
    }

    /**
     * Get a timestamp which is the same for all time-records of the same day.
     * This column is only needed if settings.mergeEventsAtSameTime is set.
     * It helps to GROUP BY these records in an SQL statement.
     *
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
