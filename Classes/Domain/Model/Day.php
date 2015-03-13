<?php
namespace JWeiland\Events2\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Stefan Froemken <projects@jweiland.net>, jweiland.net
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
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Day extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * Day
	 *
	 * @var \DateTime
	 */
	protected $day = NULL;

	/**
	 * Events
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\JWeiland\Events2\Domain\Model\Event>
	 * @lazy
	 */
	protected $events = NULL;

	/**
	 * Event
	 *
	 * @var \JWeiland\Events2\Domain\Model\Event
	 */
	protected $event = NULL;





	/**
	 * Constructor of this class
	 */
	public function __construct() {
		$this->initStorageObjects();
	}

	/**
	 * Initializes all \TYPO3\CMS\Extbase\Persistence\ObjectStorage properties.
	 *
	 * @return void
	 */
	protected function initStorageObjects() {
		$this->events = new ObjectStorage();
	}

	/**
	 * Returns the day
	 *
	 * @return \DateTime $day
	 */
	public function getDay() {
		return $this->day;
	}

	/**
	 * Sets the day
	 *
	 * @param \DateTime $day
	 * @return void
	 */
	public function setDay(\DateTime $day) {
		$this->day = $day;
	}

	/**
	 * Adds an Event
	 *
	 * @param \JWeiland\Events2\Domain\Model\Event $event
	 * @return void
	 */
	public function addEvent(Event $event) {
		$this->events->attach($event);
	}

	/**
	 * Removes an Event
	 *
	 * @param \JWeiland\Events2\Domain\Model\Event $event
	 * @return void
	 */
	public function removeEvent(Event $event) {
		$this->events->detach($event);
	}

	/**
	 * Returns the events
	 *
	 * @param string $categories
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage $events
	 */
	public function getEvents($categories = '') {
		if ($categories !== '' && count($this->events)) {
			// I need a copy of events, else "invalid argument for foreach()"
			// maybe it is because of the @lazy annotation and a loadRealInstance may help.
			$events = clone $this->events;
			/** @var $event \JWeiland\Events2\Domain\Model\Event */
			foreach ($events as $event) {
				// check for each event if it contains given categories
				$eventContainsCategory = FALSE;
				/** @var $eventCategory \JWeiland\Events2\Domain\Model\Category */
				foreach ($event->getCategories() as $eventCategory) {
					if (GeneralUtility::inList($categories, $eventCategory->getUid())) {
						$eventContainsCategory = TRUE;
						break;
					}
				}
				// remove event from ObjectStorage
				if (!$eventContainsCategory) {
					$this->events->detach($event);
				}
			}
		}
		return $this->events;
	}

	/**
	 * Sets the events
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $events
	 * @return void
	 */
	public function setEvents(ObjectStorage $events = NULL) {
		$this->events = $events;
	}

	/**
	 * Returns the event
	 *
	 * @return \JWeiland\Events2\Domain\Model\Event $event
	 */
	public function getEvent() {
		return $this->event;
	}

	/**
	 * Sets the event
	 *
	 * @param \JWeiland\Events2\Domain\Model\Event $event
	 * @return void
	 */
	public function setEvent(Event $event = NULL) {
		$this->event = $event;
	}

}