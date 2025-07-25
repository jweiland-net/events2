<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Traits;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Utility\HttpUtility;

trait CreatePostStreamBodyTrait
{
    protected function createBodyFromArray(array $postPayload): StreamInterface
    {
        $streamFactory = $this->get(StreamFactoryInterface::class);
        return $streamFactory->createStream(HttpUtility::buildQueryString($postPayload));
    }
}
