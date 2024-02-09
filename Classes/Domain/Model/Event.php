<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Model;

use JWeiland\Events2\Domain\Factory\TimeFactory;
use JWeiland\Events2\Domain\Repository\UserRepository;
use JWeiland\Events2\Domain\Traits\Typo3PropertiesTrait;
use JWeiland\Events2\Domain\Validator\CategoryMandatoryValidator;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * This class contains all getter and setters for an Event.
 */
class Event extends AbstractEntity
{
    use Typo3PropertiesTrait;

    protected string $eventType = 'single';

    protected bool $topOfList = false;

    #[Extbase\Validate(['validator' => 'NotEmpty'])]
    protected string $title = '';

    protected string $pathSegment = '';

    #[Extbase\Validate(['validator' => 'NotEmpty'])]
    protected ?\DateTimeImmutable $eventBegin = null;

    #[Extbase\ORM\Cascade(['value' => 'remove'])]
    protected ?Time $eventTime = null;

    protected ?\DateTimeImmutable $eventEnd = null;

    protected bool $sameDay = false;

    /**
     * @var ObjectStorage<Time>
     */
    #[Extbase\ORM\Cascade(['value' => 'remove'])]
    #[Extbase\ORM\Lazy]
    protected ObjectStorage $multipleTimes;

    protected int $xth = 0;

    protected int $weekday = 0;

    /**
     * @var ObjectStorage<Time>
     */
    #[Extbase\ORM\Cascade(['value' => 'remove'])]
    #[Extbase\ORM\Lazy]
    protected ObjectStorage $differentTimes;

    protected int $eachWeeks = 0;

    protected int $eachMonths = 0;

    protected ?\DateTimeImmutable $recurringEnd = null;

    /**
     * @var ObjectStorage<Exception>
     */
    #[Extbase\ORM\Cascade(['value' => 'remove'])]
    #[Extbase\ORM\Lazy]
    protected ObjectStorage $exceptions;

    protected string $teaser = '';

    protected string $detailInformation = '';

    protected bool $freeEntry = false;

    #[Extbase\ORM\Cascade(['value' => 'remove'])]
    protected ?Link $ticketLink = null;

    /**
     * @var ObjectStorage<Category>
     */
    #[Extbase\Validate(['value' => CategoryMandatoryValidator::class])]
    #[Extbase\ORM\Lazy]
    protected ObjectStorage $categories;

    /**
     * @var ObjectStorage<Day>
     */
    #[Extbase\ORM\Cascade(['value' => 'remove'])]
    #[Extbase\ORM\Lazy]
    protected ObjectStorage $days;

    protected ?Location $location = null;

    /**
     * @var ObjectStorage<Organizer>
     */
    #[Extbase\ORM\Lazy]
    protected ObjectStorage $organizers;

    /**
     * @var ObjectStorage<FileReference>
     */
    #[Extbase\ORM\Cascade(['value' => 'remove'])]
    #[Extbase\ORM\Lazy]
    protected ObjectStorage $images;

    #[Extbase\ORM\Cascade(['value' => 'remove'])]
    protected ?Link $videoLink = null;

    /**
     * @var ObjectStorage<Link>
     */
    #[Extbase\ORM\Cascade(['value' => 'remove'])]
    #[Extbase\ORM\Lazy]
    protected ObjectStorage $downloadLinks;

    protected string $importId = '';

    public function __construct()
    {
        $this->multipleTimes = new ObjectStorage();
        $this->differentTimes = new ObjectStorage();
        $this->exceptions = new ObjectStorage();
        $this->categories = new ObjectStorage();
        $this->days = new ObjectStorage();
        $this->organizers = new ObjectStorage();
        $this->images = new ObjectStorage();
        $this->downloadLinks = new ObjectStorage();
    }

    /**
     * Called again with initialize object, as fetching an entity from the DB does not use the constructor
     */
    public function initializeObject(): void
    {
        $this->multipleTimes = $this->multipleTimes ?? new ObjectStorage();
        $this->differentTimes = $this->differentTimes ?? new ObjectStorage();
        $this->exceptions = $this->exceptions ?? new ObjectStorage();
        $this->categories = $this->categories ?? new ObjectStorage();
        $this->days = $this->days ?? new ObjectStorage();
        $this->organizers = $this->organizers ?? new ObjectStorage();
        $this->images = $this->images ?? new ObjectStorage();
        $this->downloadLinks = $this->downloadLinks ?? new ObjectStorage();
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): void
    {
        $this->eventType = $eventType;
    }

