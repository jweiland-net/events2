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
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Event extends AbstractEntity
{
    /**
     * Hidden.
     *
     * @var bool
     */
    protected $hidden = false;

    /**
     * Title.
     *
     * @var string
     * @validate NotEmpty
     */
    protected $title = '';

    /**
     * TopOfList.
     *
     * @var bool
     */
    protected $topOfList = false;

    /**
     * Teaser.
     *
     * @var string
     */
    protected $teaser = '';

    /**
     * Event begin.
     *
     * @var \DateTime
     * @validate NotEmpty
     */
    protected $eventBegin = null;

    /**
     * EventTime.
     *
     * @var \JWeiland\Events2\Domain\Model\Time
     * @validate NotEmpty
     */
    protected $eventTime = null;

    /**
     * Event end.
     *
     * @var \DateTime
     */
    protected $eventEnd = null;

    /**
     * Recurring event.
     *
     * @var bool
     */
    protected $recurringEvent = false;

    /**
     * Same day.
     *
     * @var bool
     */
    protected $sameDay = false;

    /**
     * MultipleTimes.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\JWeiland\Events2\Domain\Model\Time>
     */
    protected $multipleTimes = null;

    /**
     * xTh.
     *
     * @var int
     */
    protected $xth = 0;

    /**
     * Weekday.
     *
     * @var int
     */
    protected $weekday = 0;

    /**
     * differentTimes.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\JWeiland\Events2\Domain\Model\Time>
     * @lazy
     */
    protected $differentTimes = null;

    /**
     * Each weeks.
     *
     * @var int
     */
    protected $eachWeeks = 0;

    /**
     * Exceptions.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\JWeiland\Events2\Domain\Model\Exception>
     * @lazy
     */
    protected $exceptions = null;

    /**
     * Detail informations.
     *
     * @var string
     */
    protected $detailInformations = '';

    /**
     * free entry.
     *
     * @var bool
     */
    protected $freeEntry = false;

    /**
     * Ticket link.
     *
     * @var \JWeiland\Events2\Domain\Model\Link
     * @lazy
     */
    protected $ticketLink = null;

    /**
     * Categories.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category>
     * @validate NotEmpty
     * @lazy
     */
    protected $categories = null;

    /**
     * Days.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\JWeiland\Events2\Domain\Model\Day>
     * @lazy
     */
    protected $days = null;

    /**
     * Location.
     *
     * @var \JWeiland\Events2\Domain\Model\Location
     * @validate NotEmpty
     * @lazy
     */
    protected $location = null;

    /**
     * Organizer.
     *
     * @var \JWeiland\Events2\Domain\Model\Organizer
     * @validate NotEmpty
     * @lazy
     */
    protected $organizer = null;

    /**
     * Images.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
     */
    protected $images = null;

    /**
     * VideoLink.
     *
     * @var \JWeiland\Events2\Domain\Model\Link
     * @cascade remove
     */
    protected $videoLink = null;

    /**
     * VideoLink.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\JWeiland\Events2\Domain\Model\Link>
     * @cascade remove
     * @lazy
     */
    protected $downloadLinks = null;

    /**
     * Facebook.
     *
     * @var bool
     */
    protected $facebook = false;

    /**
     * ReleaseDate.
     *
     * @var \DateTime
     */
    protected $releaseDate = null;

    /**
     * SocialTeaser.
     *
     * @var string
     */
    protected $socialTeaser = '';

    /**
     * facebookChannel.
     *
     * @var int
     */
    protected $facebookChannel = 0;

    /**
     * Theater Details.
     *
     * @var string
     */
    protected $theaterDetails = '';

    /**
     * Day.
     *
     * @var \JWeiland\Events2\Domain\Model\Day
     */
    protected $day = null;

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
        $this->multipleTimes = new ObjectStorage();
        $this->differentTimes = new ObjectStorage();
        $this->exceptions = new ObjectStorage();
        $this->categories = new ObjectStorage();
        $this->days = new ObjectStorage();
        $this->images = new ObjectStorage();
        $this->downloadLinks = new ObjectStorage();
    }

    /**
     * Returns the hidden.
     *
     * @return bool $hidden
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Sets the hidden.
     *
     * @param bool $hidden
     */
    public function setHidden($hidden)
    {
        $this->hidden = (bool) $hidden;
    }

    /**
     * Returns the title.
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the title.
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = (string) $title;
    }

    /**
     * Returns the topOfList.
     *
     * @return bool $topOfList
     */
    public function getTopOfList()
    {
        return $this->topOfList;
    }

    /**
     * Sets the topOfList.
     *
     * @param bool $topOfList
     */
    public function setTopOfList($topOfList)
    {
        $this->topOfList = (bool) $topOfList;
    }

    /**
     * Returns the boolean state of topOfList.
     *
     * @return bool
     */
    public function isTopOfList()
    {
        return $this->getTopOfList();
    }

    /**
     * Returns the teaser.
     *
     * @return string $teaser
     */
    public function getTeaser()
    {
        return $this->teaser;
    }

    /**
     * Sets the teaser.
     *
     * @param string $teaser
     */
    public function setTeaser($teaser)
    {
        $this->teaser = (string) $teaser;
    }

    /**
     * Returns the eventBegin.
     *
     * @return \DateTime $eventBegin
     */
    public function getEventBegin()
    {
        return $this->eventBegin;
    }

    /**
     * Sets the eventBegin.
     *
     * @param \DateTime $eventBegin
     */
    public function setEventBegin(\DateTime $eventBegin = null)
    {
        $this->eventBegin = $eventBegin;
    }

    /**
     * Returns the event_time.
     *
     * @return \JWeiland\Events2\Domain\Model\Time $time
     */
    public function getEventTime()
    {
        return $this->eventTime;
    }

    /**
     * Sets the event_time.
     *
     * @param \JWeiland\Events2\Domain\Model\Time $eventTime
     */
    public function setEventTime(\JWeiland\Events2\Domain\Model\Time $eventTime = null)
    {
        $this->eventTime = $eventTime;
    }

    /**
     * Returns the days of an event if it takes several days.
     *
     * @return int $durationInDays
     */
    public function getDaysOfEventsTakingDays()
    {
        $dateTimeUtility = new DateTimeUtility();

        $eventBegin = $dateTimeUtility->standardizeDateTimeObject($this->getEventBegin());
        $eventEnd = $dateTimeUtility->standardizeDateTimeObject($this->getEventEnd());
        if (!empty($eventEnd) && $eventEnd != $eventBegin) {
            $diff = $eventBegin->diff($eventEnd);
            // Example: 20.01.2013 - 23.01.2013 = 4 days but diff shows 3. So we have to add 1 day here
            return (int) $diff->format('%a') + 1;
        } else {
            return 0;
        }
    }

    /**
     * Returns the eventEnd.
     *
     * @return \DateTime $eventEnd
     */
    public function getEventEnd()
    {
        return $this->eventEnd;
    }

    /**
     * Sets the eventEnd.
     *
     * @param \DateTime $eventEnd
     */
    public function setEventEnd(\DateTime $eventEnd = null)
    {
        $this->eventEnd = $eventEnd;
    }

    /**
     * Returns the recurringEvent.
     *
     * @return bool $recurringEvent
     */
    public function getRecurringEvent()
    {
        if (!$this->recurringEvent) {
            // if an event has an EventEnd-Date set, then it is automatically an recurring event
            if ($this->getEventEnd()) {
                return true;
            } else {
                return false;
            }
        } else {
            return $this->recurringEvent;
        }
    }

    /**
     * Sets the recurringEvent.
     *
     * @param bool $recurringEvent
     */
    public function setRecurringEvent($recurringEvent)
    {
        $this->recurringEvent = (bool) $recurringEvent;
    }

    /**
     * Returns the boolean state of recurringEvent.
     *
     * @return bool
     */
    public function isRecurringEvent()
    {
        return $this->getRecurringEvent();
    }

    /**
     * Returns the sameDay.
     *
     * @return bool $sameDay
     */
    public function getSameDay()
    {
        return $this->sameDay;
    }

    /**
     * Sets the sameDay.
     *
     * @param bool $sameDay
     */
    public function setSameDay($sameDay)
    {
        $this->sameDay = (bool) $sameDay;
    }

    /**
     * Returns the boolean state of sameDay.
     *
     * @return bool
     */
    public function isSameDay()
    {
        $this->getSameDay();
    }

    /**
     * Returns the multipleTimes.
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage $time
     */
    public function getMultipleTimes()
    {
        return $this->multipleTimes;
    }

    /**
     * Sets the multipleTimes.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $multipleTimes
     */
    public function setMultipleTimes(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $multipleTimes = null)
    {
        $this->multipleTimes = $multipleTimes;
    }

    /**
     * Returns the xth.
     *
     * @return array $xth
     */
    public function getXth()
    {
        $result = array();
        $items = $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['xth']['config']['items'];
        foreach ($items as $key => $item) {
            $result[$item[1]] = $this->xth & pow(2, $key);
        }

        return $result;
    }

    /**
     * Sets the xth.
     *
     * @param int $xth
     */
    public function setXth($xth)
    {
        $this->xth = $xth;
    }

    /**
     * Returns the weekday.
     *
     * @return array $weekday
     */
    public function getWeekday()
    {
        $result = array();
        $items = $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['weekday']['config']['items'];
        foreach ($items as $key => $item) {
            $result[$item[1]] = $this->weekday & pow(2, $key);
        }

        return $result;
    }

    /**
     * Sets the weekday.
     *
     * @param int $weekday
     */
    public function setWeekday($weekday)
    {
        $this->weekday = $weekday;
    }

    /**
     * Adds a Times.
     *
     * @param \JWeiland\Events2\Domain\Model\Time $differentTime
     */
    public function addDifferentTime(\JWeiland\Events2\Domain\Model\Time $differentTime = null)
    {
        $this->differentTimes->attach($differentTime);
    }

    /**
     * Removes a Times.
     *
     * @param \JWeiland\Events2\Domain\Model\Time $differentTime The Time to be removed
     */
    public function removeDifferentTime(\JWeiland\Events2\Domain\Model\Time $differentTime)
    {
        $this->differentTimes->detach($differentTime);
    }

    /**
     * Returns the differentTimes.
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage $differentTimes
     */
    public function getDifferentTimes()
    {
        return $this->differentTimes;
    }

    /**
     * Sets the differentTimes.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $differentTimes
     */
    public function setDifferentTimes(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $differentTimes = null)
    {
        $this->differentTimes = $differentTimes;
    }

    /**
     * Returns the eachWeeks.
     *
     * @return int $eachWeeks
     */
    public function getEachWeeks()
    {
        return $this->eachWeeks;
    }

    /**
     * Sets the eachWeeks.
     *
     * @param int $eachWeeks
     */
    public function setEachWeeks($eachWeeks)
    {
        $this->eachWeeks = $eachWeeks;
    }

    /**
     * Adds an Exception.
     *
     * @param \JWeiland\Events2\Domain\Model\Exception $exception
     */
    public function addException(\JWeiland\Events2\Domain\Model\Exception $exception)
    {
        $this->exceptions->attach($exception);
    }

    /**
     * Removes an Exception.
     *
     * @param \JWeiland\Events2\Domain\Model\Exception $exception
     */
    public function removeException(\JWeiland\Events2\Domain\Model\Exception $exception)
    {
        $this->exceptions->detach($exception);
    }

    /**
     * Returns the exceptions.
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage $exceptions
     */
    public function getExceptions()
    {
        return $this->exceptions;
    }

    /**
     * Returns the exceptions in future.
     *
     * @return array $exceptions
     */
    public function getFutureExceptions()
    {
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
     * Sets the Exceptions.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $exceptions
     */
    public function setExceptions(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $exceptions)
    {
        $this->exceptions = $exceptions;
    }

    /**
     * Returns the detailInformations.
     *
     * @return string $detailInformations
     */
    public function getDetailInformations()
    {
        return $this->detailInformations;
    }

    /**
     * Sets the detailInformations.
     *
     * @param string $detailInformations
     */
    public function setDetailInformations($detailInformations)
    {
        $this->detailInformations = (string) $detailInformations;
    }

    /**
     * Returns the freeEntry.
     *
     * @return bool $freeEntry
     */
    public function getFreeEntry()
    {
        return $this->freeEntry;
    }

    /**
     * Sets the freeEntry.
     *
     * @param bool $freeEntry
     */
    public function setFreeEntry($freeEntry)
    {
        $this->freeEntry = (bool) $freeEntry;
    }

    /**
     * Returns the boolean state of freeEntry.
     *
     * @return bool
     */
    public function isFreeEntry()
    {
        return $this->getFreeEntry();
    }

    /**
     * Returns the ticketLink.
     *
     * @return \JWeiland\Events2\Domain\Model\Link $ticketLink
     */
    public function getTicketLink()
    {
        return $this->ticketLink;
    }

    /**
     * Sets the ticketLink.
     *
     * @param \JWeiland\Events2\Domain\Model\Link $ticketLink
     */
    public function setTicketLink(\JWeiland\Events2\Domain\Model\Link $ticketLink = null)
    {
        $this->ticketLink = $ticketLink;
    }

    /**
     * Adds a Category.
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\Category $category
     */
    public function addCategory(\TYPO3\CMS\Extbase\Domain\Model\Category $category)
    {
        $this->categories->attach($category);
    }

    /**
     * Removes a Category.
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\Category $categoryToRemove The Category to be removed
     */
    public function removeCategory(\TYPO3\CMS\Extbase\Domain\Model\Category $categoryToRemove)
    {
        $this->categories->detach($categoryToRemove);
    }

    /**
     * Returns the categories.
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage $categories
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Returns the category UIDs as array
     * This is a helper method
     *
     * @return array
     */
    public function getCategoryUids()
    {
        $categoryUids = array();
        /** @var Category $category */
        foreach ($this->categories as $category) {
            $categoryUids[] = $category->getUid();
        }
        return $categoryUids;
    }

    /**
     * Sets the categories.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $categories
     */
    public function setCategories(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $categories)
    {
        $this->categories = $categories;
    }

    /**
     * Adds a Day.
     *
     * @param \JWeiland\Events2\Domain\Model\Day $day
     */
    public function addDay(\JWeiland\Events2\Domain\Model\Day $day)
    {
        $this->days->attach($day);
    }

    /**
     * Removes a Day.
     *
     * @param \JWeiland\Events2\Domain\Model\Day $dayToRemove The Day to be removed
     */
    public function removeDay(\JWeiland\Events2\Domain\Model\Day $dayToRemove)
    {
        $this->days->detach($dayToRemove);
    }

    /**
     * Returns the days.
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage $days
     */
    public function getDays()
    {
        return $this->days;
    }

    /**
     * Sets the days.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $days
     */
    public function setDays(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $days)
    {
        $this->days = $days;
    }

    /**
     * Returns the location.
     *
     * @return \JWeiland\Events2\Domain\Model\Location $location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Sets the location.
     *
     * @param \JWeiland\Events2\Domain\Model\Location $location
     */
    public function setLocation(\JWeiland\Events2\Domain\Model\Location $location = null)
    {
        $this->location = $location;
    }

    /**
     * Returns the organizer.
     *
     * @return \JWeiland\Events2\Domain\Model\Organizer $organizer
     */
    public function getOrganizer()
    {
        return $this->organizer;
    }

    /**
     * Sets the organizer.
     *
     * @param \JWeiland\Events2\Domain\Model\Organizer $organizer
     */
    public function setOrganizer(\JWeiland\Events2\Domain\Model\Organizer $organizer = null)
    {
        $this->organizer = $organizer;
    }

    /**
     * Returns the images.
     *
     * @return array $images
     */
    public function getImages()
    {
        // ObjectStorage has SplObjectHashes as key which we don't know in Fluid
        // so we convert ObjectStorage to array to get numbered keys
        $references = array();
        foreach ($this->images as $image) {
            $references[] = $image;
        }

        return $references;
    }

    /**
     * Sets the images.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $images
     */
    public function setImages(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $images)
    {
        $this->images = $images;
    }

    /**
     * Returns the videoLink.
     *
     * @return \JWeiland\Events2\Domain\Model\Link $videoLink
     */
    public function getVideoLink()
    {
        return $this->videoLink;
    }

    /**
     * Sets the videoLink.
     *
     * @param \JWeiland\Events2\Domain\Model\Link $videoLink
     */
    public function setVideoLink(\JWeiland\Events2\Domain\Model\Link $videoLink = null)
    {
        $this->videoLink = $videoLink;
    }

    /**
     * Adds a DownloadLink.
     *
     * @param \JWeiland\Events2\Domain\Model\Link $downloadLink
     */
    public function addDownloadLink(\JWeiland\Events2\Domain\Model\Link $downloadLink)
    {
        $this->days->attach($downloadLink);
    }

    /**
     * Removes a VideoLink.
     *
     * @param \JWeiland\Events2\Domain\Model\Link $downloadLink The VideoLink to be removed
     */
    public function removeDownloadLink(\JWeiland\Events2\Domain\Model\Link $downloadLink)
    {
        $this->days->detach($downloadLink);
    }

    /**
     * Returns the DownloadLinks.
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage $videoLinks
     */
    public function getDownloadLinks()
    {
        return $this->downloadLinks;
    }

    /**
     * Sets the DownloadLinks.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $downloadLinks
     */
    public function setDownloadLinks(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $downloadLinks)
    {
        $this->downloadLinks = $downloadLinks;
    }

    /**
     * Returns the facebook.
     *
     * @return bool $facebook
     */
    public function getFacebook()
    {
        return $this->facebook;
    }

    /**
     * Sets the facebook.
     *
     * @param bool $facebook
     */
    public function setFacebook($facebook)
    {
        $this->facebook = (bool) $facebook;
    }

    /**
     * Returns the boolean state of facebook.
     *
     * @return bool
     */
    public function isFacebook()
    {
        return $this->getFacebook();
    }

    /**
     * Returns the releaseDate.
     *
     * @return \JWeiland\Events2\Domain\Model\Day $releaseDate
     */
    public function getReleaseDate()
    {
        return $this->releaseDate;
    }

    /**
     * Sets the releaseDate.
     *
     * @param \JWeiland\Events2\Domain\Model\Day $releaseDate
     */
    public function setReleaseDate(\JWeiland\Events2\Domain\Model\Day $releaseDate)
    {
        $this->releaseDate = $releaseDate;
    }

    /**
     * Returns the socialTeaser.
     *
     * @return string $socialTeaser
     */
    public function getSocialTeaser()
    {
        return $this->socialTeaser;
    }

    /**
     * Sets the socialTeaser.
     *
     * @param string $socialTeaser
     */
    public function setSocialTeaser($socialTeaser)
    {
        $this->socialTeaser = (string) $socialTeaser;
    }

    /**
     * Returns the facebookChannel.
     *
     * @return int $facebookChannel
     */
    public function getFacebookChannel()
    {
        return $this->facebookChannel;
    }

    /**
     * Sets the facebookChannel.
     *
     * @param int $facebookChannel
     */
    public function setFacebookChannel($facebookChannel)
    {
        $this->facebookChannel = (int) $facebookChannel;
    }

    /**
     * Returns the theaterDetails.
     *
     * @return string $theaterDetails
     */
    public function getTheaterDetails()
    {
        return $this->theaterDetails;
    }

    /**
     * Sets the theaterDetails.
     *
     * @param string $theaterDetails
     */
    public function setTheaterDetails($theaterDetails)
    {
        $this->theaterDetails = (string) $theaterDetails;
    }

    /**
     * Returns the day.
     *
     * @return \JWeiland\Events2\Domain\Model\Day $day
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * Sets the day.
     *
     * @param \JWeiland\Events2\Domain\Model\Day $day
     */
    public function setDay(\JWeiland\Events2\Domain\Model\Day $day)
    {
        $this->day = $day;
    }
}
