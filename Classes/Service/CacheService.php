<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use JWeiland\Events2\Traits\Typo3RequestTrait;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Cache service for tagging pages and content with specific events2 cache tags to clear cache while creating/updating
 * event records.
 */
class CacheService
{
    use Typo3RequestTrait;

    /**
     * Adds cache tags to page cache by event-records.
     * Following cache tags will be added to TSFE:
     * "tx_events2_uid_[event:uid]"
     */
    public function addCacheTagsByEventRecords(QueryResultInterface|array $eventRecords): void
    {
        if (!$this->isFrontendRequest()) {
            return;
        }

        $cacheTags = [];
        foreach ($eventRecords as $event) {
            // Cache tag for each event record
            $cacheTags[] = 'tx_events2_uid_' . $event->getUid();

            if ($event->_getProperty('_localizedUid')) {
                $cacheTags[] = 'tx_events2_uid_' . $event->_getProperty('_localizedUid');
            }
        }
        if ($cacheTags !== []) {
            $this->getTypoScriptFrontendController()->addCacheTags($cacheTags);
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

        $this->getTypoScriptFrontendController()->addCacheTags($cacheTags);
    }
}
