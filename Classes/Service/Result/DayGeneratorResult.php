<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service\Result;

final class DayGeneratorResult
{
    /**
     * @var \ArrayObject<DateTimeResult>
     */
    private \ArrayObject $dateTimeResultStorage;

    private array $dayRecords = [];

    public function __construct(
        private readonly array $eventRecord,
    ) {
        $this->dateTimeResultStorage = new \ArrayObject();
    }

    public function getEventRecord(): array
    {
        return $this->eventRecord;
    }

    /**
     * @return \ArrayObject<DateTimeResult>
     */
    public function getDateTimeResultStorage(): \ArrayObject
    {
        return $this->dateTimeResultStorage;
    }

    /**
     * @return \ArrayObject<DateTimeResult>
     */
    public function getDateTimeResultStorageSorted(): \ArrayObject
    {
        $this->dateTimeResultStorage->ksort(SORT_NUMERIC);

        return $this->dateTimeResultStorage;
    }

    public function addDateTimeResult(DateTimeResult $dateTimeResult): void
    {
        $this->dateTimeResultStorage[$dateTimeResult->getTimestamp()] = $dateTimeResult;
    }

    public function removeDateTimeResultByDate(\DateTimeImmutable $date): void
    {
        unset($this->dateTimeResultStorage[$date->format('U')]);
    }

    public function getFirstDateTimeResult(): ?DateTimeResult
    {
        $this->dateTimeResultStorage->ksort(SORT_NUMERIC);

        foreach ($this->dateTimeResultStorage as $dateTimeResult) {
            return $dateTimeResult;
        }

        return null;
    }

    public function getDayRecords(): array
    {
        return $this->dayRecords;
    }

    public function setDayRecords(array $dayRecords): void
    {
        $this->dayRecords = $dayRecords;
    }
}
