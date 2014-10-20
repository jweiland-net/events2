<?php
namespace JWeiland\Events2\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Stefan Froemken <projects@jweiland.net>, jweiland.net
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
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Event extends AbstractEntity {

	/**
	 * Hidden
	 *
	 * @var boolean
	 */
	protected $hidden = FALSE;

	/**
	 * Title
	 *
	 * @var string
	 * @validate NotEmpty
	 */
	protected $title = '';

	/**
	 * TopOfList
	 *
	 * @var boolean
	 */
	protected $topOfList = FALSE;

	/**
	 * Teaser
	 *
	 * @var string
	 */
	protected $teaser = '';

	/**
	 * Event begin
	 *
	 * @var \DateTime
	 * @validate NotEmpty
	 */
	protected $eventBegin = NULL;

	/**
	 * EventTime
	 *
	 * @var \JWeiland\Events2\Domain\Model\Time
	 * @validate NotEmpty
	 */
	protected $eventTime = NULL;

	/**
	 * Event end
	 *
	 * @var \DateTime
	 */
	protected $eventEnd = NULL;

	/**
	 * Recurring event
	 *
	 * @var boolean
	 */
	protected $recurringEvent = FALSE;

	/**
	 * Same day
	 *
	 * @var boolean
	 */
	protected $sameDay = FALSE;

	/**
	 * MultipleTimes
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\JWeiland\Events2\Domain\Model\Time>
	 */
	protected $multipleTimes = NULL;

	/**
	 * xTh
	 *
	 * @var integer
	 */
	protected $xth = 0;

	/**
	 * Weekday
	 *
	 * @var integer
	 */
	protected $weekday = 0;

	/**
	 * differentTimes
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\JWeiland\Events2\Domain\Model\Time>
	 * @lazy
	 */
	protected $differentTimes = NULL;

	/**
	 * Each weeks
	 *
	 * @var integer
	 */
	protected $eachWeeks = 0;

	/**
	 * Exceptions
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\JWeiland\Events2\Domain\Model\Exception>
	 * @lazy
	 */
	protected $exceptions = NULL;

	/**
	 * Detail informations
	 *
	 * @var string
	 */
	protected $detailInformations = '';

	/**
	 * free entry
	 *
	 * @var boolean
	 */
	protected $freeEntry = FALSE;

	/**
	 * Ticket link
	 *
	 * @var \JWeiland\Events2\Domain\Model\Link
	 * @lazy
	 */
	protected $ticketLink = NULL;

	/**
	 * Categories
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category>
	 * @validate NotEmpty
	 * @lazy
	 */
	protected $categories = NULL;

	/**
	 * Days
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\JWeiland\Events2\Domain\Model\Day>
	 * @lazy
	 */
	protected $days = NULL;

	/**
	 * Location
	 *
	 * @var \JWeiland\Events2\Domain\Model\Location
	 * @validate NotEmpty
	 * @lazy
	 */
	protected $location = NULL;

	/**
	 * Organizer
	 *
	 * @var \JWeiland\Events2\Domain\Model\Organizer
	 * @validate NotEmpty
	 * @lazy
	 */
	protected $organizer = NULL;

	/**
	 * Images
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\JWeiland\Events2\Domain\Model\FileReference>
	 */
	protected $images = NULL;

	/**
	 * VideoLink
	 *
	 * @var \JWeiland\Events2\Domain\Model\Link
	 * @cascade remove
	 */
	protected $videoLink = NULL;

	/**
	 * VideoLink
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\JWeiland\Events2\Domain\Model\Link>
	 * @cascade remove
	 * @lazy
	 */
	protected $downloadLinks = NULL;

	/**
	 * SuitabilityCulture
	 *
	 * @var boolean
	 */
	protected $suitabilityCulture = FALSE;

	/**
	 * SuitabilityUser
	 *
	 * @var boolean
	 */
	protected $suitabilityUser = FALSE;

	/**
	 * SuitabilityGroups
	 *
	 * @var boolean
	 */
	protected $suitabilityGroups = FALSE;

	/**
	 * Facebook
	 *
	 * @var boolean
	 */
	protected $facebook = FALSE;

	/**
	 * ReleaseDate
	 *
	 * @var \DateTime
	 */
	protected $releaseDate = NULL;

	/**
	 * SocialTeaser
	 *
	 * @var string
	 */
	protected $socialTeaser = '';

	/**
	 * facebookChannel
	 *
	 * @var integer
	 */
	protected $facebookChannel = 0;

	/**
	 * Theater Details
	 *
	 * @var string
	 */
	protected $theaterDetails = '';

	/**
	 * Day
	 *
	 * @var \JWeiland\Events2\Domain\Model\Day
	 */
	protected $day = NULL;





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
		$this->multipleTimes = new ObjectStorage();
		$this->differentTimes = new ObjectStorage();
		$this->exceptions = new ObjectStorage();
		$this->categories = new ObjectStorage();
		$this->days = new ObjectStorage();
		$this->images = new ObjectStorage();
		$this->downloadLinks = new ObjectStorage();
	}

	/**
	 * Returns the hidden
	 *
	 * @return boolean $hidden
	 */
	public function getHidden() {
		return $this->hidden;
	}

	/**
	 * Sets the hidden
	 *
	 * @param boolean $hidden
	 * @return void
	 */
	public function setHidden($hidden) {
		$this->hidden = (bool) $hidden;
	}

	/**
	 * Returns the title
	 *
	 * @return string $title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Sets the title
	 *
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = (string) $title;
	}

	/**
	 * Returns the topOfList
	 *
	 * @return boolean $topOfList
	 */
	public function getTopOfList() {
		return $this->topOfList;
	}

	/**
	 * Sets the topOfList
	 *
	 * @param boolean $topOfList
	 * @return void
	 */
	public function setTopOfList($topOfList) {
		$this->topOfList = (bool) $topOfList;
	}

	/**
	 * Returns the boolean state of topOfList
	 *
	 * @return boolean
	 */
	public function isTopOfList() {
		return $this->getTopOfList();
	}

	/**
	 * Returns the teaser
	 *
	 * @return string $teaser
	 */
	public function getTeaser() {
		return $this->teaser;
	}

	/**
	 * Sets the teaser
	 *
	 * @param string $teaser
	 * @return void
	 */
	public function setTeaser($teaser) {
		$this->teaser = (string) $teaser;
	}

	/**
	 * Returns the eventBegin
	 *
	 * @return \DateTime $eventBegin
	 */
	public function getEventBegin() {
		return $this->eventBegin;
	}

	/**
	 * Sets the eventBegin
	 *
	 * @param \DateTime $eventBegin
	 * @return void
	 */
	public function setEventBegin(\DateTime $eventBegin = NULL) {
		$this->eventBegin = $eventBegin;
	}

	/**
	 * Returns the event_time
	 *
	 * @return \JWeiland\Events2\Domain\Model\Time $time
	 */
	public function getEventTime() {
		return $this->eventTime;
	}

		/**
	 * Sets the event_time
	 *
	 * @param \JWeiland\Events2\Domain\Model\Time $eventTime
	 * @return void
	 */
	public function setEventTime(\JWeiland\Events2\Domain\Model\Time $eventTime = NULL) {
		$this->eventTime = $eventTime;
	}

	/**
	 * Returns the days of an event if it takes several days
	 *
	 * @return int $durationInDays
	 */
	public function getDaysOfEventsTakingDays() {
		$dateTimeUtility = new DateTimeUtility();

		$eventBegin = $dateTimeUtility->standardizeDateTimeObject($this->getEventBegin());
		$eventEnd = $dateTimeUtility->standardizeDateTimeObject($this->getEventEnd());
		if (!empty($eventEnd) && $eventEnd != $eventBegin) {
			$diff = $eventBegin->diff($eventEnd);
			// Example: 20.01.2013 - 23.01.2013 = 4 days but diff shows 3. So we have to add 1 day here
			return (int)$diff->format('%a') + 1;
		} else return 0;
	}

	/**
	 * Returns the eventEnd
	 *
	 * @return \DateTime $eventEnd
	 */
	public function getEventEnd() {
		return $this->eventEnd;
	}

	/**
	 * Sets the eventEnd
	 *
	 * @param \DateTime $eventEnd
	 * @return void
	 */
	public function setEventEnd(\DateTime $eventEnd = NULL) {
		$this->eventEnd = $eventEnd;
	}

	/**
	 * Returns the recurringEvent
	 *
	 * @return boolean $recurringEvent
	 */
	public function getRecurringEvent() {
		if (!$this->recurringEvent) {
			// if an event has an EventEnd-Date set, then it is automatically an recurring event
			if ($this->getEventEnd()) {
				return TRUE;
			} else return FALSE;
		} else return $this->recurringEvent;
	}

	/**
	 * Sets the recurringEvent
	 *
	 * @param boolean $recurringEvent
	 * @return void
	 */
	public function setRecurringEvent($recurringEvent) {
		$this->recurringEvent = (bool)$recurringEvent;
	}

	/**
	 * Returns the boolean state of recurringEvent
	 *
	 * @return boolean
	 */
	public function isRecurringEvent() {
		return $this->getRecurringEvent();
	}

	/**
	 * Returns the sameDay
	 *
	 * @return boolean $sameDay
	 */
	public function getSameDay() {
		return $this->sameDay;
	}

	/**
	 * Sets the sameDay
	 *
	 * @param boolean $sameDay
	 * @return void
	 */
	public function setSameDay($sameDay) {
		$this->sameDay = (bool)$sameDay;
	}

	/**
	 * Returns the boolean state of sameDay
	 *
	 * @return boolean
	 */
	public function isSameDay() {
		$this->getSameDay();
	}

	/**
	 * Returns the multipleTimes
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage $time
	 */
	public function getMultipleTimes() {
		return $this->multipleTimes;
	}

	/**
	 * Sets the multipleTimes
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $multipleTimes
	 * @return void
	 */
	public function setMultipleTimes(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $multipleTimes = NULL) {
		$this->multipleTimes = $multipleTimes;
	}

	/**
	 * Returns the xth
	 *
	 * @return array $xth
	 */
	public function getXth() {
		$result = array();
		$items = $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['xth']['config']['items'];
		foreach ($items as $key => $item) {
			$result[$item[1]] = $this->xth & pow(2, $key);
		}
		return $result;
	}

	/**
	 * Sets the xth
	 *
	 * @param integer $xth
	 * @return void
	 */
	public function setXth($xth) {
		$this->xth = $xth;
	}

	/**
	 * Returns the weekday
	 *
	 * @return array $weekday
	 */
	public function getWeekday() {
		$result = array();
		$items = $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['weekday']['config']['items'];
		foreach ($items as $key => $item) {
			$result[$item[1]] = $this->weekday & pow(2, $key);
		}
		return $result;
	}

	/**
	 * Sets the weekday
	 *
	 * @param integer $weekday
	 * @return void
	 */
	public function setWeekday($weekday) {
		$this->weekday = $weekday;
	}

	/**
	 * Adds a Times
	 *
	 * @param \JWeiland\Events2\Domain\Model\Time $differentTime
	 * @return void
	 */
	public function addDifferentTime(\JWeiland\Events2\Domain\Model\Time $differentTime = NULL) {
		$this->differentTimes->attach($differentTime);
	}

	/**
	 * Removes a Times
	 *
	 * @param \JWeiland\Events2\Domain\Model\Time $differentTime The Time to be removed
	 * @return void
	 */
	public function removeDifferentTime(\JWeiland\Events2\Domain\Model\Time $differentTime) {
		$this->differentTimes->detach($differentTime);
	}

	/**
	 * Returns the differentTimes
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage $differentTimes
	 */
	public function getDifferentTimes() {
		return $this->differentTimes;
	}

	/**
	 * Sets the differentTimes
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $differentTimes
	 * @return void
	 */
	public function setDifferentTimes(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $differentTimes = NULL) {
		$this->differentTimes = $differentTimes;
	}

	/**
	 * Returns the eachWeeks
	 *
	 * @return integer $eachWeeks
	 */
	public function getEachWeeks() {
		return $this->eachWeeks;
	}

	/**
	 * Sets the eachWeeks
	 *
	 * @param integer $eachWeeks
	 * @return void
	 */
	public function setEachWeeks($eachWeeks) {
		$this->eachWeeks = $eachWeeks;
	}

	/**
	 * Adds an Exception
	 *
	 * @param \JWeiland\Events2\Domain\Model\Exception $exception
	 * @return void
	 */
	public function addException(\JWeiland\Events2\Domain\Model\Exception $exception) {
		$this->exceptions->attach($exception);
	}

	/**
	 * Removes an Exception
	 *
	 * @param \JWeiland\Events2\Domain\Model\Exception $exception
	 * @return void
	 */
	public function removeException(\JWeiland\Events2\Domain\Model\Exception $exception) {
		$this->exceptions->detach($exception);
	}

	/**
	 * Returns the exceptions
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage $exceptions
	 */
	public function getExceptions() {
		return $this->exceptions;
	}

	/**
	 * Returns the exceptions in future
	 *
	 * @return array $exceptions
	 */
	public function getFutureExceptions() {
		$futureExceptions = array();
		$currentDate = new \DateTime('today');
		/** @var \JWeiland\Events2\Domain\Model\Exception $exception */
		foreach ($this->exceptions as $exception) {
			if ($exception->getExceptionDate() > $currentDate) {
				$futureExceptions[$exception->getExceptionDate()->format('U')] = $exception;
			}
		}
		if (count($futureExceptions) === 1 && current($futureExceptions)->getExceptionDate() == $this->day->getDay()) {
			$futureExceptions = array();
		} else {
			ksort($futureExceptions, SORT_NUMERIC);
		}
		return $futureExceptions;
	}

	/**
	 * Sets the Exceptions
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $exceptions
	 * @return void
	 */
	public function setExceptions(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $exceptions) {
		$this->exceptions = $exceptions;
	}

	/**
	 * Returns the detailInformations
	 *
	 * @return string $detailInformations
	 */
	public function getDetailInformations() {
		return $this->detailInformations;
	}

	/**
	 * Sets the detailInformations
	 *
	 * @param string $detailInformations
	 * @return void
	 */
	public function setDetailInformations($detailInformations) {
		$this->detailInformations = (string)$detailInformations;
	}

	/**
	 * Returns the freeEntry
	 *
	 * @return boolean $freeEntry
	 */
	public function getFreeEntry() {
		return $this->freeEntry;
	}

	/**
	 * Sets the freeEntry
	 *
	 * @param boolean $freeEntry
	 * @return void
	 */
	public function setFreeEntry($freeEntry) {
		$this->freeEntry = (bool)$freeEntry;
	}

	/**
	 * Returns the boolean state of freeEntry
	 *
	 * @return boolean
	 */
	public function isFreeEntry() {
		return $this->getFreeEntry();
	}

	/**
	 * Returns the ticketLink
	 *
	 * @return \JWeiland\Events2\Domain\Model\Link $ticketLink
	 */
	public function getTicketLink() {
		return $this->ticketLink;
	}

	/**
	 * Sets the ticketLink
	 *
	 * @param \JWeiland\Events2\Domain\Model\Link $ticketLink
	 * @return void
	 */
	public function setTicketLink(\JWeiland\Events2\Domain\Model\Link $ticketLink = NULL) {
		$this->ticketLink = $ticketLink;
	}

	/**
	 * Adds a Category
	 *
	 * @param \TYPO3\CMS\Extbase\Domain\Model\Category $category
	 * @return void
	 */
	public function addCategory(\TYPO3\CMS\Extbase\Domain\Model\Category $category) {
		$this->categories->attach($category);
	}

	/**
	 * Removes a Category
	 *
	 * @param \TYPO3\CMS\Extbase\Domain\Model\Category $categoryToRemove The Category to be removed
	 * @return void
	 */
	public function removeCategory(\TYPO3\CMS\Extbase\Domain\Model\Category $categoryToRemove) {
		$this->categories->detach($categoryToRemove);
	}

	/**
	 * Returns the categories
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage $categories
	 */
	public function getCategories() {
		return $this->categories;
	}

	/**
	 * Sets the categories
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $categories
	 * @return void
	 */
	public function setCategories(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $categories) {
		$this->categories = $categories;
	}

	/**
	 * Adds a Day
	 *
	 * @param \JWeiland\Events2\Domain\Model\Day $day
	 * @return void
	 */
	public function addDay(\JWeiland\Events2\Domain\Model\Day $day) {
		$this->days->attach($day);
	}

	/**
	 * Removes a Day
	 *
	 * @param \JWeiland\Events2\Domain\Model\Day $dayToRemove The Day to be removed
	 * @return void
	 */
	public function removeDay(\JWeiland\Events2\Domain\Model\Day $dayToRemove) {
		$this->days->detach($dayToRemove);
	}

	/**
	 * Returns the days
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage $days
	 */
	public function getDays() {
		return $this->days;
	}

	/**
	 * Sets the days
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $days
	 * @return void
	 */
	public function setDays(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $days) {
		$this->days = $days;
	}

	/**
	 * Returns the location
	 *
	 * @return \JWeiland\Events2\Domain\Model\Location $location
	 */
	public function getLocation() {
		return $this->location;
	}

	/**
	 * Sets the location
	 *
	 * @param \JWeiland\Events2\Domain\Model\Location $location
	 * @return void
	 */
	public function setLocation(\JWeiland\Events2\Domain\Model\Location $location = NULL) {
		$this->location = $location;
	}

	/**
	 * Returns the organizer
	 *
	 * @return \JWeiland\Events2\Domain\Model\Organizer $organizer
	 */
	public function getOrganizer() {
		return $this->organizer;
	}

	/**
	 * Sets the organizer
	 *
	 * @param \JWeiland\Events2\Domain\Model\Organizer $organizer
	 * @return void
	 */
	public function setOrganizer(\JWeiland\Events2\Domain\Model\Organizer $organizer = NULL) {
		$this->organizer = $organizer;
	}

	/**
	 * Returns the images
	 *
	 * @return array $images
	 */
	public function getImages() {
		// ObjectStorage has SplObjectHashes as key which we don't know in Fluid
		// so we convert ObjectStorage to array to get numbered keys
		$references = array();
		foreach ($this->images as $image) $references[] = $image;
		return $references;
	}

	/**
	 * Sets the images
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $images
	 * @return void
	 */
	public function setImages(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $images) {
		$this->images = $images;
	}

	/**
	 * Returns the videoLink
	 *
	 * @return \JWeiland\Events2\Domain\Model\Link $videoLink
	 */
	public function getVideoLink() {
		return $this->videoLink;
	}

	/**
	 * Sets the videoLink
	 *
	 * @param \JWeiland\Events2\Domain\Model\Link $videoLink
	 * @return void
	 */
	public function setVideoLink(\JWeiland\Events2\Domain\Model\Link $videoLink = NULL) {
		$this->videoLink = $videoLink;
	}

	/**
	 * Adds a DownloadLink
	 *
	 * @param \JWeiland\Events2\Domain\Model\Link $downloadLink
	 * @return void
	 */
	public function addDownloadLink(\JWeiland\Events2\Domain\Model\Link $downloadLink) {
		$this->days->attach($downloadLink);
	}

	/**
	 * Removes a VideoLink
	 *
	 * @param \JWeiland\Events2\Domain\Model\Link $downloadLink The VideoLink to be removed
	 * @return void
	 */
	public function removeDownloadLink(\JWeiland\Events2\Domain\Model\Link $downloadLink) {
		$this->days->detach($downloadLink);
	}

	/**
	 * Returns the DownloadLinks
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage $videoLinks
	 */
	public function getDownloadLinks() {
		return $this->downloadLinks;
	}

	/**
	 * Sets the DownloadLinks
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $downloadLinks
	 * @return void
	 */
	public function setDownloadLinks(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $downloadLinks) {
		$this->downloadLinks = $downloadLinks;
	}

	/**
	 * Returns the suitabilityCulture
	 *
	 * @return boolean $suitabilityCulture
	 */
	public function getSuitabilityCulture() {
		return $this->suitabilityCulture;
	}

	/**
	 * Sets the suitabilityCulture
	 *
	 * @param boolean $suitabilityCulture
	 * @return void
	 */
	public function setSuitabilityCulture($suitabilityCulture) {
		$this->suitabilityCulture = (bool)$suitabilityCulture;
	}

	/**
	 * Returns the boolean state of suitabilityCulture
	 *
	 * @return boolean
	 */
	public function isSuitabilityCulture() {
		return $this->getSuitabilityCulture();
	}

	/**
	 * Returns the suitabilityUser
	 *
	 * @return boolean $suitabilityUser
	 */
	public function getSuitabilityUser() {
		return $this->suitabilityUser;
	}

	/**
	 * Sets the suitabilityUser
	 *
	 * @param boolean $suitabilityUser
	 * @return void
	 */
	public function setSuitabilityUser($suitabilityUser) {
		$this->suitabilityUser = (bool)$suitabilityUser;
	}

	/**
	 * Returns the boolean state of suitabilityUser
	 *
	 * @return boolean
	 */
	public function isSuitabilityUser() {
		return $this->getSuitabilityUser();
	}

	/**
	 * Returns the suitabilityGroups
	 *
	 * @return boolean $suitabilityGroups
	 */
	public function getSuitabilityGroups() {
		return $this->suitabilityGroups;
	}

	/**
	 * Sets the suitabilityGroups
	 *
	 * @param boolean $suitabilityGroups
	 * @return void
	 */
	public function setSuitabilityGroups($suitabilityGroups) {
		$this->suitabilityGroups = (bool)$suitabilityGroups;
	}

	/**
	 * Returns the boolean state of suitabilityGroups
	 *
	 * @return boolean
	 */
	public function isSuitabilityGroups() {
		return $this->getSuitabilityGroups();
	}

	/**
	 * Returns the facebook
	 *
	 * @return boolean $facebook
	 */
	public function getFacebook() {
		return $this->facebook;
	}

	/**
	 * Sets the facebook
	 *
	 * @param boolean $facebook
	 * @return void
	 */
	public function setFacebook($facebook) {
		$this->facebook = (bool)$facebook;
	}

	/**
	 * Returns the boolean state of facebook
	 *
	 * @return boolean
	 */
	public function isFacebook() {
		return $this->getFacebook();
	}

	/**
	 * Returns the releaseDate
	 *
	 * @return \JWeiland\Events2\Domain\Model\Day $releaseDate
	 */
	public function getReleaseDate() {
		return $this->releaseDate;
	}

	/**
	 * Sets the releaseDate
	 *
	 * @param \JWeiland\Events2\Domain\Model\Day $releaseDate
	 * @return void
	 */
	public function setReleaseDate(\JWeiland\Events2\Domain\Model\Day $releaseDate) {
		$this->releaseDate = $releaseDate;
	}

	/**
	 * Returns the socialTeaser
	 *
	 * @return string $socialTeaser
	 */
	public function getSocialTeaser() {
		return $this->socialTeaser;
	}

	/**
	 * Sets the socialTeaser
	 *
	 * @param string $socialTeaser
	 * @return void
	 */
	public function setSocialTeaser($socialTeaser) {
		$this->socialTeaser = (string)$socialTeaser;
	}

	/**
	 * Returns the facebookChannel
	 *
	 * @return integer $facebookChannel
	 */
	public function getFacebookChannel() {
		return $this->facebookChannel;
	}

	/**
	 * Sets the facebookChannel
	 *
	 * @param integer $facebookChannel
	 * @return void
	 */
	public function setFacebookChannel($facebookChannel) {
		$this->facebookChannel = (int)$facebookChannel;
	}

	/**
	 * Returns the theaterDetails
	 *
	 * @return string $theaterDetails
	 */
	public function getTheaterDetails() {
		return $this->theaterDetails;
	}

	/**
	 * Sets the theaterDetails
	 *
	 * @param string $theaterDetails
	 * @return void
	 */
	public function setTheaterDetails($theaterDetails) {
		$this->theaterDetails = (string)$theaterDetails;
	}

	/**
	 * Returns the day
	 *
	 * @return \JWeiland\Events2\Domain\Model\Day $day
	 */
	public function getDay() {
		return $this->day;
	}

	/**
	 * Sets the day
	 *
	 * @param \JWeiland\Events2\Domain\Model\Day $day
	 * @return void
	 */
	public function setDay(\JWeiland\Events2\Domain\Model\Day $day) {
		$this->day = $day;
	}

}