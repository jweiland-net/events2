<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service\Result;

/**
 * DateTime data object. Instantiate this object with new()
 */
class DateTimeResult
{
    /**
     * @var \ArrayObject<TimeResult>
     */
    private \ArrayObject $timeResultStorage;

    public function __construct(
        private readonly \DateTimeImmutable $date,
        private readonly bool $isRemovedDate = false,
        private ?TimeResult $firstTimeResult = null,
    ) {
        $this->timeResultStorage = new \ArrayObject();
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * Useful to sort values
     */
    public function getTimestamp(): int
    {
        return (int)$this->date->format('U');
    }

    public function isRemovedDate(): bool
    {
        return $this->isRemovedDate;
    }

    /**
     * @return \ArrayObject<TimeResult>
     */
    public function getTimeResultStorage(): \ArrayObject
    {
        return $this->timeResultStorage;
    }

    public function addTimeResult(TimeResult $timeData): void
    {
        if ($this->firstTimeResult === null) {
            $this->firstTimeResult = $timeData;
        }

        $this->timeResultStorage->append($timeData);
    }

    public function getFirstTimeResult(): ?TimeResult
    {
        return $this->firstTimeResult;
    }
}
