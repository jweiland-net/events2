<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Utility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Cache Utility class
 */
class CacheUtility
{
    /**
     * Adds cache tags to page cache by event-records.
     * Following cache tags will be added to TSFE:
     * "tx_events2_uid_[event:uid]"
     */
    public static function addCacheTagsByEventRecords(QueryResultInterface|array $eventRecords): void
    {
        if (!ApplicationType::fromRequest(self::getTypo3Request())->isFrontend()) {
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
            self::getTypoScriptFrontendController()->addCacheTags($cacheTags);
        }
    }

    /**
     * Adds page cache tags by used storagePages.
     * This adds tags with the scheme tx_events2_pid_[event:pid]
     */
    public static function addPageCacheTagsByQuery(QueryInterface $query): void
    {
        if (!ApplicationType::fromRequest(self::getTypo3Request())->isFrontend()) {
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

        self::getTypoScriptFrontendController()->addCacheTags($cacheTags);
    }

    private static function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return self::getTypo3Request()->getAttribute('frontend.controller');
    }

    private static function getTypo3Request(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }
}
