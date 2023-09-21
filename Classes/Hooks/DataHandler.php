<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Hooks;

use JWeiland\Events2\Service\DayRelationService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * Hook into DataHandler and clear special caches or re-generate day records after saving an event.
 */
class DataHandler
{
    protected CacheManager $cacheManager;

    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
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

    /**
     * Add day relations to event record(s) while creating or updating them in backend.
     */
    public function processDatamap_afterAllOperations(\TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler): void
    {
        if (array_key_exists('tx_events2_domain_model_event', $dataHandler->datamap)) {
            foreach ($dataHandler->datamap['tx_events2_domain_model_event'] as $eventUid => $eventRecord) {
                if (!$this->isValidRecord($eventRecord, 'tx_events2_domain_model_event')) {
                    continue;
                }

                $this->addDayRelationsForEvent($this->getRealUid($eventUid, $dataHandler));
            }
        }
    }

    /**
     * TYPO3 adds parts of translated records to DataMap while saving a record in default language.
     * See: DataMapProcessor::instance(x, y, z)->process(); in DataHandler::process_datamap().
     *
     * These translated records contains all columns configured with l10n_mode=exclude like "starttime" and "endtime".
     * As these translated records leads to duplicates while saving an event record we have to prevent processing
     * such kind of records.
     */
    protected function isValidRecord(array $recordFromRequest, string $tableName): bool
    {
        $isTableLocalizable = BackendUtility::isTableLocalizable($tableName);

        return
            !$isTableLocalizable
            || (
                $isTableLocalizable
                && ($languageField = $GLOBALS['TCA'][$tableName]['ctrl']['languageField'])
                && array_key_exists($languageField, $recordFromRequest)
            );
    }

    /**
     * Add day relations to event record
     */
    protected function addDayRelationsForEvent(int $eventUid): void
    {
        $this->getDayRelationService()->createDayRelations($eventUid);
    }

    /**
     * If a record was new, its uid is not an int. It's a string starting with "NEW"
     * This method returns the real uid as int.
     *
     * @param int|string $uid
     */
    protected function getRealUid($uid, \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler): int
    {
        if (\str_starts_with($uid, 'NEW')) {
            $uid = $dataHandler->substNEWwithIDs[$uid];
        }

        return (int)$uid;
    }

    protected function getDayRelationService(): DayRelationService
    {
        return GeneralUtility::makeInstance(DayRelationService::class);
    }
}
