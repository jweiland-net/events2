<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Event;

class GeneratePathSegmentEvent
{
    /**
     * @var array
     */
    protected $baseRecord = [];

    /**
     * @var string
     */
    protected $pathSegment = '';

    public function __construct(array $baseRecord) {
        $this->baseRecord = $baseRecord;
    }

    public function getBaseRecord(): array
    {
        return $this->baseRecord;
    }

    public function getPathSegment(): string
    {
        return $this->pathSegment;
    }

    public function setPathSegment(string $pathSegment): void
    {
        $this->pathSegment = $pathSegment;
    }
}
