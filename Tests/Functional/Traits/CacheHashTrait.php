<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Traits;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * Trait to simplify the generation of a cache hash
 * This is adopted from PageRouter::getCacheHashParameters
 */
trait CacheHashTrait
{
    /**
     * @param array $parameters Add URI parameters WITHOUT the page UID. Please use $pageUid instead
     */
    protected function generateCacheHash(array $parameters, int $pageUid): string
    {
        $cacheHashCalculator = $this->getCacheHashCalculator();

        $parameters['id'] = $pageUid;
        $uri = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);

        return $cacheHashCalculator->calculateCacheHash(
            $cacheHashCalculator->getRelevantParameters($uri),
        );
    }

    protected function getCacheHashCalculator(): CacheHashCalculator
    {
        return GeneralUtility::makeInstance(CacheHashCalculator::class);
    }
}
