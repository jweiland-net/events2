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
use JWeiland\Events2\Service\Record\DayRecordService;
use JWeiland\Events2\Service\Record\EventRecordService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Hook into DataHandler and clear special caches or re-generate day records after saving an event.
 */
readonly class Events2DataHandler
{
    public function __construct(
        protected DayRelationService $dayRelationService,
        protected DayRecordService $dayRecordService,
        protected EventRecordService $eventRecordService,
        protected CacheManager $cacheManager,
    ) {
    }

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

    /*public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        if (!array_key_exists('tx_events2_domain_model_event', $dataHandler->datamap)) {
            return;
        }

        // Due to the DataMapProcessor, this array contains entries for all available languages
        foreach ($dataHandler->datamap['tx_events2_domain_model_event'] as $id => $incomingFieldArray) {
            $eventRecord = $this->getEventRecord($id, $dataHandler);
        }

        $tmp = '';
    }*/

    /**
     * Add day relations to event record(s) while creating or updating them in the backend.
     */
    public function processDatamap_beforeStart(DataHandler $dataHandler): void
    {
        if (!array_key_exists('tx_events2_domain_model_event', $dataHandler->datamap)) {
            return;
        }

        foreach ($dataHandler->datamap['tx_events2_domain_model_event'] as $id => $incomingFieldArray) {
            $eventRecord = $this->getEventRecord($id, $incomingFieldArray);
            $workspaceVersion = BackendUtility::getWorkspaceVersionOfRecord(
                $this->getBackendUser()->workspace,
                'tx_events2_domain_model_event',
                $eventRecord['uid'],
                'uid,t3ver_oid'
            );

            if (!$workspaceVersion) {
                // No action required. First, allow TYPO3 DataHandler to create the necessary versioning records.
                continue;
            }

            $this->deleteExistingDayRecords($id, $eventRecord, $dataHandler);
            $this->addNewDayRecords($id, $eventRecord, $dataHandler);
        }
    }

    private function getExceptionRecords(int|string $id): array
    {
        $exceptionRecords = [];
        foreach ($dataHandler->datamap['tx_events2_domain_model_exception'] ?? [] as $exceptionRecord) {
            if ($exceptionRecord['event'] !== $id) {
                continue;
            }

            $exceptionRecords[] = $exceptionRecord;
        }

        return $exceptionRecords;
    }

    private function addNewDayRecords(int|string $id, array $eventRecord, DataHandler $dataHandler): void
    {
        $newDayUids = [];

        foreach ($this->dayRelationService->getDayRecords($eventRecord, $this->getExceptionRecords($id)) as $dayRecord) {
            $dayRecord['event'] = $id;
            $dayRecord['def_lang_event_uid'] = $id;

            $newDayUid = StringUtility::getUniqueId('NEW');
            $dataHandler->datamap['tx_events2_domain_model_day'][$newDayUid] = $dayRecord;

            $newDayUids[] = $newDayUid;
        }

        $dataHandler->datamap['tx_events2_domain_model_event'][$id]['days'] = implode(',', $newDayUids);
    }

    private function deleteExistingDayRecords(int|string $id, array $eventRecord, DataHandler $dataHandler): void
    {
        if (!MathUtility::canBeInterpretedAsInteger($id)) {
            return;
        }

        foreach ($this->getExistingDayRecords($eventRecord) as $existingDayRecord) {
            $dataHandler->cmdmap['tx_events2_domain_model_day'][$existingDayRecord['uid']]['delete'] = 1;
        }
    }

    private function getEventRecord(int|string $id, array $incomingFieldArray): array
    {
        if (MathUtility::canBeInterpretedAsInteger($id)) {
            $eventRecord = $this->eventRecordService->findByUid($id);
            ArrayUtility::mergeRecursiveWithOverrule($eventRecord, $incomingFieldArray);
        } else {
            $eventRecord = $incomingFieldArray;
        }

        return $eventRecord;
    }

    private function getExistingDayRecords(array $eventRecord): array
    {
        return $this->dayRecordService->getDayRecordsByEvent(
            $eventRecord,
            $this->getBackendUser()->workspace,
        );
    }

    private function getDataHandler(): DataHandler
    {
        return GeneralUtility::makeInstance(DataHandler::class);
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
