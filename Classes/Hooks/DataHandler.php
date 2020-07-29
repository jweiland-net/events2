<?php

declare(strict_types = 1);

/**
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Hooks;

use JWeiland\Events2\Service\DayRelationService;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Hook into DataHandler and clear special caches or re-generate day records after saving an event.
 */
class DataHandler
{
    /**
     * Flushes the cache if an event record was edited.
     * This happens on two levels: by UID and by PID.
     *
     * @param array $params
     */
    public function clearCachePostProc(array $params)
    {
        if (isset($params['table']) && $params['table'] === 'tx_events2_domain_model_event') {
            $cacheTagsToFlush = ['tx_events2_domain_model_event'];
            if (isset($params['uid'])) {
                $cacheTagsToFlush[] = 'tx_events2_uid_' . $params['uid'];
            }
            if (isset($params['uid_page'])) {
                $cacheTagsToFlush[] = 'tx_events2_pid_' . $params['uid_page'];
            }

            $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
            foreach ($cacheTagsToFlush as $cacheTag) {
                $cacheManager->flushCachesInGroupByTag('pages', $cacheTag);
            }
        }
    }

    /**
     * Add day relations to event record(s) while creating or saving them in backend.
     *
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     */
    public function processDatamap_afterAllOperations(\TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler)
    {
        if (array_key_exists('tx_events2_domain_model_event', $dataHandler->datamap)) {
            foreach ($dataHandler->datamap['tx_events2_domain_model_event'] as $eventUid => $eventRecord) {
                $this->addDayRelationsForEvent($this->getRealUid($eventUid, $dataHandler));
            }
        }
    }

    /**
     * Add day relations to event record
     *
     * @param int $eventUid
     */
    protected function addDayRelationsForEvent(int $eventUid)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $dayRelationService = $objectManager->get(DayRelationService::class);
        $dayRelationService->createDayRelations($eventUid);
    }

    /**
     * If a record was new, its uid is not an int. It's a string starting with "NEW"
     * This method returns the real uid as int.
     *
     * @param int|string $uid
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     * @return int
     */
    protected function getRealUid($uid, \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler): int
    {
        if (GeneralUtility::isFirstPartOfStr($uid, 'NEW')) {
            $uid = $dataHandler->substNEWwithIDs[$uid];
        }
        return (int)$uid;
    }
}
