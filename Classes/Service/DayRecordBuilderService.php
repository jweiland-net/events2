<?php

declare(strict_types=1);

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
                $this->buildRecordsForDateTimeResult($dateTimeResult, $dayGeneratorResult)
            );
        }
    }

    protected function buildRecordsForDateTimeResult(
        DateTimeResult $dateTimeResult,
        DayGeneratorResult $dayGeneratorResult
    ): array {
        if ($dateTimeResult->getTimeResultStorage()->count() === 0) {
            return [$this->buildDayRecord($dateTimeResult, $dayGeneratorResult, new TimeResult())];
        }

        $records = [];
        foreach ($dateTimeResult->getTimeResultStorage() as $timeResult) {
            $records[] = $this->buildDayRecord($dateTimeResult, $dayGeneratorResult, $timeResult);
        }

        return $records;
    }

    protected function buildDayRecord(
        DateTimeResult $dateTimeResult,
        DayGeneratorResult $dayGeneratorResult,
        TimeResult $timeResult
    ): array {
        $baseDate = $dateTimeResult->getDate();
        $eventRecord = $dayGeneratorResult->getEventRecord();

        $dayTime = $baseDate->modify(sprintf('+%d hour +%d minute', $timeResult->getHour(), $timeResult->getMinute()));

        return [
            'pid' => (int)($eventRecord['pid'] ?? 0),
            'crdate' => time(),
            'tstamp' => time(),
            'hidden' => $eventRecord['hidden'] ?? 0,
            'fe_group' => $eventRecord['fe_group'] ?? 0,
            't3ver_wsid' => $eventRecord['t3ver_wsid'] ?? 0,
            'day' => (int)$baseDate->format('U'),
            'day_time' => (int)$dayTime->format('U'),
            'sort_day_time' => (int)$this->getSortDayTime($dateTimeResult, $dayGeneratorResult, $timeResult)->format(
                'U'
            ),
            'same_day_time' => (int)$this->getSameDayTime($dateTimeResult, $dayGeneratorResult, $timeResult)->format(
                'U'
            ),
            'is_removed_date' => (int)$dateTimeResult->isRemovedDate(),
            'event' => $eventRecord['uid'],
        ];
    }

    protected function getSortDayTime(
        DateTimeResult $dateTimeResult,
        DayGeneratorResult $dayGeneratorResult,
        TimeResult $timeResult
    ): \DateTimeImmutable {
        $eventRecord = $dayGeneratorResult->getEventRecord();
        $firstDate = $dayGeneratorResult->getFirstDateTimeResult();

        if (($eventRecord['event_type'] ?? '') === 'duration' && $firstDate instanceof DateTimeResult) {
            $firstTime = $firstDate->getFirstTimeResult();
            if ($firstTime !== null) {
                return $firstDate->getDate()->modify(
                    sprintf('+%d hour +%d minute', $firstTime->getHour(), $firstTime->getMinute())
                );
            }
        }

        return $dateTimeResult->getDate()->modify(
            sprintf('+%d hour +%d minute', $timeResult->getHour(), $timeResult->getMinute())
        );
    }

    protected function getSameDayTime(
        DateTimeResult $dateTimeResult,
        DayGeneratorResult $dayGeneratorResult,
        TimeResult $timeResult
    ): \DateTimeImmutable {
        return $this->getSortDayTime($dateTimeResult, $dayGeneratorResult, $timeResult);
    }
}