    public function getTopOfList(): bool
    {
        return $this->topOfList;
    }

    public function setTopOfList(bool $topOfList): void
    {
        $this->topOfList = $topOfList;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getPathSegment(): string
    {
        return trim($this->pathSegment);
    }

    public function setPathSegment(string $pathSegment): void
    {
        $this->pathSegment = $pathSegment;
    }

    public function getEventBegin(): ?\DateTimeImmutable
    {
        if ($this->eventBegin instanceof \DateTimeImmutable) {
            // Since PHP 7.4 we can not access timezone_type directly anymore.
            // If location is false, timezone_type is 1 or 2, but we need 3
            if ($this->eventBegin->getTimezone()->getLocation() === false) {
                $this->eventBegin->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            }

            return $this->eventBegin;
        }

        // Can be null, in case of Management::newAction
        return null;
    }

    public function setEventBegin(\DateTimeImmutable $eventBegin): void
    {
        $this->eventBegin = $eventBegin;
    }

    public function getEventTime(): ?Time
    {
        return $this->eventTime;
    }

    public function setEventTime(?Time $eventTime): void
    {
        $this->eventTime = $eventTime;
    }

    public function getDaysOfEventsTakingDays(): int
    {
        $dateTimeUtility = new DateTimeUtility();

        $eventBegin = $dateTimeUtility->standardizeDateTimeObject($this->getEventBegin());
        $eventEnd = $dateTimeUtility->standardizeDateTimeObject($this->getEventEnd());
        if (
            $eventBegin !== null
            && $eventEnd !== null
            && $eventEnd <> $eventBegin
        ) {
            $diff = $eventBegin->diff($eventEnd);

            // Example: 20.01.2013 - 23.01.2013 = 4 days but diff shows 3. So we have to add 1 day here
            return (int)$diff->format('%a') + 1;
        }

        return 0;
    }

    public function getEventEnd(): ?\DateTimeImmutable
    {
        if ($this->eventEnd instanceof \DateTimeImmutable) {
            // Since PHP 7.4 we can not access timezone_type directly anymore.
            // If location is false, timezone_type is 1 or 2, but we need 3
            if ($this->eventEnd->getTimezone()->getLocation() === false) {
                $this->eventEnd->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            }

            return $this->eventEnd;
        }

        return null;
    }

    public function setEventEnd(?\DateTimeImmutable $eventEnd): void
    {
        $this->eventEnd = $eventEnd;
    }

    public function getSameDay(): bool
    {
        return $this->sameDay;
    }

    public function setSameDay(bool $sameDay): void
    {
        $this->sameDay = $sameDay;
    }

    /**
     * @return ObjectStorage|Time[]
     */
    public function getMultipleTimes(): ObjectStorage
    {
        return $this->multipleTimes;
    }

    /**
     * @param ObjectStorage|Time[] $multipleTimes
     */
    public function setMultipleTimes(ObjectStorage $multipleTimes): void
    {
        $this->multipleTimes = $multipleTimes;
    }

    public function addMultipleTime(Time $multipleTime): void
    {
        $this->multipleTimes->attach($multipleTime);
    }

    public function removeMultipleTime(Time $multipleTime): void
    {
        $this->multipleTimes->detach($multipleTime);
    }

    public function getXth(): int
    {
        return $this->xth;
    }

    public function setXth(int $xth): void
    {
        $this->xth = $xth;
    }

    public function getWeekday(): int
    {
        return $this->weekday;
    }

    public function setWeekday(int $weekday): void
    {
        $this->weekday = $weekday;
    }

    /**
     * @return ObjectStorage|Time[]
     */
    public function getDifferentTimes(): ObjectStorage
    {
        return $this->differentTimes;
    }

    /**
     * @param ObjectStorage|Time[] $differentTimes
     */
    public function setDifferentTimes(ObjectStorage $differentTimes): void
    {
        $this->differentTimes = $differentTimes;
    }

    public function addDifferentTime(Time $differentTime): void
    {
        $this->differentTimes->attach($differentTime);
    }

    public function removeDifferentTime(Time $differentTime): void
    {
        $this->differentTimes->detach($differentTime);
    }

    public function getEachWeeks(): int
    {
        return $this->eachWeeks;
    }

    public function setEachWeeks(int $eachWeeks): void
    {
        $this->eachWeeks = $eachWeeks;
    }

    public function getEachMonths(): int
    {
        return $this->eachMonths;
    }

    public function setEachMonths(int $eachMonths): void
    {
        $this->eachMonths = $eachMonths;
    }

    public function getRecurringEnd(): ?\DateTimeImmutable
    {
        if ($this->recurringEnd instanceof \DateTimeImmutable) {
            // Since PHP 7.4 we can not access timezone_type directly anymore.
            // If location is false, timezone_type is 1 or 2, but we need 3
            if ($this->recurringEnd->getTimezone()->getLocation() === false) {
                $this->recurringEnd->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            }

            return clone $this->recurringEnd;
        }

        return null;
    }

    public function setRecurringEnd(?\DateTimeImmutable $recurringEnd): void
    {
        $this->recurringEnd = $recurringEnd;
    }

    public function addException(Exception $exception): void
    {
        $this->exceptions->attach($exception);
    }

    public function removeException(Exception $exception): void
    {
        $this->exceptions->detach($exception);
    }

    /**
     * Without argument, it returns all exceptions.
     * Additionally, you can filter exceptions by type
     * Types: add, remove, time, info
     *
     * @param string $exceptionTypes Comma-separated list of exception types
     * @return ObjectStorage|Exception[]
     */
    public function getExceptions(string $exceptionTypes = ''): ObjectStorage
    {
        $exceptions = new ObjectStorage();
        $exceptionTypes = GeneralUtility::trimExplode(',', strtolower($exceptionTypes), true);

        if (empty($exceptionTypes)) {
            $exceptions = $this->exceptions;
        } else {
            foreach ($this->exceptions as $exception) {
                if (in_array(strtolower($exception->getExceptionType()), $exceptionTypes)) {
                    $exceptions->attach($exception);
                }
            }
        }

        return $exceptions;
    }

    /**
     * Get exceptions for a given date
     * You can limit the result by given exception types.
     *
     * @param \DateTimeImmutable $date
     * @param string $exceptionTypes Type like Add, Remove, Time or Info. If empty add all exceptions
     * @return ObjectStorage|Exception[]
     */
    public function getExceptionsForDate(
        \DateTimeImmutable $date,
        string $exceptionTypes = ''
    ): ObjectStorage {
        $exceptionsForDate = new ObjectStorage();
        if (in_array($this->getEventType(), ['recurring', 'duration']) && $this->getExceptions()->count()) {
            $dateTimeUtility = GeneralUtility::makeInstance(DateTimeUtility::class);
            foreach ($this->getExceptions($exceptionTypes) as $filteredException) {
                $exceptionDate = $dateTimeUtility->standardizeDateTimeObject($filteredException->getExceptionDate());
                $currentDate = $dateTimeUtility->standardizeDateTimeObject($date);

                // we compare objects here so no === possible
                if ($exceptionDate == $currentDate) {
                    $exceptionsForDate->attach($filteredException);
                }
            }
        }

        return $exceptionsForDate;
    }

    public function setExceptions(ObjectStorage $exceptions): void
    {
        $this->exceptions = $exceptions;
    }

    public function getTeaser(): string
    {
        return $this->teaser;
    }

    public function setTeaser(string $teaser): void
    {
        $this->teaser = $teaser;
    }

    public function getDetailInformation(): string
    {
        return $this->detailInformation;
    }

    public function setDetailInformation(string $detailInformation): void
    {
        $this->detailInformation = $detailInformation;
    }

    public function getFreeEntry(): bool
    {
        return $this->freeEntry;
    }

    public function setFreeEntry(bool $freeEntry): void
    {
        $this->freeEntry = $freeEntry;
    }

    public function getTicketLink(): ?Link
    {
        return $this->ticketLink;
    }

    public function setTicketLink(?Link $ticketLink): void
    {
        $this->ticketLink = $ticketLink;
    }

    public function addCategory(Category $category): void
    {
        $this->categories->attach($category);
    }

    public function removeCategory(Category $category): void
    {
        $this->categories->detach($category);
    }

    /**
     * @return ObjectStorage|Category[]
     */
    public function getCategories(): ObjectStorage
    {
        return $this->categories;
    }

    /**
     * A helper method to get all category UIDs as array
     *
     * @return int[]|null[]
     */
    public function getCategoryUids(): array
    {
        $categoryUids = [];
        foreach ($this->categories as $category) {
            $categoryUids[] = $category->getUid();
        }

        return $categoryUids;
    }

    /**
     * @param ObjectStorage|Category[] $categories
     */
    public function setCategories(ObjectStorage $categories): void
    {
        $this->categories = $categories;
    }

    public function addDay(Day $day): void
    {
        $this->days->attach($day);
    }

    public function removeDay(Day $day): void
    {
        $this->days->detach($day);
    }

    /**
     * @return ObjectStorage|Day[] $days
     */
    public function getDays(): ObjectStorage
    {
        return $this->days;
    }

    /**
     * Returns grouped and sorted days of today and future.
     * It must be grouped by day (midnight) as we need it to merge it with exceptions
     * which only have a date, but not a time record for comparison.
     *
     * @return array|\DateTimeImmutable[]
     */
    public function getFutureDatesGroupedAndSorted(): array
    {
        $dateTimeUtility = GeneralUtility::makeInstance(DateTimeUtility::class);
        $today = $dateTimeUtility->standardizeDateTimeObject(new \DateTimeImmutable());

        $futureDates = [];
        foreach ($this->getDays() as $day) {
            if ($day->getDayTime() >= $today) {
                $futureDay = clone $day;
                $futureDates[$futureDay->getDayAsTimestamp()] = $futureDay->getDay();
            }
        }

        ksort($futureDates);

        return $futureDates;
    }

    /**
     * Returns grouped and sorted alternative days of today and future.
     * It must be grouped by day (midnight) as we need it in FE to show multiple
     * Time records for one day.
     *
     * @return array|\DateTimeImmutable[]
     */
    public function getAlternativeTimesGroupedAndSorted(): array
    {
        $dateTimeUtility = GeneralUtility::makeInstance(DateTimeUtility::class);
        $today = $dateTimeUtility->standardizeDateTimeObject(new \DateTimeImmutable());
        $timeFactory = GeneralUtility::makeInstance(TimeFactory::class);

        $alternativeDays = [];
        foreach ($this->getDays() as $day) {
            if ($day->getDayTime() >= $today) {
                $alternativeDay = clone $day;
                $times = $timeFactory->getSortedTimesForDate(
                    $this,
                    $alternativeDay->getDay(),
                    true
                );
                if ($times->count()) {
                    $alternativeDays[$alternativeDay->getDayAsTimestamp()] = [
                        'date' => $alternativeDay->getDay(),
                        'times' => $times
                    ];
                }
            }
        }

        ksort($alternativeDays);

        return $alternativeDays;
    }

    /**
     * Returns grouped and sorted days of today and future.
     * This method also returns days which are marked as removed (is_removed=1).
     * It must be grouped by day (midnight) as we need it to merge it with exceptions
     * which only have a date, but not a time record for comparison.
     *
     * @return array|\DateTimeImmutable[]
     */
    public function getFutureDatesIncludingRemovedGroupedAndSorted(): array
    {
        $dateTimeUtility = GeneralUtility::makeInstance(DateTimeUtility::class);
        $today = $dateTimeUtility->standardizeDateTimeObject(new \DateTimeImmutable());

        $futureDates = $this->getFutureDatesGroupedAndSorted();
        foreach ($this->getExceptions('remove') as $exception) {
            if ($exception->getExceptionDate() >= $today) {
                $exceptionDate = clone $exception->getExceptionDate();
                $futureDates[$exceptionDate->format('U')] = $exceptionDate;
            }
        }

        ksort($futureDates);

        return $futureDates;
    }

    /**
     * Returns grouped and sorted alternative days including removed of today and future.
     * It must be grouped by day (midnight) as we need it in FE to show multiple
     * Time records for one day.
     *
     * @return array|\DateTimeImmutable[]
     */
    public function getAlternativeTimesIncludingRemovedGroupedAndSorted(): array
    {
        $alternativeTimes = $this->getAlternativeTimesGroupedAndSorted();
        $dateTimeUtility = GeneralUtility::makeInstance(DateTimeUtility::class);
        $today = $dateTimeUtility->standardizeDateTimeObject(new \DateTimeImmutable());
        $timeFactory = GeneralUtility::makeInstance(TimeFactory::class);

        foreach ($this->getExceptions('remove') as $exception) {
            if ($exception->getExceptionDate() >= $today) {
                $exceptionDate = clone $exception->getExceptionDate();
                $times = $timeFactory->getSortedTimesForDate(
                    $this,
                    $exceptionDate,
                    true
                );
                if ($times->count()) {
                    $alternativeTimes[$exceptionDate->format('U')] = [
                        'date' => $exceptionDate,
                        'times' => $times
                    ];
                }
            }
        }

        ksort($alternativeTimes);

        return $alternativeTimes;
    }

    /**
     * @param ObjectStorage|Day[] $days
     */
    public function setDays(ObjectStorage $days): void
    {
        $this->days = $days;
    }

    /**
     * Returns the location.
     * Since version 2.3.1 this property can be (not must be) required
     */
    public function getLocation(): ?Location
    {
        return $this->location;
    }

    /**
     * Returns the location as string incl. the full address.
     * This is useful for export or LOCATION-part in ICS.
     */
    public function getLocationAsString(): string
    {
        if ($this->getLocation() instanceof Location) {
            return $this->getLocation()->getLocationAsString();
        }

        return '';
    }

    public function setLocation(?Location $location): void
    {
        $this->location = $location;
    }

    public function getOrganizers(): ObjectStorage
    {
        return $this->organizers;
    }

    public function setOrganizers(ObjectStorage $organizers): void
    {
        $this->organizers = $organizers;
    }

    public function addOrganizer(Organizer $organizer): void
    {
        $this->organizers->attach($organizer);
    }

    public function removeOrganizer(Organizer $organizer): void
    {
        $this->organizers->detach($organizer);
    }

    public function getFirstOrganizer(): ?Organizer
    {
        $this->organizers->rewind();
        if ($this->organizers->count()) {
            return $this->organizers->current();
        }

        return null;
    }

    public function getIsCurrentUserAllowedOrganizer(): bool
    {
        $isAllowed = false;
        $userRepository = GeneralUtility::makeInstance(UserRepository::class);
        $userAssignedOrganizer = (int)$userRepository->getFieldFromUser('tx_events2_organizer');

        foreach ($this->organizers as $organizer) {
            if ($organizer->getUid() === $userAssignedOrganizer) {
                $isAllowed = true;
                break;
            }
        }

        return $isAllowed;
    }

    public function getOriginalImages(): ObjectStorage
    {
        return $this->images;
    }

    /**
     * @return array|FileReference[]
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

    public function setImages(ObjectStorage $images): void
    {
        $this->images = $images;
    }

    public function getVideoLink(): ?Link
    {
        return $this->videoLink;
    }

    public function setVideoLink(?Link $videoLink): void
    {
        $this->videoLink = $videoLink;
    }

    /**
     * @return ObjectStorage|Link[]
     */
    public function getDownloadLinks(): ObjectStorage
    {
        return $this->downloadLinks;
    }

    /**
     * @param ObjectStorage|Link[] $downloadLinks
     */
    public function setDownloadLinks(ObjectStorage $downloadLinks): void
    {
        $this->downloadLinks = $downloadLinks;
    }

    public function addDownloadLink(Link $downloadLink): void
    {
        $this->downloadLinks->attach($downloadLink);
    }

    public function removeDownloadLink(Link $downloadLink): void
    {
        $this->downloadLinks->detach($downloadLink);
    }

    public function getImportId(): string
    {
        return $this->importId;
    }

    public function setImportId(string $importId): void
    {
        $this->importId = $importId;
    }

    /**
     * Helper method to build a baseRecord for path_segment
     * Needed in PathSegmentHelper
     *
     * @return array<string, int>|array<string, string>|array<string, null>
     */
    public function getBaseRecordForPathSegment(): array
    {
        return [
            'uid' => $this->getUid(),
            'pid' => $this->getPid(),
            'title' => $this->getTitle()
        ];
    }
}
