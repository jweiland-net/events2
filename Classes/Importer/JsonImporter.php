<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Importer;

use JWeiland\Events2\Configuration\ImportConfiguration;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reactions\Authentication\ReactionUserAuthentication;

/**
 * Import event records via JSON.
 * Use this importer, if you have data which represents events2 database structure. If you need something simpler try
 * XmlImporter which has a much simpler structure.
 */
class JsonImporter
{
    public function __construct(
        protected readonly LoggerInterface $logger,
    ) {
    }

    public function import(ImportConfiguration $importConfiguration): bool
    {
        // Chunk event data for faster DataHandler processing
        foreach (array_chunk($importConfiguration->getPayload(), 10) as $eventsImportDataChunk) {
            try {
                $dataMap = [];
                foreach ($eventsImportDataChunk as $eventImportData) {
                    $this->updateDataMapForEventImport(
                        $eventImportData,
                        $importConfiguration->getStoragePid(),
                        $dataMap
                    );
                }

                $dataHandler = $this->getDataHandler();
                $dataHandler->start($dataMap, [], $this->getBackendUser());
                $dataHandler->process_datamap();
                $dataHandler->process_cmdmap();

                if ($dataHandler->errorLog !== []) {
                    foreach ($dataHandler->errorLog as $errorLog) {
                        $this->logger->error('DataHandler Log: ' . $errorLog);
                    }
                    return false;
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                return false;
            }
        }

        $this->logger->info('We have processed ' . count($importConfiguration->getPayload()) . ' events');

        return true;
    }

    protected function updateDataMapForEventImport(array $eventImportData, int $storagePid, array &$dataMap): void
    {
        $eventRecord = $this->getEventRecordByImportId($eventImportData['uid']);

        // Early return, if record was already imported.
        // We don't try to update the record, as we had to add an import_id column to each events2 table.
        // Possible, yes, but too much work right now.
        if (is_array($eventRecord)) {
            return;
        }

        $eventUid = $this->getUniqueIdForNewRecords();

        $dataMap['tx_events2_domain_model_event'][$eventUid] = [
            'pid' => $storagePid,
            'crdate' => time(),
            'tstamp' => time(),
            'starttime' => $this->migrateDateToTimestamp($eventImportData['starttime']),
            'endtime' => $this->migrateDateToTimestamp($eventImportData['endtime']),
            'hidden' => $eventImportData['hidden'] ? 1 : 0,
            'event_type' => $eventImportData['event_type'],
            'top_of_list' => $eventImportData['top_of_list'] ? 1 : 0,
            'title' => $eventImportData['title'],
            'path_segment' => $eventImportData['path_segment'],
            'event_begin' => $this->migrateDateToTimestamp($eventImportData['event_begin']),
            'event_time' => $this->migrateTimeRecordToDataMap($eventImportData['event_time'], $storagePid, $dataMap),
            'event_end' => $this->migrateDateToTimestamp($eventImportData['event_end']),
            'same_day' => $eventImportData['same_day'] ? 1 : 0,
            'multiple_times' => $this->migrateTimeRecordsToDataMap($eventImportData['multiple_times'], $storagePid, $dataMap),
            'xth' => $eventImportData['xth'],
            'weekday' => $eventImportData['weekday'],
            'different_times' => $this->migrateTimeRecordsToDataMap($eventImportData['different_times'], $storagePid, $dataMap),
            'each_weeks' => $eventImportData['each_weeks'],
            'each_months' => $eventImportData['each_months'],
            'recurring_end' => $this->migrateDateToTimestamp($eventImportData['recurring_end']),
            'exceptions' => $this->migrateExceptionsToDataMap($eventImportData['exceptions'], $storagePid, $dataMap),
            'teaser' => $eventImportData['teaser'],
            'detail_information' => $eventImportData['detail_information'],
            'free_entry' => $eventImportData['free_entry'] ? 1 : 0,
            'ticket_link' => $this->migrateLinkRecordToDataMap($eventImportData['ticket_link'], $storagePid, $dataMap),
            // 'categories' => $eventImportData['categories'],
            'location' => $this->migrateLocationRecordToDataMap($eventImportData['location'], $storagePid, $dataMap),
            'organizers' => $this->migrateOrganizerRecordsToDataMap($eventImportData['organizers'], $storagePid, $dataMap),
            // 'images' => $eventImportData['images'],
            'video_link' => $this->migrateLinkRecordToDataMap($eventImportData['video_link'], $storagePid, $dataMap),
            'download_links' => $this->migrateLinkRecordsToDataMap($eventImportData['download_links'], $storagePid, $dataMap),
        ];
    }

    protected function migrateDateToTimestamp(?string $date): int
    {
        if ($date === null) {
            return 0;
        }

        try {
            $date = new \DateTime($date);
        } catch (\DateMalformedStringException $e) {
            return 0;
        }

        return $date->getTimestamp();
    }

    protected function migrateTimeRecordsToDataMap(array $timeRecords, $storagePid, array &$dataMap): string
    {
        if ($timeRecords === []) {
            return '0';
        }

        $timeUidCollection = [];
        foreach ($timeRecords as $timeRecord) {
            $timeUidCollection[] = $this->migrateTimeRecordToDataMap($timeRecord, $storagePid, $dataMap);
        }

        return implode(',', $timeUidCollection);
    }

    protected function migrateTimeRecordToDataMap(array $timeRecord, int $storagePid, array &$dataMap): string
    {
        if ($timeRecord === []) {
            return '0';
        }

        $timeUid = $this->getUniqueIdForNewRecords();

        $dataMap['tx_events2_domain_model_time'][$timeUid] = [
            'pid' => $storagePid,
            'crdate' => time(),
            'tstamp' => time(),
            'starttime' => $this->migrateDateToTimestamp($timeRecord['starttime']),
            'endtime' => $this->migrateDateToTimestamp($timeRecord['endtime']),
            'hidden' => $timeRecord['hidden'] ? 1 : 0,
            'weekday' => $timeRecord['weekday'],
            'time_begin' => $timeRecord['time_begin'],
            'time_entry' => $timeRecord['time_entry'],
            'duration' => $timeRecord['duration'],
            'time_end' => $timeRecord['time_end'],
        ];

        return $timeUid;
    }

    protected function migrateLinkRecordsToDataMap(array $linkRecords, int $storagePid, array &$dataMap): string
    {
        if ($linkRecords === []) {
            return '0';
        }

        $linkUidCollection = [];
        foreach ($linkRecords as $linkRecord) {
            $linkUidCollection[] = $this->migrateLinkRecordToDataMap($linkRecord, $storagePid, $dataMap);
        }

        return implode(',', $linkUidCollection);
    }

    protected function migrateLinkRecordToDataMap(array $linkRecord, int $storagePid, array &$dataMap): string
    {
        if ($linkRecord === []) {
            return '';
        }

        $linkUid = $this->getUniqueIdForNewRecords();

        $dataMap['tx_events2_domain_model_link'][$linkUid] = [
            'pid' => $storagePid,
            'crdate' => time(),
            'tstamp' => time(),
            'starttime' => $this->migrateDateToTimestamp($linkRecord['starttime']),
            'endtime' => $this->migrateDateToTimestamp($linkRecord['endtime']),
            'hidden' => $linkRecord['hidden'] ? 1 : 0,
            'title' => $linkRecord['title'],
            'link' => $linkRecord['link'],
        ];

        return $linkUid;
    }

    protected function migrateExceptionsToDataMap(array $exceptions, $storagePid, array &$dataMap): string
    {
        if ($exceptions === []) {
            return '0';
        }

        $exceptionUidCollection = [];
        foreach ($exceptions as $exception) {
            $exceptionUid = $this->getUniqueIdForNewRecords();

            $dataMap['tx_events2_domain_model_exception'][$exceptionUid] = [
                'pid' => $storagePid,
                'crdate' => time(),
                'tstamp' => time(),
                'starttime' => $this->migrateDateToTimestamp($exception['starttime']),
                'endtime' => $this->migrateDateToTimestamp($exception['endtime']),
                'hidden' => $exception['hidden'] ? 1 : 0,
                'exception_type' => $exception['exception_type'],
                'exception_date' => $this->migrateDateToTimestamp($exception['exception_date']),
                'exception_time' => $this->migrateTimeRecordToDataMap($exception['exception_time'], $storagePid, $dataMap),
                'exception_details' => $exception['exception_details'],
                'show_anyway' => $exception['show_anyway'] ? 1 : 0,
                'mark_as' =>$exception['mark_as'],
            ];

            $exceptionUidCollection[] = $exceptionUid;
        }

        return implode(',', $exceptionUidCollection);
    }

    protected function migrateLocationRecordToDataMap(array $locationRecord, $storagePid, array &$dataMap): string
    {
        if ($locationRecord === []) {
            return '';
        }

        $locationUid = $this->getUniqueIdForNewRecords();

        $dataMap['tx_events2_domain_model_location'][$locationUid] = [
            'pid' => $storagePid,
            'crdate' => time(),
            'tstamp' => time(),
            'starttime' => $this->migrateDateToTimestamp($locationRecord['starttime']),
            'endtime' => $this->migrateDateToTimestamp($locationRecord['endtime']),
            'hidden' => $locationRecord['hidden'] ? 1 : 0,
            'location' => $locationRecord['location'],
        ];

        return $locationUid;
    }

    protected function migrateOrganizerRecordsToDataMap(array $organizerRecords, $storagePid, array &$dataMap): string
    {
        if ($organizerRecords === []) {
            return '0';
        }

        $organizerUidCollection = [];
        foreach ($organizerRecords as $organizerRecord) {
            $organizerUid = $this->getUniqueIdForNewRecords();

            $dataMap['tx_events2_domain_model_organizer'][$organizerUid] = [
                'pid' => $storagePid,
                'crdate' => time(),
                'tstamp' => time(),
                'starttime' => $this->migrateDateToTimestamp($organizerRecord['starttime']),
                'endtime' => $this->migrateDateToTimestamp($organizerRecord['endtime']),
                'hidden' => $organizerRecord['hidden'] ? 1 : 0,
                'organizer' => $organizerRecord['organizer'],
                'link' => $this->migrateLinkRecordToDataMap($organizerRecord['link'], $storagePid, $dataMap),
            ];

            $organizerUidCollection[] = $organizerUid;
        }

        return implode(',', $organizerUidCollection);

    }

    /**
     * Get event record by import_id.
     * Will only return event records of default language
     */
    protected function getEventRecordByImportId(int $importId): ?array
    {
        $queryBuilder = $this->getQueryBuilderForTable('tx_events2_domain_model_event');
        try {
            $eventRecord = $queryBuilder
                ->select('*')
                ->from('tx_events2_domain_model_event')
                ->where(
                    $queryBuilder->expr()->eq(
                        'import_id',
                        $queryBuilder->createNamedParameter($importId, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'l10n_parent',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    )
                )
                ->executeQuery()
                ->fetchAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            $eventRecord = null;
        }

        return is_array($eventRecord) ? $eventRecord : null;
    }

    protected function getQueryBuilderForTable(string $table, bool $allowHidden = false): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        if (!$allowHidden) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        }

        return $queryBuilder;
    }

    private function getUniqueIdForNewRecords(): string
    {
        return str_replace('.', '', uniqid('NEW', true));
    }

    protected function getDataHandler(): DataHandler
    {
        return GeneralUtility::makeInstance(DataHandler::class);
    }

    private function getBackendUser(): ReactionUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
