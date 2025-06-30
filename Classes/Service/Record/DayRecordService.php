<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service\Record;

use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

readonly class DayRecordService
{
    private const TABLE = 'tx_events2_domain_model_day';

    public function __construct(
        private QueryBuilder $queryBuilder,
        private ConnectionPool $connectionPool,
        private PageRepository $pageRepository,
        private LoggerInterface $logger,
    ) {}

    public function getByEventAndTime(int $eventUid, int $timestamp): array
    {
        $queryBuilder = $this->queryBuilder;
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

        try {
            $day = $queryBuilder
                ->select('*')
                ->from(self::TABLE, 'd')
                ->where(
                    $queryBuilder->expr()->eq(
                        'd.event',
                        $queryBuilder->createNamedParameter($eventUid, Connection::PARAM_INT),
                    ),
                    $queryBuilder->expr()->eq(
                        'd.day_time',
                        $queryBuilder->createNamedParameter($timestamp, Connection::PARAM_INT),
                    ),
                )
                ->executeQuery()
                ->fetchAssociative();
        } catch (Exception) {
            return [];
        }

        return is_array($day) ? $day : [];
    }

    /**
     * Removes all day records linked to the specified event record. If the event record is versioned,
     * only day records within the same workspace will be deleted. Since the "event" column may contain
     * various translated or versioned event UIDs, the more stable "def_lang_event_uid" column is used instead,
     * as it consistently references the event UID in the default workspace and default language.
     */
    public function removeAllByEventRecord(array $eventRecordInDefaultLanguage): void
    {
        $eventUid = (int)($eventRecordInDefaultLanguage['uid'] ?? 0);
        if ($eventUid === 0) {
            return;
        }

        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
        $connection->delete(
            self::TABLE,
            [
                'def_lang_event_uid' => $eventUid,
                't3ver_wsid' => $eventRecordInDefaultLanguage['t3ver_wsid'] ?? 0,
            ],
        );
    }

    public function bulkInsertAllDayRecords(
        array $dayRecords,
        array $eventRecordInDefaultLanguage,
        array $languageUids
    ): void {
        if ($dayRecords === []) {
            return;
        }

        $this->createDayRecordInDefaultLanguage($dayRecords, $eventRecordInDefaultLanguage);
        $this->translateDayRecords($eventRecordInDefaultLanguage, $languageUids);
    }

    protected function createDayRecordInDefaultLanguage(array $dayRecords, array $eventRecordInDefaultLanguage): void
    {
        if ($eventRecordInDefaultLanguage['t3ver_wsid'] > 0) {
            $this->createVersionRecords($dayRecords, $eventRecordInDefaultLanguage);
        } else {
            foreach (array_chunk($dayRecords, 10) as $dayRecordsChunk) {
                $dataMap = [];
                foreach ($dayRecordsChunk as $dayRecord) {
                    $dataMap[self::TABLE][StringUtility::getUniqueId('NEW')] = $dayRecord;
                }

                $this->processWithDataHandler($dataMap);
            }
        }
    }

    protected function translateDayRecords(array $eventRecordInDefaultLanguage, array $languageUids): void
    {
        // The $dayRecords from self::bulkInsertAllDayRecords cannot be reused here because they do not contain a UID
        $dayRecords = $this->getDayRecordsByEvent(
            $eventRecordInDefaultLanguage,
            (int)($eventRecordInDefaultLanguage['t3ver_wsid'] ?? 0)
        );

        if ($eventRecordInDefaultLanguage['t3ver_wsid'] > 0) {
            // For workspaces, the "localize" (see "else" below) method cannot be used, as it would create a new
            // translated AND versioned record with t3ver_state set to NEW instead of DEFAULT.
            foreach ($languageUids as $languageUid) {
                $this->createVersionRecords($dayRecords, $eventRecordInDefaultLanguage, $languageUid);
            }
        } else {
            // Translate day records
            foreach ($languageUids as $languageUid) {
                foreach (array_chunk($dayRecords, 10) as $dayRecordsChunk) {
                    $cmdMap = [];
                    foreach ($dayRecordsChunk as $dayRecord) {
                        $cmdMap[self::TABLE][$dayRecord['uid']]['localize'] = $languageUid;
                    }

                    $this->processWithDataHandler([], $cmdMap);
                }
            }
        }
    }

    protected function createVersionRecords(array $newDayRecords, array $eventRecord): void
    {
        $dataHandler = $this->getDataHandler();
        $dayRecordsOfLive = $this->getDayRecordsByEvent($eventRecord, 0);
        $newDayRecordKey = 0;

        $workspaceOptions = [
            'label' => 'Auto-created by events2 for WS #' . $this->getBackendUser()->workspace,
            'delete' => false,
        ];

        foreach ($newDayRecords as $newDayRecordKey => $newDayRecord) {
            $overrideArray = [
                't3ver_wsid' => $this->getBackendUser()->workspace,
                't3ver_stage' => 0,
            ];

            // Verify whether an active LIVE record exists to which a new relation can be established
            if (array_key_exists($newDayRecordKey, $dayRecordsOfLive)) {
                $uidOfLiveRecord = (int)$dayRecordsOfLive[$newDayRecordKey];
                $overrideArray['t3ver_oid'] = $uidOfLiveRecord;
                $overrideArray['t3ver_state'] = VersionState::DEFAULT_STATE->value;

                $dataHandler->copyRecord_raw(self::TABLE, $uidOfLiveRecord, (int)$eventRecord['pid'], $overrideArray, $workspaceOptions);
            } else {
                // There are more new day records than currently available in the LIVE workspace. Create new day
                // records here without an associated relational record in the LIVE workspace.
                $newDayRecord['t3ver_oid'] = 0;
                $newDayRecord['t3ver_state'] = VersionState::NEW_PLACEHOLDER->value;
                $newDayRecord['t3ver_wsid'] = $this->getBackendUser()->workspace;

                $dataHandler->insertDB(self::TABLE, StringUtility::getUniqueId('NEW'), $newDayRecord);
            }
        }

        // If there are fewer new day records compared to the LIVE workspace, versioned day records must be added
        // to mark the day record as DELETED.
        foreach (array_filter($dayRecordsOfLive, fn ($dayRecordOfLive, $dayRecordKeyOfLive) => $dayRecordKeyOfLive > $newDayRecordKey,ARRAY_FILTER_USE_BOTH) as $dayRecordOfLive) {
            $dataHandler->versionizeRecord(self::TABLE, (int)$dayRecordOfLive['uid'], 'Auto-created by events2 for WS #' . $this->getBackendUser()->workspace, true);
        }
    }

    protected function processWithDataHandler(array $dataMap = [], $cmdMap = []): void
    {
        $dataHandler = $this->getDataHandler();
        $dataHandler->start($dataMap, $cmdMap, $this->getBackendUser());
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        if ($dataHandler->errorLog !== []) {
            foreach ($dataHandler->errorLog as $errorLog) {
                $this->logger->error('DataHandler Log: ' . $errorLog);
            }
        }
    }

    public function getDayRecordsByEvent(
        array $eventRecordInDefaultLanguage,
        int $workspaceUid,
    ): array {
        $queryBuilder = $this->queryBuilder;
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $eventUid = (int)($eventRecordInDefaultLanguage['_ORIG_uid'] ?? $eventRecordInDefaultLanguage['uid']);

        $queryResult = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'event',
                    $queryBuilder->createNamedParameter($eventUid, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->eq(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter($workspaceUid, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA'][self::TABLE]['ctrl']['languageField'],
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
            ->executeQuery();

        $dayRecordsInDefaultLanguage = [];
        while ($dayRecordInDefaultLanguage = $queryResult->fetchAssociative()) {
            $dayRecordsInDefaultLanguage[] = $dayRecordInDefaultLanguage;
        }

        return $dayRecordsInDefaultLanguage;
    }

    protected function getDataHandler(): DataHandler
    {
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        // Since we are using copyRecord_raw, the DataHandler must be initialized beforehand
        $dataHandler->start([], []);

        return $dataHandler;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
