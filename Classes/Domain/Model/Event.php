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
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Event extends AbstractEntity
{
    /**
     * EventType.
     *
     * @var string
     */
    protected $eventType = 'single';

    /**
     * Hidden.
     *
     * @var bool
     */
    protected $hidden = false;

    /**
     * TopOfList.
     *
     * @var bool
     */
    protected $topOfList = false;

    /**
     * Title.
     *
     * @var string
     * @validate NotEmpty
     */
    protected $title = '';

    /**
     * Event begin.
     *
     * @var \DateTime
     * @validate NotEmpty
     */
    protected $eventBegin;

    /**
     * EventTime.
     *
     * @var \JWeiland\Events2\Domain\Model\Time
     * @cascade remove
     * @validate NotEmpty
     */
    protected $eventTime;

    /**
     * Event end.
     *
     * @var \DateTime
     */
    protected $eventEnd;

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
     * @cascade remove
     * @lazy
     */
    protected $multipleTimes;

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
     * @cascade remove
     * @lazy
     */
    protected $differentTimes;

    /**
     * Each weeks.
     *
     * @var int
     */
    protected $eachWeeks = 0;

    /**
     * Each months.
     *
     * @var int
     */
    protected $eachMonths = 0;

    /**
     * RecurringEnd.
     *
     * @var \DateTime
     */
    protected $recurringEnd;

    /**
     * Exceptions.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\JWeiland\Events2\Domain\Model\Exception>
     * @cascade remove
     * @lazy
     */
    protected $exceptions;

    /**
     * Teaser.
     *
     * @var string
     */
    protected $teaser = '';

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
     * @cascade remove
     * @lazy
     */
    protected $ticketLink;

    /**
     * Categories.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category>
     * @validate NotEmpty
     * @lazy
     */
    protected $categories;

    /**
     * Days.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\JWeiland\Events2\Domain\Model\Day>
     * @cascade remove
     * @lazy
     */
    protected $days;

    /**
     * Location.
     *
     * @var \JWeiland\Events2\Domain\Model\Location
     * @lazy
     */
    protected $location;

    /**
     * Organizer.
     *
     * @var \JWeiland\Events2\Domain\Model\Organizer
     * @lazy
     */
    protected $organizer;

    /**
     * Images.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
     * @cascade remove
     * @lazy
     */
    protected $images;

    /**
     * VideoLink.
     *
     * @var \JWeiland\Events2\Domain\Model\Link
     * @cascade remove
     * @lazy
     */
    protected $videoLink;

    /**
     * DownloadLink.
     *
     * @var \JWeiland\Events2\Domain\Model\Link
     * @cascade remove
     * @lazy
     */
    protected $downloadLinks;

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
    }

    /**
     * Returns the eventType
     *
     * @return string $eventType
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * Sets the eventType
     *
     * @param string $eventType
     *
     * @return void
     */
    public function setEventType($eventType)
    {
        $this->eventType = (string)$eventType;
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
     *
     * @return void
     */
    public function setHidden($hidden)
    {
        $this->hidden = (bool)$hidden;
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
     *
     * @return void
     */
    public function setTopOfList($topOfList)
    {
        $this->topOfList = (bool)$topOfList;
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
     *
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = (string)$title;
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
     * Returns the eventBegin.
     *
     * @return \DateTime|null $eventBegin
     */
    public function getEventBegin()
    {
        if ($this->eventBegin instanceof \DateTime) {
            $this->eventBegin->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            return clone $this->eventBegin;
        } else {
            return null;
        }
    }

    /**
     * Sets the eventBegin.
     *
     * @param \DateTime $eventBegin
     *
     * @return void
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
     * @param Time $eventTime
     *
     * @return void
     */
    public function setEventTime(Time $eventTime = null)
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
            return (int)$diff->format('%a') + 1;
        } else {
            return 0;
        }
    }

    /**
     * Returns the eventEnd.
     *
     * @return \DateTime|null
     */
    public function getEventEnd()
    {
        if ($this->eventEnd instanceof \DateTime) {
            $this->eventEnd->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            return clone $this->eventEnd;
        } else {
            return null;
        }
    }

    /**
     * Sets the eventEnd.
     *
     * @param \DateTime $eventEnd
     *
     * @return void
     */
    public function setEventEnd(\DateTime $eventEnd = null)
    {
        $this->eventEnd = $eventEnd;
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
     *
     * @return void
     */
    public function setSameDay($sameDay)
    {
        $this->sameDay = (bool)$sameDay;
    }

    /**
     * Returns the boolean state of sameDay.
     *
     * @return void
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
     * @param ObjectStorage $multipleTimes
     *
     * @return void
     */
    public function setMultipleTimes(ObjectStorage $multipleTimes = null)
    {
        $this->multipleTimes = $multipleTimes;
    }

    /**
     * Adds a Times.
     *
     * @param Time $multipleTime
     *
     * @return void
     */
    public function addMultipleTime(Time $multipleTime = null)
    {
        $this->multipleTimes->attach($multipleTime);
    }

    /**
     * Removes a Times.
     *
     * @param Time $multipleTime The Time to be removed
     *
     * @return void
     */
    public function removeMultipleTime(Time $multipleTime)
    {
        $this->multipleTimes->detach($multipleTime);
    }

    /**
     * Returns the xth.
     *
     * @return array $xth
     */
    public function getXth()
    {
        $result = [];
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
     *
     * @return void
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
        $result = [];
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
     *
     * @return void
     */
    public function setWeekday($weekday)
    {
        $this->weekday = $weekday;
    }

    /**
     * Returns the differentTimes.
     *
     * @return ObjectStorage $differentTimes
     */
    public function getDifferentTimes()
    {
        return $this->differentTimes;
    }

    /**
     * Sets the differentTimes.
     *
     * @param ObjectStorage $differentTimes
     *
     * @return void
     */
    public function setDifferentTimes(ObjectStorage $differentTimes = null)
    {
        $this->differentTimes = $differentTimes;
    }

    /**
     * Adds a Times.
     *
     * @param Time $differentTime
     *
     * @return void
     */
    public function addDifferentTime(Time $differentTime = null)
    {
        $this->differentTimes->attach($differentTime);
    }

    /**
     * Removes a Times.
     *
     * @param Time $differentTime The Time to be removed
     *
     * @return void
     */
    public function removeDifferentTime(Time $differentTime)
    {
        $this->differentTimes->detach($differentTime);
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
     *
     * @return void
     */
    public function setEachWeeks($eachWeeks)
    {
        $this->eachWeeks = (int)$eachWeeks;
    }

    /**
     * Returns the eachMonths.
     *
     * @return int $eachMonths
     */
    public function getEachMonths()
    {
        return $this->eachMonths;
    }

    /**
     * Sets the eachMonths.
     *
     * @param int $eachMonths
     *
     * @return void
     */
    public function setEachMonths($eachMonths)
    {
        $this->eachMonths = (int)$eachMonths;
    }

    /**
     * Returns the recurringEnd
     *
     * @return \DateTime|null
     */
    public function getRecurringEnd()
    {
        if ($this->recurringEnd instanceof \DateTime) {
            $this->recurringEnd->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            return clone $this->recurringEnd;
        } else {
            return null;
        }
    }

    /**
     * Sets the recurringEnd
     *
     * @param \DateTime $recurringEnd
     *
     * @return void
     */
    public function setRecurringEnd(\DateTime $recurringEnd = null)
    {
        $this->recurringEnd = $recurringEnd;
    }

    /**
     * Adds an Exception.
     *
     * @param Exception $exception
     *
     * @return void
     */
    public function addException(Exception $exception)
    {
        $this->exceptions->attach($exception);
    }

    /**
     * Removes an Exception.
     *
     * @param Exception $exception
     *
     * @return void
     */
    public function removeException(Exception $exception)
    {
        $this->exceptions->detach($exception);
    }

    /**
     * Returns the exceptions.
     *
     * @return ObjectStorage $exceptions
     */
    public function getExceptions()
    {
        return $this->exceptions;
    }

    /**
     * Sets the Exceptions.
     *
     * @param ObjectStorage $exceptions
     *
     * @return void
     */
    public function setExceptions(ObjectStorage $exceptions)
    {
        $this->exceptions = $exceptions;
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
     *
     * @return void
     */
    public function setTeaser($teaser)
    {
        $this->teaser = (string)$teaser;
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
     *
     * @return void
     */
    public function setDetailInformations($detailInformations)
    {
        $this->detailInformations = (string)$detailInformations;
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
     *
     * @return void
     */
    public function setFreeEntry($freeEntry)
    {
        $this->freeEntry = (bool)$freeEntry;
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
     * @param Link $ticketLink
     *
     * @return void
     */
    public function setTicketLink(Link $ticketLink = null)
    {
        $this->ticketLink = $ticketLink;
    }

    /**
     * Adds a Category.
     *
     * @param Category $category
     *
     * @return void
     */
    public function addCategory(Category $category)
    {
        $this->categories->attach($category);
    }

    /**
     * Removes a Category.
     *
     * @param Category $categoryToRemove The Category to be removed
     *
     * @return void
     */
    public function removeCategory(Category $categoryToRemove)
    {
        $this->categories->detach($categoryToRemove);
    }

    /**
     * Returns the categories.
     *
     * @return ObjectStorage $categories
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
        $categoryUids = [];
        /** @var Category $category */
        foreach ($this->categories as $category) {
            $categoryUids[] = $category->getUid();
        }
        return $categoryUids;
    }

    /**
     * Sets the categories.
     *
     * @param ObjectStorage $categories
     *
     * @return void
     */
    public function setCategories(ObjectStorage $categories)
    {
        $this->categories = $categories;
    }

    /**
     * Adds a Day.
     *
     * @param Day $day
     *
     * @return void
     */
    public function addDay(Day $day)
    {
        $this->days->attach($day);
    }

    /**
     * Removes a Day.
     *
     * @param Day $dayToRemove The Day to be removed
     *
     * @return void
     */
    public function removeDay(Day $dayToRemove)
    {
        $this->days->detach($dayToRemove);
    }

    /**
     * Returns the days.
     *
     * @return ObjectStorage $days
     */
    public function getDays()
    {
        return $this->days;
    }

    /**
     * Sets the days.
     *
     * @param ObjectStorage $days
     *
     * @return void
     */
    public function setDays(ObjectStorage $days)
    {
        $this->days = $days;
    }

    /**
     * Returns the location.
     *
     * @return Location $location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Sets the location.
     *
     * @param Location $location
     *
     * @return void
     */
    public function setLocation(Location $location = null)
    {
        $this->location = $location;
    }

    /**
     * Returns the organizer.
     *
     * @return Organizer $organizer
     */
    public function getOrganizer()
    {
        return $this->organizer;
    }

    /**
     * Sets the organizer.
     *
     * @param Organizer $organizer
     *
     * @return void
     */
    public function setOrganizer(Organizer $organizer = null)
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
        $references = [];
        foreach ($this->images as $image) {
            $references[] = $image;
        }

        return $references;
    }

    /**
     * Sets the images.
     *
     * @param ObjectStorage $images
     *
     * @return void
     */
    public function setImages(ObjectStorage $images)
    {
        $this->images = $images;
    }

    /**
     * Returns the videoLink.
     *
     * @return Link $videoLink
     */
    public function getVideoLink()
    {
        return $this->videoLink;
    }

    /**
     * Sets the videoLink.
     *
     * @param Link $videoLink
     *
     * @return void
     */
    public function setVideoLink(Link $videoLink = null)
    {
        $this->videoLink = $videoLink;
    }

    /**
     * Returns the DownloadLinks.
     *
     * @return Link
     */
    public function getDownloadLinks()
    {
        return $this->downloadLinks;
    }

    /**
     * Sets the DownloadLinks.
     *
     * @param Link $downloadLinks
     *
     * @return void
     */
    public function setDownloadLinks(Link $downloadLinks)
    {
        $this->downloadLinks = $downloadLinks;
    }
}
