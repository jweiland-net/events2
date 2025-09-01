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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Hook into DataHandler and clear special caches or re-generate day records after saving an event.
 */
readonly class Events2DataHandler
{
    public function __construct(
        protected DayRelationService $dayRelationService,
        protected CacheManager $cacheManager,
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
        if (!array_key_exists('tx_events2_domain_model_event', $dataHandler->datamap)) {
            return;
        }

        if (!$dataHandler->isOuterMostInstance()) {
            return;
        }

        foreach ($dataHandler->datamap['tx_events2_domain_model_event'] as $id => $incomingFieldArray) {
            $this->dayRelationService->createDayRelations($this->getEventUid($id, $dataHandler));
        }
    }

    public function processCmdmap_postProcess(string $command, $table, $id, $value, DataHandler $dataHandler, $pasteUpdate, $pasteDatamap)
    {
        if ($table !== 'tx_events2_domain_model_event' || $command !== 'copy') {
            return;
        }

        $newId = $dataHandler->copyMappingArray['tx_events2_domain_model_event'][$id];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_events2_domain_model_day');
        $queryBuilder
            ->update('tx_events2_domain_model_day')
            ->set('def_lang_event_uid', $newId)
            ->where(
                $queryBuilder->expr()->eq('event', $queryBuilder->createNamedParameter($newId, Connection::PARAM_INT))
            )
            ->executeStatement();
    }

    private function getEventUid(int|string $id, DataHandler $dataHandler): int
    {
        if (MathUtility::canBeInterpretedAsInteger($id)) {
            $eventUid = $id;
        } else {
            $eventUid = $dataHandler->substNEWwithIDs[$id];
        }

        return (int)$eventUid;
    }
}
