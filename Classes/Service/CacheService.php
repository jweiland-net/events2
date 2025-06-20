<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Traits\Typo3RequestTrait;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Cache service for assigning events2 cache tags to pages and content,
 * ensuring targeted cache invalidation when creating or updating event records.
 */
readonly class CacheService
{
    use Typo3RequestTrait;

    /**
     * Enriches the page cache with cache tags based on event records.
     * The following cache tags will be added to the CacheDataCollector:
     * "tx_events2_uid_[event:uid]"
     *
     * @param QueryResultInterface<Event>|array<Event> $events Collection of event records used to generate cache tags.
     */
    public function addCacheTagsByEventRecords(QueryResultInterface|array $events): void
    {
        if (!$this->isFrontendRequest()) {
            return;
        }

        $cacheTags = [];
        foreach ($events as $event) {
            // Cache tag for each event record
            $cacheTags[] = 'tx_events2_uid_' . $event->getUid();

            if ($localizedUid = $event->_getProperty('_localizedUid')) {
                $cacheTags[] = 'tx_events2_uid_' . $localizedUid;
            }
        }

        if ($cacheTags !== []) {
            $this->getCacheDataCollector()->addCacheTags(
                ...array_map(fn(string $cacheTag) => new CacheTag($cacheTag), $cacheTags)
            );
        }
    }

    /**
     * Adds page cache tags by used storagePages.
     * This adds tags with the scheme tx_events2_pid_[event:pid]
     */
    public function addPageCacheTagsByQuery(QueryInterface $query): void
    {
        if (!$this->isFrontendRequest()) {
            return;
        }

        $cacheTags = [];
        if ($query->getQuerySettings()->getStoragePageIds()) {
            // Add cache tags for each storage page
            foreach ($query->getQuerySettings()->getStoragePageIds() as $pageId) {
                $cacheTags[] = 'tx_events2_pid_' . $pageId;
            }
        } else {
            $cacheTags[] = 'tx_events2_domain_model_event';
        }

        $this->getCacheDataCollector()->addCacheTags(
            ...array_map(fn(string $cacheTag) => new CacheTag($cacheTag), $cacheTags)
        );
    }
}
