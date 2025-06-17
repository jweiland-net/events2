<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Event;

/**
 * Use this event if you want to add further modifications to the generated days
 */
final class PostGenerateDaysEvent
{
    private array $dateTimeStorage;

    private array $eventRecord;

    public function __construct(array $dateTimeStorage, array $eventRecord)
    {
        $this->dateTimeStorage = $dateTimeStorage;
        $this->eventRecord = $eventRecord;
    }

    public function getDateTimeStorage(): array
    {
        return $this->dateTimeStorage;
    }

    public function setDateTimeStorage(array $dateTimeStorage): void
    {
        $this->dateTimeStorage = $dateTimeStorage;
    }

    public function getEventRecord(): array
    {
        return $this->eventRecord;
    }
}
