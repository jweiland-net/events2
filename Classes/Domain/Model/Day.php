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
     * Event.
     *
     * @var \JWeiland\Events2\Domain\Model\Event
     * @lazy
     */
    protected $event;

    /**
     * Returns the day.
     *
     * @return \DateTime $day
     */
    public function getDay()
    {
        return $this->day;
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
     *
     * @return \DateTime $dayTime
     */
    public function getDayTime()
    {
        return $this->dayTime;
    }
    
    /**
     * Sets the dayTime
     *
     * @param \DateTime $dayTime
     * @return void
     */
    public function setDayTime($dayTime)
    {
        $this->dayTime = $dayTime;
    }

    /**
     * Returns the event.
     *
     * @return Event $event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Sets the event.
     *
     * @param Event $event
     */
    public function setEvent(Event $event)
    {
        $this->event = $event;
    }
}
