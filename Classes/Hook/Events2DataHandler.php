<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Hook;

use JWeiland\Events2\Service\DayRelationService;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Hook into DataHandler and clear special caches or re-generate day records after saving an event.
 */
readonly class Events2DataHandler
{
    public function __construct(
        protected DayRelationService $dayRelationService,
        protected CacheManager $cacheManager,
        protected ConnectionPool $connectionPool,
    ) {}

    /**
     * Flushes the cache if an event record was edited.
     * This happens on two levels: by UID and by PID.
     */
    public function clearCachePostProc(array $params): void
    {
        if (isset($params['table']) && $params['table'] === 'tx_events2_domain_model_event') {
            $cacheTagsToFlush = ['tx_events2_domain_model_event'];
            if (isset($params['uid'])) {
                $cacheTagsToFlush[] = 'tx_events2_uid_' . $params['uid'];
            }

            if (isset($params['uid_page'])) {
                $cacheTagsToFlush[] = 'tx_events2_pid_' . $params['uid_page'];
            }

            foreach ($cacheTagsToFlush as $cacheTag) {
                $this->cacheManager->flushCachesInGroupByTag('pages', $cacheTag);
            }
        }
    }

    public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        if (!$dataHandler->isOuterMostInstance()) {
            return;
        }

        if (array_key_exists('tx_events2_domain_model_day', $dataHandler->datamap)) {
            foreach (array_keys($dataHandler->datamap['tx_events2_domain_model_day']) as $id) {
                $this->deleteDayRecord($this->getRecordUid($id, $dataHandler));
            }
        }

        if (array_key_exists('tx_events2_domain_model_event', $dataHandler->datamap)) {
            foreach (array_keys($dataHandler->datamap['tx_events2_domain_model_event']) as $id) {
                $this->dayRelationService->createDayRelations($this->getRecordUid($id, $dataHandler));
            }
        }
    }

    /**
     * Handles copy commands for event records and prevents TYPO3 from copying
     * the existing day relation records.
     *
     * The events2 extension manages records of the table tx_events2_domain_model_day
     * itself. These day records are derived from the event record and must be
     * generated for the concrete event record they belong to.
     *
     * During TYPO3's default copy process, the relation field "days" of an event
     * record would be copied as well. This creates copies of the related day records,
     * but those copied day records still contain the UID of the original event record.
     * As a result, the copied event would not receive a clean and valid set of day
     * records pointing to its own UID.
     *
     * Ideally, the "days" field would be excluded before TYPO3 starts copying the
     * record. DataHandler::copyRecord() supports this via its excludeFields argument.
     * However, the core command processing calls copyRecord() with a hard-coded empty
     * exclude field list. The same applies to the override values argument, which is
     * also passed as an empty array and therefore cannot be used from the outside in
     * this situation.
     *
     * To work around this limitation, this hook intercepts copy commands for event
     * records, performs the copy operation manually and passes "days" as excluded
     * field to DataHandler::copyRecord(). After the event record was copied
     * successfully, the day relations are generated explicitly for the newly created
     * event record. Finally, the command is marked as processed so TYPO3 does not run
     * its default copy handling again.
     */
    public function processCmdmap(
        string $command,
        string $table,
        string|int $id,
        mixed $value,
        bool &$commandIsProcessed,
        DataHandler $dataHandler,
        mixed $pasteUpdate,
    ): void {
        if ($command !== 'copy') {
            return;
        }

        if ($table !== 'tx_events2_domain_model_event') {
            return;
        }

        $target = $value['target'] ?? $value;
        $ignoreLocalization = (bool)($value['ignoreLocalization'] ?? false);

        $dataHandler->copyRecord(
            $table,
            $id,
            $target,
            true,
            [],
            'days',
        );

        if ($dataHandler->errorLog === [] && isset($dataHandler->copyMappingArray[$table][$id])) {
            $this->dayRelationService->createDayRelations($this->getRecordUid(
                (int)$dataHandler->copyMappingArray[$table][$id],
                $dataHandler,
            ));

            $commandIsProcessed = true;
        }
    }

    /**
     * Prevent the DataHandler from creating any day records. The relationship between
     * the day and event tables is highly specific. An event in the LIVE workspace
     * may have a different number of associated day records compared to the same
     * event record in a different workspace (> 0). This discrepancy can occur
     * when, for example, the event type is changed from "single" to "recurring".
     *
     * Only our DayRelationService will create valid translated/versionized day records
     * for an event
     */
    private function deleteDayRecord(int $dayRecordUid): void
    {
        $connection = $this->connectionPool->getConnectionForTable('tx_events2_domain_model_day');
        $connection->delete(
            'tx_events2_domain_model_day',
            [
                'uid' => $dayRecordUid,
            ],
        );
    }

    private function getRecordUid(int|string $id, DataHandler $dataHandler): int
    {
        if (MathUtility::canBeInterpretedAsInteger($id)) {
            $recordUid = $id;
        } else {
            $recordUid = $dataHandler->substNEWwithIDs[$id];
        }

        return (int)$recordUid;
    }
}
