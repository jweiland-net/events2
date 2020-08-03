<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
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

    /**
     * @var string
     */
    protected $weekday = '';

    /**
     * @var string
     * @Extbase\Validate("NotEmpty")
     * @Extbase\Validate("RegularExpression", options={"regularExpression": "/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/"})
     */
    protected $timeBegin = '';

    /**
     * @var string
     * @Extbase\Validate("NotEmpty")
     * @Extbase\Validate("RegularExpression", options={"regularExpression": "/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/"})
     */
    protected $timeEntry = '';

    /**
     * @var string
     * @Extbase\Validate("NotEmpty")
     * @Extbase\Validate("RegularExpression", options={"regularExpression": "/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/"})
     */
    protected $duration = '';

    /**
     * @var string
     * @Extbase\Validate("NotEmpty")
     * @Extbase\Validate("RegularExpression", options={"regularExpression": "/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/"})
     */
    protected $timeEnd = '';

    /*
     * Helper variables for Fluid
     * Helpful to use f:format.date()
     */

    /**
     * @var \DateTime
     */
    protected $timeBeginAsDateTime;

    /**
     * @var \DateTime
     */
    protected $timeEntryAsDateTime;

    /**
     * @var \DateTime
     */
    protected $timeEndAsDateTime;

    public function getWeekday(): string
    {
        return $this->weekday;
    }

    public function setWeekday(string $weekday)
    {
        $this->weekday = $weekday;
    }

    public function getTimeBegin(): string
    {
        return $this->timeBegin;
    }

    public function setTimeBegin(string $timeBegin)
    {
        $this->timeBegin = $timeBegin;
    }

    public function getTimeEntry(): string
    {
        return $this->timeEntry;
    }

    public function setTimeEntry(string $timeEntry)
    {
        $this->timeEntry = $timeEntry;
    }

    public function getDuration(): string
    {
        return $this->duration;
    }

    public function setDuration(string $duration)
    {
        $this->duration = $duration;
    }

    public function getTimeEnd(): string
    {
        return $this->timeEnd;
    }

    public function setTimeEnd(string $timeEnd)
    {
        $this->timeEnd = $timeEnd;
    }

    /*
     * Helper methods for Fluid
     * Helpful to use f:format.date()
     */

    public function getTimeBeginAsDateTime(): ?\DateTime
    {
        return $this->timeBeginAsDateTime;
    }

    public function setTimeBeginAsDateTime(\DateTime $timeBegin = null)
    {
        $this->timeBeginAsDateTime = $timeBegin;
    }

    public function getTimeEntryAsDateTime(): ?\DateTime
    {
        return $this->timeEntryAsDateTime;
    }

    public function setTimeEntryAsDateTime(\DateTime $timeEntry = null)
    {
        $this->timeEntryAsDateTime = $timeEntry;
    }

    public function getTimeEndAsDateTime(): ?\DateTime
    {
        return $this->timeEndAsDateTime;
    }

    public function setTimeEndAsDateTime(\DateTime $timeEnd = null)
    {
        $this->timeEndAsDateTime = $timeEnd;
    }
}
