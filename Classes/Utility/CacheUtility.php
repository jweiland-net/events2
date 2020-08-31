<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

/*
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
    public static function addCacheTagsByEventRecords($eventRecords): void
    {
        if (!self::getEnvironmentService()->isEnvironmentInFrontendMode()) {
            return;
        }

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
    public static function addPageCacheTagsByQuery(QueryInterface $query): void
    {
        if (!self::getEnvironmentService()->isEnvironmentInFrontendMode()) {
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
        $GLOBALS['TSFE']->addCacheTags($cacheTags);
    }

    protected static function getEnvironmentService(): EnvironmentService
    {
        return GeneralUtility::makeInstance(EnvironmentService::class);
    }
}
