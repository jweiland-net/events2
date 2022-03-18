<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Event;

/*
 * Use this event, if you want to modify or create your own start-/end-date for a given listType
 */
class ModifyStartEndDateForListTypeEvent
{
    protected string $listType = '';

    protected \DateTimeImmutable $startDateTime;

    protected ?\DateTimeImmutable $endDateTime = null;

    protected array $settings = [];

    public function __construct(
        string $listType,
        \DateTimeImmutable $startDateTime,
        ?\DateTimeImmutable $endDateTime = null,
        array $settings = []
    ) {
        $this->listType = $listType;
        $this->startDateTime = $startDateTime;
        $this->endDateTime = $endDateTime;
        $this->settings = $settings;
    }

    public function getListType(): string
    {
        return $this->listType;
    }

    public function getStartDateTime(): \DateTimeImmutable
    {
        return $this->startDateTime;
    }

    public function setStartDateTime(\DateTimeImmutable $startDateTime): void
    {
        $this->startDateTime = $startDateTime;
    }

    public function getEndDateTime(): ?\DateTimeImmutable
    {
        return $this->endDateTime;
    }

    public function setEndDateTime(?\DateTimeImmutable $endDateTime): void
    {
        $this->endDateTime = $endDateTime;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }
}
