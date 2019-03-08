<?php
namespace JWeiland\Events2\Tests\Unit\Utility\AccessibleProxies;

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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Accessible proxy with protected methods made public
 */
class ExtensionManagementUtilityAccessibleProxy extends ExtensionManagementUtility
{
    public static function setCacheManager(CacheManager $cacheManager = null)
    {
        static::$cacheManager = $cacheManager;
    }

    public static function getPackageManager()
    {
        return static::$packageManager;
    }
}
