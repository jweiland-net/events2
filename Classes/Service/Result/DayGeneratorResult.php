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
 * Represents the result of the DayGeneratorService processing for an event.
 *
 * This class stores the possible event days for a given event record, as determined by the DayGeneratorService.
 * Each detected event day is initially stored as a DateTimeResult object in the $dateTimeResultStorage property.
 * For each specific day (DateTimeResult), the corresponding possible time records (TimeResult objects) are later assigned.
 *
 * The $dayRecords property is populated subsequently by the DayRecordBuilderService,
 * which computes comprehensive day records for each day/time combination after all times have been added.
 *
 * Responsibilities:
 * - Hold the event record data.
 * - Collect all possible event days as DateTimeResult objects.
 * - Allow addition and access to both date and time results per day.
 * - Store finalized day records, each reflecting a concrete occurrence with all relevant attributes for the TYPO3 events2 extension.
 *
 * Typical usage in the events2 context:
 * 1. DayGeneratorService populates $dateTimeResultStorage with possible event days.
 * 2. For each day, possible times are added as TimeResult objects.
 * 3. DayRecordBuilderService processes days and times, populating $dayRecords with all calculated day/time combinations.
 */
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

    public function addDayRecords(array ...$dayRecords): void
    {
        $this->dayRecords = array_merge($this->dayRecords, ...$dayRecords);
    }
}
