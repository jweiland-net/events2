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
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Domain\Repository\TimeRepository;
use JWeiland\Events2\Utility\DateTimeUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * While saving an event in backend, this class generates all the day records
 * and sets them in relation to the event record.
 */
class DayRelationService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected array $eventRecord = [];

    protected ExtConf $extConf;

    protected DayGeneratorService $dayGenerator;

    protected EventRepository $eventRepository;

    protected DayRepository $dayRepository;

    protected TimeRepository $timeRepository;

    protected DateTimeUtility $dateTimeUtility;

    protected ?\DateTimeImmutable $firstDateTime = null;

    protected ?array $firstTimeRecord = null;

    /**
     * Must be called by ObjectManager, because of EventRepository which has inject methods
     */
    public function __construct(
        DayGeneratorService $dayGenerator,
        EventRepository $eventRepository,
        DayRepository $dayRepository,
        TimeRepository $timeRepository,
        DateTimeUtility $dateTimeUtility
    ) {
        $this->dayGenerator = $dayGenerator;
        $this->eventRepository = $eventRepository;
        $this->dayRepository = $dayRepository;
        $this->timeRepository = $timeRepository;
        $this->dateTimeUtility = $dateTimeUtility;
    }

    /**
     * Delete all related day records of given event and
     * start re-creating the day records.
     */
    public function createDayRelations(int $eventUid): array
    {
        $eventRecord = $this->eventRepository->getRecord($eventUid, ['*'], true);
        if ($eventRecord === []) {
            $this->logger->warning('Related days could not be created, because of an empty eventRecord.');

            return $eventRecord;
        }

        try {
            $this->dayRepository->removeAllByEventRecord($eventRecord);
            $days = [];
            $dateTimeStorage = $this->dayGenerator->getDateTimeStorageForEvent($eventRecord);
            foreach ($dateTimeStorage as $dateTime) {
                if ($this->firstDateTime === null) {
                    $this->firstDateTime = $dateTime;
                }

                array_push(
                    $days,
                    ...$this->buildDayRecordsForDateTime($eventRecord, $dateTime)
                );
            }

            $this->firstDateTime = null;
            $this->dayRepository->createAll($days);
            $eventRecord['days'] = $days;
        } catch (\Exception $exception) {
            $this->logger->error(sprintf(
                'Error while building day records for event: %d. File: %s. Line: %d. Error: %s',
                $eventUid,
                $exception->getFile(),
                $exception->getLine(),
                $exception->getMessage()
            ));
        }

        return $eventRecord;
    }

    public function buildDayRecordsForDateTime(array $eventRecord, \DateTimeImmutable $dateTime): array
    {
        $dayRecords = [];

        foreach ($this->getTimeRecordsWithHighestPriority($eventRecord, $dateTime) as $timeRecord) {
            if ($this->firstTimeRecord === null) {
                $this->firstTimeRecord = $timeRecord;
            }

            $dayRecords[] = $this->buildDayRecordForDateTime($dateTime, $eventRecord, $timeRecord);
        }

        // sort_day_time of all duration days have to be the same value, so do not reset this value in that case.
        if ($eventRecord['event_type'] !== 'duration') {
            $this->firstTimeRecord = null;
        }

        return $dayRecords;
    }

    protected function getTimeRecordsWithHighestPriority(array $eventRecord, \DateTimeImmutable $dateTime): array
    {
        $allTimeRecords = $this->timeRepository->getAllByEventRecord($eventRecord, true);
        $timeRecords = $this->filterTimeRecords($allTimeRecords, $eventRecord, 'exception_time', $dateTime);
        if ($timeRecords !== []) {
            return $timeRecords;
        }

        $timeRecords = $this->filterTimeRecords($allTimeRecords, $eventRecord, 'different_times', $dateTime);
        if ($timeRecords !== []) {
            return $timeRecords;
        }

        // Following time types have to be merged
        $filteredTimeRecords = [];
        $timeRecords = $this->filterTimeRecords($allTimeRecords, $eventRecord, 'event_time', $dateTime);
        if ($timeRecords !== []) {
            $filteredTimeRecords = $timeRecords;
        }

        $timeRecords = $this->filterTimeRecords($allTimeRecords, $eventRecord, 'multiple_times', $dateTime);
        if ($timeRecords !== []) {
            array_push($filteredTimeRecords, ...$timeRecords);
        }


        return $filteredTimeRecords;
    }

    protected function filterTimeRecords(
        array $allTimeRecords,
        array $eventRecord,
        string $recordType,
        \DateTimeImmutable $dateTime
    ): array {
        $filteredTimeRecords = [];
        foreach ($allTimeRecords as $timeRecord) {
            if (!isset($timeRecord['type'], $timeRecord['event'], $timeRecord['exception'], $timeRecord['weekday'])) {
                continue;
            }

            if (!is_array($timeRecord['event']) || !is_array($timeRecord['exception'])) {
                continue;
            }

            if ($timeRecord['type'] !== $recordType) {
                continue;
            }

            switch ($recordType) {
                case 'exception_time':
                    // Record must match date of exception record
                    if (
                        ($exceptionRecord = $eventRecord['exceptions'][$timeRecord['exception']['uid'] ?? 0] ?? [])
                        && in_array($exceptionRecord['exception_type'] ?? '', ['Add', 'Time'])
                        && ($exceptionDate = $this->dateTimeUtility->convert($exceptionRecord['exception_date'] ?? 0))
                        && $exceptionDate == $dateTime // we compare objects here so no === possible
                    ) {
                        $filteredTimeRecords[] = $timeRecord;
                    }
                    break;
                case 'different_times':
                    // Record must match day of week to be added
                    if (strtolower($timeRecord['weekday']) === strtolower($dateTime->format('l'))) {
                        $filteredTimeRecords[] = $timeRecord;
                    }
                    break;
                default:
                    $filteredTimeRecords[] = $timeRecord;
            }
        }

        return $filteredTimeRecords;
    }

    protected function buildDayRecordForDateTime(
        \DateTimeImmutable $dateTime,
        array $eventRecord,
        array $timeRecord
    ): array {
        [$hour, $minute] = $this->getHourAndMinuteFromTime($timeRecord);

        return [
            'pid' => (int)$eventRecord['pid'],
            'crdate' => time(),
            'tstamp' => time(),
            'cruser_id' => $GLOBALS['BE_USER']->user['uid'] ?? 0,
            'hidden' => $eventRecord['hidden'] ?? 0,
            'day' => (int)$dateTime->format('U'),
            'day_time' => (int)$this->getDayTime($dateTime, $hour, $minute)->format('U'),
            'sort_day_time' => (int)$this->getSortDayTime($dateTime, $hour, $minute, $eventRecord)->format('U'),
            'same_day_time' => (int)$this->getSameDayTime($dateTime, $hour, $minute, $eventRecord)->format('U'),
            'event' => $eventRecord['uid']
        ];
    }

    /**
     * Analyze for valid time value like "21:40" and return exploded time parts: hour (21) and minute (40).
     * It does not prepend "0" to a value. Time for "08:04" will be returned with hour (8) and minute (4).
     */
    protected function getHourAndMinuteFromTime(array $timeRecord): array
    {
        if (!isset($timeRecord['time_begin'])) {
            return [0, 0];
        }

        if (!preg_match('@^([0-1]\d|2[0-3]):[0-5]\d$@', $timeRecord['time_begin'])) {
            return [0, 0];
        }

        return GeneralUtility::intExplode(':', $timeRecord['time_begin']);
    }

    /**
     * Returns a new DateTime with added hour and minute
     *
     * Day: 17.01.2017 00:00:00 + 8h + 30m
     * Day: 18.01.2017 00:00:00 + 10h + 15m
     * Day: 19.01.2017 00:00:00 + 9h + 25m
     * Day: 20.01.2017 00:00:00 + 14h + 45m
     */
    protected function getDayTime(\DateTimeImmutable $day, int $hour, int $minute): \DateTimeImmutable
    {
        return $day->modify(sprintf(
            '+%d hour +%d minute',
            $hour,
            $minute
        ));
    }

    /**
     * Get timestamp which is the same for all event days of type duration
     * Instead of getDayTime this method will return the same timestamp for all days in event
     *
     * Day: 17.01.2017 00:00:00 + 8h + 30m  = 17.01.2017 08:30:00
     * Day: 18.01.2017 00:00:00 + 10h + 15m = 17.01.2017 08:30:00
     * Day: 19.01.2017 00:00:00 + 9h + 25m  = 17.01.2017 08:30:00
     * Day: 20.01.2017 00:00:00 + 14h + 45m = 17.01.2017 08:30:00
     */
    protected function getSortDayTime(
        \DateTimeImmutable $day,
        int $hour,
        int $minute,
        array $eventRecord
    ): \DateTimeImmutable
    {
        if ($eventRecord['event_type'] === 'duration') {
            [$hour, $minute] = $this->getHourAndMinuteFromTime($this->firstTimeRecord);

            return $this->getDayTime($this->firstDateTime, $hour, $minute);
        }

        return $this->getDayTime($day, $hour, $minute);
    }

    /**
     * Get timestamp which is the same for all-time records of same day.
     * This column is only needed if mergeEventsAtSameTime is set.
     * It helps to GROUP BY these records in SQL statement.
     *
     * Day: 17.01.2017 00:00:00 + 8h + 30m  = 17.01.2017 08:30:00
     * Day: 17.01.2017 00:00:00 + 10h + 15m = 17.01.2017 08:30:00
     * Day: 18.01.2017 00:00:00 + 8h + 30m  = 18.01.2017 08:30:00
     * Day: 28.01.2017 00:00:00 + 10h + 15m = 18.01.2017 08:30:00
     */
    protected function getSameDayTime(
        \DateTimeImmutable $day,
        int $hour,
        int $minute,
        array $eventRecord
    ): \DateTimeImmutable {
        if ($eventRecord['event_type'] !== 'duration') {
            [$hour, $minute] = $this->getHourAndMinuteFromTime($this->firstTimeRecord);
        }

        return $this->getSortDayTime($day, $hour, $minute, $eventRecord);
    }
}
