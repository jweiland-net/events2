<?php
declare(strict_types = 1);
namespace JWeiland\Events2\Utility;

/*
 * This file is part of the events2 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Cache Utility class
 */
class CacheUtility
{
    /**
     * Adds cache tags to page cache by event-records.
     * Following cache tags will be added to TSFE:
     * "tx_events2_uid_[event:uid]"
     *
     * @param array|QueryResultInterface $eventRecords
     */
    public static function addCacheTagsByEventRecords($eventRecords)
    {
        $cacheTags = [];
        foreach ($eventRecords as $event) {
            // cache tag for each event record
            $cacheTags[] = 'tx_events2_uid_' . $event->getUid();

            if ($event->_getProperty('_localizedUid')) {
                $cacheTags[] = 'tx_events2_uid_' . $event->_getProperty('_localizedUid');
            }
        }
        if (count($cacheTags) > 0) {
            $GLOBALS['TSFE']->addCacheTags($cacheTags);
        }
    }

    /**
     * Adds page cache tags by used storagePages.
     * This adds tags with the scheme tx_events2_pid_[event:pid]
     *
     * @param QueryInterface $query
     */
    public static function addPageCacheTagsByQuery(QueryInterface $query)
    {
        $cacheTags = [];
        if ($query->getQuerySettings()->getStoragePageIds()) {
            // Add cache tags for each storage page
            foreach ($query->getQuerySettings()->getStoragePageIds() as $pageId) {
                $cacheTags[] = 'tx_events2_pid_' . $pageId;
            }
        } else {
            $cacheTags[] = 'tx_events2_domain_model_event';
        }
        $GLOBALS['TSFE']->addCacheTags($cacheTags);
    }
}
