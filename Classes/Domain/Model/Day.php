<?php

namespace JWeiland\Events2\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Stefan Froemken <projects@jweiland.net>, jweiland.net
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Day extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * Day.
     *
     * @var \DateTime
     */
    protected $day = null;

    /**
     * Events.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\JWeiland\Events2\Domain\Model\Event>
     * @lazy
     */
    protected $events = null;

    /**
     * Event.
     *
     * @var Event
     */
    protected $event = null;

    /**
     * Constructor of this class.
     */
    public function __construct()
    {
        $this->initStorageObjects();
    }

    /**
     * Initializes all \TYPO3\CMS\Extbase\Persistence\ObjectStorage properties.
     */
    protected function initStorageObjects()
    {
        $this->events = new ObjectStorage();
    }

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
     * Adds an Event.
     *
     * @param Event $event
     */
    public function addEvent(Event $event)
    {
        $this->events->attach($event);
    }

    /**
     * Removes an Event.
     *
     * @param Event $event
     */
    public function removeEvent(Event $event)
    {
        $this->events->detach($event);
    }

    /**
     * Returns the events.
     *
     * @param array $categories
     * @param array $storagePids
     *
     * @return ObjectStorage $events
     */
    public function getEvents(array $categories = array(), array $storagePids = array())
    {
        if (empty($this->events)) {
            return $this->events;
        }
        $isEmptyCategories = empty($categories);

        /** @var ObjectStorage $eventStorage */
        $eventStorage = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');

        /** @var Event $event */
        foreach ($this->events as $event) {
            // assign current day to event
            $event->setDay($this);
            // check against storagePid
            if (in_array($event->getPid(), $storagePids) || empty($storagePids)) {
                if ($isEmptyCategories) {
                    // there is no category defined, so we can add the category without further checks
                    $eventStorage->attach($event);
                } elseif (!$isEmptyCategories && array_intersect($event->getCategoryUids(), $categories)) {
                    // a category is set. Check, if event has defined category. If yes, add it to the storageObject
                    $eventStorage->attach($event);
                }
            }
        }

        // override and return reduced set of events
        return $this->events = $eventStorage;
    }

    /**
     * Sets the events.
     *
     * @param ObjectStorage $events
     */
    public function setEvents(ObjectStorage $events = null)
    {
        $this->events = $events;
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
    public function setEvent(Event $event = null)
    {
        $this->event = $event;
    }
}
