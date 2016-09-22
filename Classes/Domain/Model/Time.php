<?php

namespace JWeiland\Events2\Domain\Model;
    
/*
 * This file is part of the TYPO3 CMS project.
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
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Time extends AbstractEntity
{
    /**
     * Weekday.
     *
     * @var string
     */
    protected $weekday = '';

    /**
     * Time begin.
     *
     * @var string
     * @validate NotEmpty,RegularExpression(regularExpression=/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/)
     */
    protected $timeBegin = '';

    /**
     * Time entry.
     *
     * @var string
     * @validate RegularExpression(regularExpression=/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/)
     */
    protected $timeEntry = '';

    /**
     * Duration.
     *
     * @var string
     * @validate RegularExpression(regularExpression=/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/)
     */
    protected $duration = '';

    /**
     * Time end.
     *
     * @var string
     * @validate RegularExpression(regularExpression=/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/)
     */
    protected $timeEnd = '';

    /**
     * Returns the weekday.
     *
     * @return string $weekday
     */
    public function getWeekday()
    {
        return $this->weekday;
    }

    /**
     * Sets the weekday.
     *
     * @param string $weekday
     */
    public function setWeekday($weekday)
    {
        $this->weekday = (string)$weekday;
    }

    /**
     * Returns the timeBegin.
     *
     * @return string $timeBegin
     */
    public function getTimeBegin()
    {
        return $this->timeBegin;
    }

    /**
     * Sets the timeBegin.
     *
     * @param string $timeBegin
     */
    public function setTimeBegin($timeBegin)
    {
        $this->timeBegin = (string)$timeBegin;
    }

    /**
     * Returns the timeEntry.
     *
     * @return string $timeEntry
     */
    public function getTimeEntry()
    {
        return $this->timeEntry;
    }

    /**
     * Sets the timeEntry.
     *
     * @param string $timeEntry
     */
    public function setTimeEntry($timeEntry)
    {
        $this->timeEntry = (string)$timeEntry;
    }

    /**
     * Returns the duration.
     *
     * @return string $duration
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Sets the duration.
     *
     * @param string $duration
     */
    public function setDuration($duration)
    {
        $this->duration = (string)$duration;
    }

    /**
     * Returns the timeEnd.
     *
     * @return string $timeEnd
     */
    public function getTimeEnd()
    {
        return $this->timeEnd;
    }

    /**
     * Sets the timeEnd.
     *
     * @param string $timeEnd
     */
    public function setTimeEnd($timeEnd)
    {
        $this->timeEnd = (string)$timeEnd;
    }
}
