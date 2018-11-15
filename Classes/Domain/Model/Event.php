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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * This class contains all getter and setters for an Event.
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
     * DownloadLinks.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\JWeiland\Events2\Domain\Model\Link>
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
     * Initializes all ObjectStorage properties.
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
     * Returns the eventType
     */
    public function getEventType(): string
    {
        return $this->eventType;
    }

    /**
     * Sets the eventType
     *
     * @param string $eventType
     */
    public function setEventType(string $eventType)
    {
        $this->eventType = $eventType;
    }

    /**
     * Returns the hidden.
     */
    public function getHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Sets the hidden.
     *
     * @param bool $hidden
     */
    public function setHidden(bool $hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * Returns the topOfList.
     */
    public function getTopOfList(): bool
    {
        return $this->topOfList;
    }

    /**
     * Returns the boolean state of topOfList.
     *
     * @return bool
     */
    public function isTopOfList(): bool
    {
        return (bool)$this->getTopOfList();
    }

    /**
     * Sets the topOfList.
     *
     * @param bool $topOfList
     */
    public function setTopOfList(bool $topOfList)
    {
        $this->topOfList = $topOfList;
    }

    /**
     * Returns the title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets the title.
     *
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * Returns the eventBegin.
     *
     * @return \DateTime|null
     */
    public function getEventBegin()
    {
        if ($this->eventBegin instanceof \DateTime) {
            if ($this->eventBegin->timezone_type !== 3) {
                $this->eventBegin->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            }
            return clone $this->eventBegin;
        } else {
            return null;
        }
    }

    /**
     * Sets the eventBegin.
     *
     * @param \DateTime|null $eventBegin
     */
    public function setEventBegin(\DateTime $eventBegin = null)
    {
        $this->eventBegin = $eventBegin;
    }

    /**
     * Returns the event_time.
     *
     * @return Time|null
     */
    public function getEventTime()
    {
        return $this->eventTime;
    }

    /**
     * Sets the event_time.
     *
     * @param Time|null $eventTime
     */
    public function setEventTime(Time $eventTime = null)
    {
        $this->eventTime = $eventTime;
    }

    /**
     * Returns the days of an event if it takes several days.
     */
    public function getDaysOfEventsTakingDays(): int
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
            if ($this->eventEnd->timezone_type !== 3) {
                $this->eventEnd->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            }
            return clone $this->eventEnd;
        } else {
            return null;
        }
    }

    /**
     * Sets the eventEnd.
     *
     * @param \DateTime|null $eventEnd
     */
    public function setEventEnd(\DateTime $eventEnd = null)
    {
        $this->eventEnd = $eventEnd;
    }

    /**
     * Returns the sameDay.
     */
    public function getSameDay(): bool
    {
        return $this->sameDay;
    }

    /**
     * Sets the sameDay.
     *
     * @param bool $sameDay
     */
    public function setSameDay(bool $sameDay)
    {
        $this->sameDay = $sameDay;
    }

    /**
     * Returns the boolean state of sameDay.
     */
    public function isSameDay(): bool
    {
        return (bool)$this->getSameDay();
    }

    /**
     * Returns the multipleTimes.
     */
    public function getMultipleTimes(): ObjectStorage
    {
        return $this->multipleTimes;
    }

    /**
     * Sets the multipleTimes.
     *
     * @param ObjectStorage $multipleTimes
     */
    public function setMultipleTimes(ObjectStorage $multipleTimes)
    {
        $this->multipleTimes = $multipleTimes;
    }

    /**
     * Adds a Times.
     *
     * @param Time $multipleTime
     */
    public function addMultipleTime(Time $multipleTime)
    {
        $this->multipleTimes->attach($multipleTime);
    }

    /**
     * Removes a Times.
     *
     * @param Time $multipleTime The Time to be removed
     */
    public function removeMultipleTime(Time $multipleTime)
    {
        $this->multipleTimes->detach($multipleTime);
    }

    /**
     * Returns the xth.
     */
    public function getXth(): array
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
     */
    public function setXth(int $xth)
    {
        $this->xth = $xth;
    }

    /**
     * Returns the weekday.
     */
    public function getWeekday(): array
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
     */
    public function setWeekday(int $weekday)
    {
        $this->weekday = $weekday;
    }

    /**
     * Returns the differentTimes.
     *
     * @return ObjectStorage|Time[]
     */
    public function getDifferentTimes(): ObjectStorage
    {
        return $this->differentTimes;
    }

    /**
     * Sets the differentTimes.
     *
     * @param ObjectStorage $differentTimes
     */
    public function setDifferentTimes(ObjectStorage $differentTimes)
    {
        $this->differentTimes = $differentTimes;
    }

    /**
     * Adds a Times.
     *
     * @param Time $differentTime
     */
    public function addDifferentTime(Time $differentTime)
    {
        $this->differentTimes->attach($differentTime);
    }

    /**
     * Removes a Times.
     *
     * @param Time $differentTime The Time to be removed
     */
    public function removeDifferentTime(Time $differentTime)
    {
        $this->differentTimes->detach($differentTime);
    }

    /**
     * Returns the eachWeeks.
     */
    public function getEachWeeks(): int
    {
        return $this->eachWeeks;
    }

    /**
     * Sets the eachWeeks.
     *
     * @param int $eachWeeks
     */
    public function setEachWeeks(int $eachWeeks)
    {
        $this->eachWeeks = $eachWeeks;
    }

    /**
     * Returns the eachMonths.
     */
    public function getEachMonths(): int
    {
        return $this->eachMonths;
    }

    /**
     * Sets the eachMonths.
     *
     * @param int $eachMonths
     */
    public function setEachMonths(int $eachMonths)
    {
        $this->eachMonths = $eachMonths;
    }

    /**
     * Returns the recurringEnd
     *
     * @return \DateTime|null
     */
    public function getRecurringEnd()
    {
        if ($this->recurringEnd instanceof \DateTime) {
            if ($this->recurringEnd->timezone_type !== 3) {
                $this->recurringEnd->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            }
            return clone $this->recurringEnd;
        } else {
            return null;
        }
    }

    /**
     * Sets the recurringEnd
     *
     * @param \DateTime|null $recurringEnd
     */
    public function setRecurringEnd(\DateTime $recurringEnd = null)
    {
        $this->recurringEnd = $recurringEnd;
    }

    /**
     * Adds an Exception.
     *
     * @param Exception $exception
     */
    public function addException(Exception $exception)
    {
        $this->exceptions->attach($exception);
    }

    /**
     * Removes an Exception.
     *
     * @param Exception $exception
     */
    public function removeException(Exception $exception)
    {
        $this->exceptions->detach($exception);
    }

    /**
     * Returns the exceptions.
     * Additionally you can filter exceptions by type
     * Types: add, remove, time, info
     *
     * @param string $filterByType
     * @return ObjectStorage|Exception[]
     */
    public function getExceptions($filterByType = ''): ObjectStorage
    {
        $exceptions = new ObjectStorage();
        $filterByType = strtolower($filterByType);

        if (empty($filterByType)) {
            $exceptions = $this->exceptions;
        } else {
            foreach ($this->exceptions as $exception) {
                $exceptionType = strtolower($exception->getExceptionType());
                if ($exceptionType === $filterByType) {
                    $exceptions->attach($exception);
                }
            }
        }
        return $exceptions;
    }

    /**
     * Sets the Exceptions.
     *
     * @param ObjectStorage $exceptions
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
    public function getTeaser(): string
    {
        return $this->teaser;
    }

    /**
     * Sets the teaser.
     *
     * @param string $teaser
     */
    public function setTeaser(string $teaser)
    {
        $this->teaser = $teaser;
    }

    /**
     * Returns the detailInformations.
     */
    public function getDetailInformations(): string
    {
        return $this->detailInformations;
    }

    /**
     * Sets the detailInformations.
     *
     * @param string $detailInformations
     */
    public function setDetailInformations(string $detailInformations)
    {
        $this->detailInformations = $detailInformations;
    }

    /**
     * Returns the freeEntry.
     */
    public function getFreeEntry(): bool
    {
        return $this->freeEntry;
    }

    /**
     * Sets the freeEntry.
     *
     * @param bool $freeEntry
     */
    public function setFreeEntry(bool $freeEntry)
    {
        $this->freeEntry = $freeEntry;
    }

    /**
     * Returns the boolean state of freeEntry.
     */
    public function isFreeEntry(): bool
    {
        return $this->getFreeEntry();
    }

    /**
     * Returns the ticketLink.
     *
     * @return Link|null $ticketLink
     */
    public function getTicketLink()
    {
        return $this->ticketLink;
    }

    /**
     * Sets the ticketLink.
     *
     * @param Link $ticketLink
     */
    public function setTicketLink(Link $ticketLink = null)
    {
        $this->ticketLink = $ticketLink;
    }

    /**
     * Adds a Category.
     *
     * @param Category $category
     */
    public function addCategory(Category $category)
    {
        $this->categories->attach($category);
    }

    /**
     * Removes a Category.
     *
     * @param Category $categoryToRemove The Category to be removed
     */
    public function removeCategory(Category $categoryToRemove)
    {
        $this->categories->detach($categoryToRemove);
    }

    /**
     * Returns the categories.
     */
    public function getCategories(): ObjectStorage
    {
        return $this->categories;
    }

    /**
     * Returns the category UIDs as array
     * This is a helper method
     */
    public function getCategoryUids(): array
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
     */
    public function setCategories(ObjectStorage $categories)
    {
        $this->categories = $categories;
    }

    /**
     * Adds a Day.
     *
     * @param Day $day
     */
    public function addDay(Day $day)
    {
        $this->days->attach($day);
    }

    /**
     * Removes a Day.
     *
     * @param Day $dayToRemove The Day to be removed
     */
    public function removeDay(Day $dayToRemove)
    {
        $this->days->detach($dayToRemove);
    }

    /**
     * Returns the days.
     *
     * @return ObjectStorage|Day[] $days
     */
    public function getDays(): ObjectStorage
    {
        return $this->days;
    }

    /**
     * Returns grouped and sorted days of today and future.
     *
     * @return array|\DateTime[] $days
     */
    public function getFutureDatesGroupedAndSorted(): array
    {
        $dateTimeUtility = GeneralUtility::makeInstance(DateTimeUtility::class);
        $today = $dateTimeUtility->standardizeDateTimeObject(new \DateTime());

        $futureDates = [];
        foreach ($this->getDays() as $day) {
            if ($day->getDayTime() > $today) {
                $futureDay = clone $day;
                $futureDates[$futureDay->getDay()->format('U')] = $futureDay->getDay();
            }
        }
        ksort($futureDates);
        return $futureDates;
    }

    /**
     * Returns grouped and sorted days of today and future.
     * This method also returns days which are marked as removed (is_removed=1).
     *
     * @return array|\DateTime[] $days
     */
    public function getFutureDatesIncludingRemovedGroupedAndSorted(): array
    {
        $dateTimeUtility = GeneralUtility::makeInstance(DateTimeUtility::class);
        $today = $dateTimeUtility->standardizeDateTimeObject(new \DateTime());

        $futureDates = $this->getFutureDatesGroupedAndSorted();
        foreach ($this->getExceptions('remove') as $exception) {
            if ($exception->getExceptionDate() > $today) {
                $exceptionDate = clone $exception->getExceptionDate();
                $futureDates[$exceptionDate->format('U')] = $exceptionDate;
            }
        }

        ksort($futureDates);
        return $futureDates;
    }

    /**
     * Sets the days.
     *
     * @param ObjectStorage $days
     */
    public function setDays(ObjectStorage $days)
    {
        $this->days = $days;
    }

    /**
     * Returns the location.
     * Since version 2.3.1 this property can be (not must be) required
     *
     * @return Location|null $location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Sets the location.
     *
     * @param Location|null $location
     */
    public function setLocation(Location $location = null)
    {
        $this->location = $location;
    }

    /**
     * Returns the organizer.
     * Since version 2.3.1 this property can be (not must be) required
     *
     * @return Organizer|null
     */
    public function getOrganizer()
    {
        return $this->organizer;
    }

    /**
     * Sets the organizer.
     *
     * @param Organizer|null $organizer
     */
    public function setOrganizer(Organizer $organizer = null)
    {
        $this->organizer = $organizer;
    }

    /**
     * Returns the images.
     */
    public function getImages(): array
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
     */
    public function setImages(ObjectStorage $images)
    {
        $this->images = $images;
    }

    /**
     * Returns the videoLink.
     *
     * @return Link|null $videoLink
     */
    public function getVideoLink()
    {
        return $this->videoLink;
    }

    /**
     * Sets the videoLink.
     *
     * @param Link $videoLink
     */
    public function setVideoLink(Link $videoLink = null)
    {
        $this->videoLink = $videoLink;
    }

    /**
     * Returns the DownloadLinks.
     *
     * @return ObjectStorage
     */
    public function getDownloadLinks(): ObjectStorage
    {
        return $this->downloadLinks;
    }

    /**
     * Sets the DownloadLinks.
     *
     * @param ObjectStorage $downloadLinks
     */
    public function setDownloadLinks(ObjectStorage $downloadLinks)
    {
        $this->downloadLinks = $downloadLinks;
    }

    /**
     * Add download link
     *
     * @param Link $downloadLink
     */
    public function addDownloadLink(Link $downloadLink)
    {
        $this->downloadLinks->attach($downloadLink);
    }

    /**
     * Remove download link
     *
     * @param Link $downloadLink
     */
    public function removeDownloadLink(Link $downloadLink)
    {
        $this->downloadLinks->detach($downloadLink);
    }
}
