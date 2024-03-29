<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Traits;

use JWeiland\Events2\Service\CacheService;

/**
 * Trait to inject CacheService. Mostly used in controllers.
 */
trait InjectCacheServiceTrait
{
    protected CacheService $cacheService;

    public function injectCacheService(CacheService $cacheService): void
    {
        $this->cacheService = $cacheService;
    }
}
