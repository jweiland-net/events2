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
use JWeiland\Events2\Helper\PathSegmentHelper;
use JWeiland\Events2\Service\CategoryService;
use JWeiland\Events2\Service\LocationService;
use JWeiland\Events2\Service\OrganizerService;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
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
        protected readonly PathSegmentHelper $pathSegmentHelper,
        protected readonly CategoryService $categoryService,
        protected readonly LocationService $locationService,
        protected readonly OrganizerService $organizerService,
        protected readonly ResourceFactory $resourceFactory,
    ) {}

    public function import(ImportConfiguration $importConfiguration): bool
    {
        // Chunk event data for faster DataHandler processing
        foreach (array_chunk($importConfiguration->getPayload(), 10) as $eventsImportDataChunk) {
            try {
                $dataMap = [];
                foreach ($eventsImportDataChunk as $eventImportData) {
                    $this->updateDataMapForEventImport(
                        $eventImportData,
                        $importConfiguration,
                        $dataMap,
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

                // Can only be done after storing the records, as we need the UID
                $this->updateSlugs($dataHandler);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                return false;
            }
        }

        $this->logger->info('We have processed ' . count($importConfiguration->getPayload()) . ' events');

        return true;
    }

    protected function updateDataMapForEventImport(
        array $eventImportData,
        ImportConfiguration $configuration,
        array &$dataMap,
    ): void {
        $eventRecord = $this->getEventRecordByImportId($eventImportData['uid']);

        // Early return, if record was already imported.
        // We don't try to update the record, as we had to add an import_id column to each events2 table.
        // Possible, yes, but too much work right now.
        if (is_array($eventRecord)) {
            return;
        }

        $eventUid = $this->getUniqueIdForNewRecords();

        $dataMap['tx_events2_domain_model_event'][$eventUid] = [
            'import_id' => (int)$eventImportData['uid'],
            'pid' => $configuration->getStoragePid(),
            'crdate' => time(),
            'tstamp' => time(),
            'starttime' => $this->migrateDateToTimestamp($eventImportData['starttime']),
            'endtime' => $this->migrateDateToTimestamp($eventImportData['endtime']),
            'hidden' => $eventImportData['hidden'] ? 1 : 0,
            'sys_language_uid' => $eventImportData['sys_language_uid'] ?? 0,
            'l10n_parent' => $eventImportData['l10n_parent'] ?? 0,
            'event_type' => $eventImportData['event_type'],
            'top_of_list' => $eventImportData['top_of_list'] ? 1 : 0,
            'title' => $eventImportData['title'],
            'event_begin' => $this->migrateDateToTimestamp($eventImportData['event_begin']),
            'event_time' => $this->migrateTimeRecordToDataMap($eventImportData['event_time'], $configuration->getStoragePid(), $dataMap),
            'event_end' => $this->migrateDateToTimestamp($eventImportData['event_end']),
            'same_day' => $eventImportData['same_day'] ? 1 : 0,
            'multiple_times' => $this->migrateTimeRecordsToDataMap($eventImportData['multiple_times'], $configuration->getStoragePid(), $dataMap),
            'xth' => $eventImportData['xth'],
            'weekday' => $eventImportData['weekday'],
            'different_times' => $this->migrateTimeRecordsToDataMap($eventImportData['different_times'], $configuration->getStoragePid(), $dataMap),
            'each_weeks' => $eventImportData['each_weeks'],
            'each_months' => $eventImportData['each_months'],
            'recurring_end' => $this->migrateDateToTimestamp($eventImportData['recurring_end']),
            'exceptions' => $this->migrateExceptionsToDataMap($eventImportData['exceptions'], $configuration->getStoragePid(), $dataMap),
            'teaser' => $eventImportData['teaser'],
            'detail_information' => $eventImportData['detail_information'],
            'free_entry' => $eventImportData['free_entry'] ? 1 : 0,
            'ticket_link' => $this->migrateLinkRecordToDataMap($eventImportData['ticket_link'], $configuration->getStoragePid(), $dataMap),
            'categories' => $this->migrateCategoryRecordsToDataMap($eventImportData['categories'], $configuration->getStoragePid(), $dataMap),
            'location' => $this->migrateLocationRecordToDataMap($eventImportData['location'], $configuration->getStoragePid(), $dataMap),
            'organizers' => $this->migrateOrganizerRecordsToDataMap($eventImportData['organizers'], $configuration->getStoragePid(), $dataMap),
            'images' => $this->migrateImageRecordsToDataMap($eventImportData['images'], $eventUid, $configuration, $dataMap),
            'video_link' => $this->migrateLinkRecordToDataMap($eventImportData['video_link'], $configuration->getStoragePid(), $dataMap),
            'download_links' => $this->migrateLinkRecordsToDataMap($eventImportData['download_links'], $configuration->getStoragePid(), $dataMap),
        ];
    }

    protected function migrateDateToTimestamp(?string $date): int
    {
        if ($date === null) {
            return 0;
        }

        if ($date === '') {
            return 0;
        }

        try {
            $date = new \DateTime($date);
        } catch (\DateMalformedStringException $e) {
            return 0;
        }

        return $date->getTimestamp();
    }

    protected function migrateTimeRecordsToDataMap(array $importTimeRecords, int $storagePid, array &$dataMap): string
    {
        if ($importTimeRecords === []) {
            return '0';
        }

        $timeUidCollection = [];
        foreach ($importTimeRecords as $timeRecord) {
            $timeUidCollection[] = $this->migrateTimeRecordToDataMap($timeRecord, $storagePid, $dataMap);
        }

        return implode(',', $timeUidCollection);
    }

    protected function migrateTimeRecordToDataMap(array $importTimeRecord, int $storagePid, array &$dataMap): string
    {
        if ($importTimeRecord === []) {
            return '0';
        }

        $timeUid = $this->getUniqueIdForNewRecords();

        $dataMap['tx_events2_domain_model_time'][$timeUid] = [
            'pid' => $storagePid,
            'crdate' => time(),
            'tstamp' => time(),
            'starttime' => $this->migrateDateToTimestamp($importTimeRecord['starttime']),
            'endtime' => $this->migrateDateToTimestamp($importTimeRecord['endtime']),
            'hidden' => $importTimeRecord['hidden'] ? 1 : 0,
            'weekday' => $importTimeRecord['weekday'],
            'time_begin' => $importTimeRecord['time_begin'],
            'time_entry' => $importTimeRecord['time_entry'],
            'duration' => $importTimeRecord['duration'],
            'time_end' => $importTimeRecord['time_end'],
        ];

        return $timeUid;
    }

    protected function migrateLinkRecordsToDataMap(array $importLinkRecords, int $storagePid, array &$dataMap): string
    {
        if ($importLinkRecords === []) {
            return '0';
        }

        $linkUidCollection = [];
        foreach ($importLinkRecords as $linkRecord) {
            $linkUidCollection[] = $this->migrateLinkRecordToDataMap($linkRecord, $storagePid, $dataMap);
        }

        return implode(',', $linkUidCollection);
    }

    protected function migrateLinkRecordToDataMap(array $importLinkRecord, int $storagePid, array &$dataMap): string
    {
        if ($importLinkRecord === []) {
            return '';
        }

        $linkUid = $this->getUniqueIdForNewRecords();

        $dataMap['tx_events2_domain_model_link'][$linkUid] = [
            'pid' => $storagePid,
            'crdate' => time(),
            'tstamp' => time(),
            'starttime' => $this->migrateDateToTimestamp($importLinkRecord['starttime']),
            'endtime' => $this->migrateDateToTimestamp($importLinkRecord['endtime']),
            'hidden' => $importLinkRecord['hidden'] ? 1 : 0,
            'title' => $importLinkRecord['title'],
            'link' => $importLinkRecord['link'],
        ];

        return $linkUid;
    }

    protected function migrateExceptionsToDataMap(array $importExceptionRecords, int $storagePid, array &$dataMap): string
    {
        if ($importExceptionRecords === []) {
            return '0';
        }

        $exceptionUidCollection = [];
        foreach ($importExceptionRecords as $importExceptionRecord) {
            $exceptionUid = $this->getUniqueIdForNewRecords();

            $dataMap['tx_events2_domain_model_exception'][$exceptionUid] = [
                'pid' => $storagePid,
                'crdate' => time(),
                'tstamp' => time(),
                'starttime' => $this->migrateDateToTimestamp($importExceptionRecord['starttime']),
                'endtime' => $this->migrateDateToTimestamp($importExceptionRecord['endtime']),
                'hidden' => $importExceptionRecord['hidden'] ? 1 : 0,
                'exception_type' => $importExceptionRecord['exception_type'],
                'exception_date' => $this->migrateDateToTimestamp($importExceptionRecord['exception_date']),
                'exception_time' => $this->migrateTimeRecordToDataMap($importExceptionRecord['exception_time'], $storagePid, $dataMap),
                'exception_details' => $importExceptionRecord['exception_details'],
                'show_anyway' => $importExceptionRecord['show_anyway'] ? 1 : 0,
                'mark_as' => $importExceptionRecord['mark_as'],
            ];

            $exceptionUidCollection[] = $exceptionUid;
        }

        return implode(',', $exceptionUidCollection);
    }

    protected function migrateCategoryRecordsToDataMap(array $importCategoryRecords, int $storagePid, array &$dataMap): string
    {
        if ($importCategoryRecords === []) {
            return '0';
        }

        $categoryUidCollection = [];
        foreach ($importCategoryRecords ?? [] as $importCategoryRecord) {
            foreach ($dataMap['sys_category'] ?? [] as $uid => $categoryRecord) {
                if ($categoryRecord['title'] === $importCategoryRecord['title']) {
                    $categoryUidCollection[] = $uid;
                    continue 2;
                }
            }

            $categoryRecord = $this->categoryService->getCategoryRecordByTitle($importCategoryRecord['title'] ?? '');

            if ($categoryRecord === null) {
                $categoryUid = $this->getUniqueIdForNewRecords();

                $dataMap['sys_category'][$categoryUid] = [
                    'pid' => $storagePid,
                    'crdate' => time(),
                    'tstamp' => time(),
                    'title' => $importCategoryRecord['title'],
                    'parent' => 0,
                ];
            } else {
                $categoryUid = (string)$importCategoryRecord['uid'];
            }

            $categoryUidCollection[] = $categoryUid;
        }

        return implode(',', $categoryUidCollection);
    }

    protected function migrateLocationRecordToDataMap(array $importLocationRecord, int $storagePid, array &$dataMap): string
    {
        if ($importLocationRecord === []) {
            return '';
        }

        // Early return, if location was already registered in DataMap
        foreach ($dataMap['tx_events2_domain_model_location'] ?? [] as $uid => $locationRecord) {
            if ($locationRecord['location'] === $importLocationRecord['location']) {
                return $uid;
            }
        }

        $locationRecord = $this->locationService->getLocationRecordByTitle($importLocationRecord['location'] ?? '');

        if ($locationRecord === null) {
            $locationUid = $this->getUniqueIdForNewRecords();

            $dataMap['tx_events2_domain_model_location'][$locationUid] = [
                'pid' => $storagePid,
                'crdate' => time(),
                'tstamp' => time(),
                'starttime' => $this->migrateDateToTimestamp($importLocationRecord['starttime']),
                'endtime' => $this->migrateDateToTimestamp($importLocationRecord['endtime']),
                'hidden' => $importLocationRecord['hidden'] ? 1 : 0,
                'location' => $importLocationRecord['location'],
            ];
        } else {
            $locationUid = $locationRecord['uid'];
        }

        return (string)$locationUid;
    }

    protected function migrateOrganizerRecordsToDataMap(array $importOrganizerRecords, int $storagePid, array &$dataMap): string
    {
        if ($importOrganizerRecords === []) {
            return '';
        }

        $organizerUidCollection = [];
        foreach ($importOrganizerRecords as $importOrganizerRecord) {
            foreach ($dataMap['tx_events2_domain_model_organizer'] ?? [] as $uid => $organizerRecord) {
                if ($organizerRecord['organizer'] === $importOrganizerRecord['organizer']) {
                    $organizerUidCollection[] = $uid;
                    continue 2;
                }
            }

            $organizerRecord = $this->organizerService->getOrganizerRecordByTitle($importOrganizerRecord['organizer'] ?? '');

            if ($organizerRecord === null) {
                $organizerUid = $this->getUniqueIdForNewRecords();

                $dataMap['tx_events2_domain_model_organizer'][$organizerUid] = [
                    'pid' => $storagePid,
                    'crdate' => time(),
                    'tstamp' => time(),
                    'starttime' => $this->migrateDateToTimestamp($importOrganizerRecord['starttime']),
                    'endtime' => $this->migrateDateToTimestamp($importOrganizerRecord['endtime']),
                    'hidden' => $importOrganizerRecord['hidden'] ? 1 : 0,
                    'organizer' => $importOrganizerRecord['organizer'],
                    'link' => $this->migrateLinkRecordToDataMap($importOrganizerRecord['link'], $storagePid, $dataMap),
                ];
            } else {
                $organizerUid = (string)$organizerRecord['uid'];
            }

            $organizerUidCollection[] = $organizerUid;
        }

        return implode(',', $organizerUidCollection);
    }

    protected function migrateImageRecordsToDataMap(
        array $importImageRecords,
        string $eventUid,
        ImportConfiguration $configuration,
        array &$dataMap,
    ): string {
        if ($importImageRecords === []) {
            return '0';
        }

        $sysFileReferenceUidCollection = [];
        foreach ($importImageRecords as $imageRecord) {
            if (!GeneralUtility::isValidUrl($imageRecord['url'])) {
                continue;
            }

            $resourceStorage = $this->resourceFactory->getStorageObjectFromCombinedIdentifier(
                $configuration->getStorageFolder(),
            );
            $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($configuration->getStorageFolder());
            $filename = $resourceStorage->sanitizeFileName(pathinfo($imageRecord['url'], PATHINFO_BASENAME));

            if ($folder->hasFile($filename)) {
                $fileObject = $folder->getFile($filename);
            } else {
                try {
                    $tempImagePath = GeneralUtility::tempnam('events2_import_');
                    file_put_contents($tempImagePath, GeneralUtility::getUrl($imageRecord['url']));
                    $fileObject = $resourceStorage->addFile(
                        $tempImagePath,
                        $folder,
                        $filename,
                    );
                } catch (ExistingTargetFileNameException $e) {
                    continue;
                }
            }

            $sysFileReferenceUid = $this->getUniqueIdForNewRecords();

            $dataMap['sys_file_reference'][$sysFileReferenceUid] = [
                'uid_local' => $fileObject->getUid(),
                'tablenames' => 'tx_events2_domain_model_event',
                'uid_foreign' => $eventUid,
                'fieldname' => 'images',
                'pid' => $configuration->getStoragePid(),
            ];

            $sysFileReferenceUidCollection[] = $sysFileReferenceUid;
        }

        return implode(',', $sysFileReferenceUidCollection);
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
                        $queryBuilder->createNamedParameter($importId, Connection::PARAM_INT),
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                    ),
                    $queryBuilder->expr()->eq(
                        'l10n_parent',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                    ),
                )
                ->executeQuery()
                ->fetchAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            $eventRecord = null;
        }

        return is_array($eventRecord) ? $eventRecord : null;
    }

    /**
     * If you found another solution how to retrieve the just stored records from DataHandler: Give me a note
     */
    protected function updateSlugs(DataHandler $dataHandler): void
    {
        // Collect all NEW[hash] values valid for event table
        $eventNEWidValues = array_filter($dataHandler->substNEWwithIDs_table, static function (string $table): bool {
            return $table === 'tx_events2_domain_model_event';
        });

        // Get real event INSERT ID for NEW[hash] values
        $eventUidValues = array_filter($dataHandler->substNEWwithIDs, static function (string $newId) use ($eventNEWidValues): bool {
            return array_key_exists($newId, $eventNEWidValues);
        }, ARRAY_FILTER_USE_KEY);

        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_event');
        foreach ($eventUidValues as $eventUid) {
            $eventRecord = BackendUtility::getRecord('tx_events2_domain_model_event', (int)$eventUid);
            $uniqueSlug = $this->pathSegmentHelper->generatePathSegment($eventRecord);
            $connection->update(
                'tx_events2_domain_model_event',
                [
                    'path_segment' => $uniqueSlug,
                ],
                [
                    'uid' => (int)$eventUid,
                ],
            );
        }
    }

    protected function getQueryBuilderForTable(string $table): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

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
