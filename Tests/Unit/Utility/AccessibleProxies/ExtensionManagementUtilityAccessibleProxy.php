<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Utility\AccessibleProxies;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Accessible proxy with protected methods made public
 */
class ExtensionManagementUtilityAccessibleProxy extends ExtensionManagementUtility
{
    public static function setCacheManager(CacheManager $cacheManager = null): void
    {
        static::$cacheManager = $cacheManager;
    }

    public static function getPackageManager(): PackageManager
    {
        return static::$packageManager;
    }
}
