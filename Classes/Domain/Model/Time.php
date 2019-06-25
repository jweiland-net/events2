<?php
declare(strict_types = 1);
namespace JWeiland\Events2\Domain\Model;

/*
 * This file is part of the events2 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * This class contains all getter and setters for a Time.
 */
class Time extends AbstractEntity
{
    /**
     * @var string
     */
    protected $weekday = '';

    /**
     * @var string
     * @validate NotEmpty,RegularExpression(regularExpression=/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/)
     */
    protected $timeBegin = '';

    /**
     * @var string
     * @validate RegularExpression(regularExpression=/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/)
     */
    protected $timeEntry = '';

    /**
     * @var string
     * @validate RegularExpression(regularExpression=/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/)
     */
    protected $duration = '';

    /**
     * @var string
     * @validate RegularExpression(regularExpression=/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/)
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

    /**
     * @return string
     */
    public function getWeekday(): string
    {
        return $this->weekday;
    }

    /**
     * @param string $weekday
     */
    public function setWeekday(string $weekday)
    {
        $this->weekday = $weekday;
    }

    /**
     * @return string
     */
    public function getTimeBegin(): string
    {
        return $this->timeBegin;
    }

    /**
     * @param string $timeBegin
     */
    public function setTimeBegin(string $timeBegin)
    {
        $this->timeBegin = $timeBegin;
    }

    /**
     * @return string
     */
    public function getTimeEntry(): string
    {
        return $this->timeEntry;
    }

    /**
     * @param string $timeEntry
     */
    public function setTimeEntry(string $timeEntry)
    {
        $this->timeEntry = $timeEntry;
    }

    /**
     * @return string
     */
    public function getDuration(): string
    {
        return $this->duration;
    }

    /**
     * @param string $duration
     */
    public function setDuration(string $duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return string
     */
    public function getTimeEnd(): string
    {
        return $this->timeEnd;
    }

    /**
     * @param string $timeEnd
     */
    public function setTimeEnd(string $timeEnd)
    {
        $this->timeEnd = $timeEnd;
    }

    /*
     * Helper methods for Fluid
     * Helpful to use f:format.date()
     */

    /**
     * @return \DateTime|null
     */
    public function getTimeBeginAsDateTime()
    {
        return $this->timeBeginAsDateTime;
    }

    /**
     * @param \DateTime $timeBegin
     */
    public function setTimeBeginAsDateTime(\DateTime $timeBegin = null)
    {
        $this->timeBeginAsDateTime = $timeBegin;
    }

    /**
     * @return \DateTime|null
     */
    public function getTimeEntryAsDateTime()
    {
        return $this->timeEntryAsDateTime;
    }

    /**
     * @param \DateTime $timeEntry
     */
    public function setTimeEntryAsDateTime(\DateTime $timeEntry = null)
    {
        $this->timeEntryAsDateTime = $timeEntry;
    }

    /**
     * @return \DateTime|null
     */
    public function getTimeEndAsDateTime()
    {
        return $this->timeEndAsDateTime;
    }

    /**
     * @param \DateTime $timeEnd
     */
    public function setTimeEndAsDateTime(\DateTime $timeEnd = null)
    {
        $this->timeEndAsDateTime = $timeEnd;
    }
}
