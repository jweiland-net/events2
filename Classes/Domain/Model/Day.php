<?php

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
 * This class contains all getter and setters for a Day.
 */
class Day extends AbstractEntity
{
    /**
     * Day.
     *
     * @var \DateTime
     */
    protected $day;

    /**
     * DayTime.
     *
     * @var \DateTime
     */
    protected $dayTime;

    /**
     * SortDayTime.
     *
     * @var \DateTime
     */
    protected $sortDayTime;

    /**
     * Event.
     *
     * @var \JWeiland\Events2\Domain\Model\Event
     */
    protected $event;

    /**
     * Returns the day.
     */
    public function getDay(): \DateTime
    {
        if ($this->day->timezone_type !== 3) {
            $this->day->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }
        return clone $this->day;
    }

    /**
     * Sets the day.
     *
     * @param \DateTime $day
     */
    public function setDay(\DateTime $day)
    {
        $this->day = $day;
    }

    /**
     * Returns the dayTime
     */
    public function getDayTime(): \DateTime
    {
        if ($this->dayTime->timezone_type !== 3) {
            $this->dayTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }
        return clone $this->dayTime;
    }

    /**
     * Sets the dayTime
     *
     * @param \DateTime $dayTime
     */
    public function setDayTime(\DateTime $dayTime)
    {
        $this->dayTime = $dayTime;
    }

    /**
     * Returns the sortDayTime
     */
    public function getSortDayTime(): \DateTime
    {
        if ($this->sortDayTime->timezone_type !== 3) {
            $this->sortDayTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }
        return clone $this->sortDayTime;
    }

    /**
     * Sets the sortDayTime
     *
     * @param \DateTime $sortDayTime
     */
    public function setSortDayTime(\DateTime $sortDayTime)
    {
        $this->sortDayTime = $sortDayTime;
    }

    /**
     * Returns the event.
     *
     * @return Event|null $event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Sets the event.
     *
     * @param Event|null $event
     */
    public function setEvent(Event $event = null)
    {
        $this->event = $event;
    }
}
