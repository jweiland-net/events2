<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Event;

use JWeiland\Events2\Service\Result\DayGeneratorResult;

/**
 * Use this event if you want to add further modifications to the generated days
 */
final readonly class PostGenerateDaysEvent
{
    public function __construct(
        private DayGeneratorResult $dayGeneratorResult,
        private array $eventRecord,
    ) {}

    public function getDayGeneratorResult(): DayGeneratorResult
    {
        return $this->dayGeneratorResult;
    }

    public function getEventRecord(): array
    {
        return $this->eventRecord;
    }
}
