<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Model;

use JWeiland\Events2\Domain\Traits\Typo3PropertiesTrait;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/*
 * This class contains all getter and setters for a Time.
 */
class Time extends AbstractEntity
{
    use Typo3PropertiesTrait;

    protected string $weekday = '';

    /**
     * @Extbase\Validate("NotEmpty")
     * @Extbase\Validate("RegularExpression", options={"regularExpression": "/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/"})
     */
    protected string $timeBegin = '';

    /**
     * @Extbase\Validate("RegularExpression", options={"regularExpression": "/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/"})
     */
    protected string $timeEntry = '';

    /**
     * @Extbase\Validate("RegularExpression", options={"regularExpression": "/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/"})
     */
    protected string $duration = '';

    /**
     * @Extbase\Validate("RegularExpression", options={"regularExpression": "/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/"})
     */
    protected string $timeEnd = '';

    /*
     * Helper variables for Fluid
     * Helpful to use f:format.date()
     */

    /**
     * @var \DateTime|\DateTimeImmutable|null
     */
    protected ?\DateTimeInterface $timeBeginAsDateTime;

    /**
     * @var \DateTime|\DateTimeImmutable|null
     */
    protected ?\DateTimeInterface $timeEntryAsDateTime;

    /**
     * @var \DateTime|\DateTimeImmutable|null
     */
    protected ?\DateTimeInterface $timeEndAsDateTime;

    public function getWeekday(): string
    {
        return $this->weekday;
    }

    public function setWeekday(string $weekday): void
    {
        $this->weekday = $weekday;
    }

    public function getTimeBegin(): string
    {
        return $this->timeBegin;
    }

    public function setTimeBegin(string $timeBegin): void
    {
        $this->timeBegin = $timeBegin;
    }

    public function getTimeEntry(): string
    {
        return $this->timeEntry;
    }

    public function setTimeEntry(string $timeEntry): void
    {
        $this->timeEntry = $timeEntry;
    }

    public function getDuration(): string
    {
        return $this->duration;
    }

    public function setDuration(string $duration): void
    {
        $this->duration = $duration;
    }

    public function getTimeEnd(): string
    {
        return $this->timeEnd;
    }

    public function setTimeEnd(string $timeEnd): void
    {
        $this->timeEnd = $timeEnd;
    }

    /*
     * Helper methods for Fluid
     * Helpful to use f:format.date()
     */

    /**
     * @return \DateTime|\DateTimeImmutable|null
     */
    public function getTimeBeginAsDateTime(): ?\DateTimeInterface
    {
        return $this->timeBeginAsDateTime;
    }

    /**
     * @param \DateTime|\DateTimeImmutable $timeBegin
     */
    public function setTimeBeginAsDateTime(\DateTimeInterface $timeBegin = null): void
    {
        $this->timeBeginAsDateTime = $timeBegin;
    }

    /**
     * @return \DateTime|\DateTimeImmutable|null
     */
    public function getTimeEntryAsDateTime(): ?\DateTimeInterface
    {
        return $this->timeEntryAsDateTime;
    }

    /**
     * @param \DateTime|\DateTimeImmutable $timeEntry
     */
    public function setTimeEntryAsDateTime(\DateTimeInterface $timeEntry = null): void
    {
        $this->timeEntryAsDateTime = $timeEntry;
    }

    /**
     * @return \DateTime|\DateTimeImmutable|null
     */
    public function getTimeEndAsDateTime(): ?\DateTimeInterface
    {
        return $this->timeEndAsDateTime;
    }

    /**
     * @param \DateTime|\DateTimeImmutable $timeEnd
     */
    public function setTimeEndAsDateTime(\DateTimeInterface $timeEnd = null): void
    {
        $this->timeEndAsDateTime = $timeEnd;
    }
}
