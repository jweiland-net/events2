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
use JWeiland\Events2\Domain\Model\DateTimeEntry;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\TimeRepository;
use JWeiland\Events2\Utility\DateTimeUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
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

    protected DayRepository $dayRepository;

    protected TimeRepository $timeRepository;

    protected DateTimeUtility $dateTimeUtility;

    protected ?\DateTimeImmutable $firstDateTime = null;

    protected array $firstTimeRecordForCurrentDateTime = [];

    public function __construct(
        DayGeneratorService $dayGenerator,
        DayRepository $dayRepository,
        TimeRepository $timeRepository,
        DateTimeUtility $dateTimeUtility
    ) {
        $this->dayGenerator = $dayGenerator;
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
        // We are in BE context. Do not overlay.
        $eventRecord = $this->getEventRecord($eventUid);
        if ($eventRecord === []) {
            $this->logger->warning('Related days could not be created, because of an empty eventRecord.');
            return $eventRecord;
        }

        if ($eventRecord['uid'] === 0 || $eventRecord['event_type'] === '') {
            $this->logger->info('DayRelationService will not build day records for invalid events.');
            return $eventRecord;
        }

        if ($eventRecord['sys_language_uid'] > 0) {
            $this->logger->info('DayRelationService will not build day records for translated events.');
            return $eventRecord;
        }

        try {
            $this->firstTimeRecordForCurrentDateTime = [];
            $this->dayRepository->removeAllByEventRecord($eventRecord);
            $days = [];
            $dateTimeStorage = $this->dayGenerator->getDateTimeStorageForEvent($eventRecord);
            foreach ($dateTimeStorage as $dateTimeEntry) {
                if ($this->firstDateTime === null) {
                    $this->firstDateTime = $dateTimeEntry->getDate();
                }

                array_push(
                    $days,
                    ...$this->buildDayRecordsForDateTime($eventRecord, $dateTimeEntry)
                );

                // While looping through the DateTime entries the sort_day_time value has to be the same for all
                // durational events. So, do not reset this value in that case.
                if ($eventRecord['event_type'] !== 'duration') {
                    $this->firstTimeRecordForCurrentDateTime = [];
                }
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

    protected function getEventRecord(int $eventUid): array
    {
        $eventRecord = BackendUtility::getLiveVersionOfRecord('tx_events2_domain_model_event', $eventUid);
        if (is_array($eventRecord)) {
            BackendUtility::workspaceOL('tx_events2_domain_model_event', $eventRecord);
        } else {
            // BackendUtility::getRecordWSOL does NOT check against missing record. Do it manually:
            if (BackendUtility::getRecord('tx_events2_domain_model_event', $eventUid) === null) {
                return [];
            }

            // We already have a LIVE record. Do overlay.
            $eventRecord = BackendUtility::getRecordWSOL('tx_events2_domain_model_event', $eventUid);
        }

        if ($eventRecord === null) {
            $this->logger->warning('Event record can not be overlayed into current workspace: ' . $eventUid);
            return [];
        }

        $this->addExceptionsToEventRecord($eventRecord);

        $eventRecord['days'] = [];

        return $eventRecord;
    }

    protected function addExceptionsToEventRecord(array &$eventRecord): void
    {
        if (!isset($eventRecord['uid'])) {
            return;
        }

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_exception');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $statement = $queryBuilder
            ->select('*')
            ->from('tx_events2_domain_model_exception')
            ->where(
                $queryBuilder->expr()->eq(
                    'event',
                    $queryBuilder->createNamedParameter((int)$eventRecord['uid'], \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->execute();

        $exceptionRecords = [];
        while ($exceptionRecord = $statement->fetch(\PDO::FETCH_ASSOC)) {
            BackendUtility::workspaceOL('tx_events2_domain_model_exception', $exceptionRecord);
            if ($exceptionRecord === null) {
                $this->logger->warning(
                    'Exception record can not be overlayed into current workspace: ' . $exceptionRecord['uid']
                );
                continue;
            }

            $exceptionRecords[(int)$exceptionRecord['uid']] = $exceptionRecord;
        }

        $eventRecord['exceptions'] = $exceptionRecords;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }

    public function buildDayRecordsForDateTime(array $eventRecord, DateTimeEntry $dateTimeEntry): array
    {
        // Early return, if no time records were found
        $timeRecords = $this->getTimeRecordsWithHighestPriority($eventRecord, $dateTimeEntry->getDate());
        if ($timeRecords === []) {
            return [
                $this->buildDayRecordForDateTime($dateTimeEntry, $eventRecord, [])
            ];
        }

        $dayRecords = [];
        foreach ($timeRecords as $timeRecord) {
            if ($this->firstTimeRecordForCurrentDateTime === []) {
                $this->firstTimeRecordForCurrentDateTime = $timeRecord;
            }

            $dayRecords[] = $this->buildDayRecordForDateTime($dateTimeEntry, $eventRecord, $timeRecord);
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
            if (!isset(
                $timeRecord['type'],
                $timeRecord['hidden'],
                $timeRecord['event'],
                $timeRecord['exception'],
                $timeRecord['weekday']
            )) {
                continue;
            }

            if ($timeRecord['hidden']) {
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
                        && isset($exceptionRecord['hidden'])
                        && (int)$exceptionRecord['hidden'] === 0
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
        DateTimeEntry $dateTimeEntry,
        array $eventRecord,
        array $timeRecord
    ): array {
        [$hour, $minute] = $this->getHourAndMinuteFromTime($timeRecord);

        return [
            'pid' => (int)$eventRecord['pid'],
            'crdate' => time(),
            'tstamp' => time(),
            'hidden' => $eventRecord['hidden'] ?? 0,
            'fe_group' => $eventRecord['fe_group'] ?? 0,
            't3ver_wsid' => $eventRecord['t3ver_wsid'] ?? 0,
            'day' => (int)$dateTimeEntry->getDate()->format('U'),
            'day_time' => (int)$this->getDayTime($dateTimeEntry->getDate(), $hour, $minute)->format('U'),
            'sort_day_time' => (int)$this->getSortDayTime($dateTimeEntry->getDate(), $hour, $minute, $eventRecord)->format('U'),
            'same_day_time' => (int)$this->getSameDayTime($dateTimeEntry->getDate(), $hour, $minute, $eventRecord)->format('U'),
            'is_removed_date' => (int)$dateTimeEntry->isRemovedDate(),
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
    ): \DateTimeImmutable {
        if ($eventRecord['event_type'] === 'duration') {
            [$hour, $minute] = $this->getHourAndMinuteFromTime($this->firstTimeRecordForCurrentDateTime);

            return $this->getDayTime($this->firstDateTime, $hour, $minute);
        }

        return $this->getDayTime($day, $hour, $minute);
    }

    /**
     * Get timestamp which is the same for all time-records of same day.
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
            [$hour, $minute] = $this->getHourAndMinuteFromTime($this->firstTimeRecordForCurrentDateTime);
        }

        // In case of duration the date is the same for all days.
        return $this->getSortDayTime($day, $hour, $minute, $eventRecord);
    }
}
