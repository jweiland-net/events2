<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Domain\Repository;

use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Organizer;
use JWeiland\Events2\Domain\Model\Search;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Domain\Repository\CategoryRepository;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Domain\Repository\LocationRepository;
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for DayRepository
 */
class DayRepositoryTest extends FunctionalTestCase
{
    protected DayRepository $dayRepository;

    protected QuerySettingsInterface $querySettings;

    protected ObjectManager $objectManager;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
        'jweiland/maps2',
    ];

    protected function setUp(): void
    {
        self::markTestIncomplete('DayRepositoryTest not updated until right now');

        parent::setUp();

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->dayRepository = $this->objectManager->get(DayRepository::class);

        $this->querySettings = $this->objectManager->get(QuerySettingsInterface::class);
        $this->querySettings->setStoragePageIds([Events2Constants::PAGE_STORAGE]);
        $this->querySettings->setIgnoreEnableFields(true);
        $this->querySettings->setEnableFieldsToBeIgnored(['disabled']); // needed to create hidden events, too

        $this->dayRepository->setDefaultQuerySettings($this->querySettings);
        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $dayRelationService = $this->objectManager->get(DayRelationService::class);

        $eventRepository = $this->objectManager->get(EventRepository::class);
        $eventRepository->setDefaultQuerySettings($this->querySettings);

        // As we need day related records, we can not use XML import functionality
        $organizer1 = new Organizer();
        $organizer1->setPid(Events2Constants::PAGE_STORAGE);
        $organizer1->setOrganizer('Stefan');

        $organizer2 = new Organizer();
        $organizer2->setPid(Events2Constants::PAGE_STORAGE);
        $organizer2->setOrganizer('Petra');

        $location1 = new Location();
        $location1->setPid(Events2Constants::PAGE_STORAGE);
        $location1->setLocation('Market');

        $location2 = new Location();
        $location2->setPid(Events2Constants::PAGE_STORAGE);
        $location2->setLocation('Hospital');

        $eventBegin = new \DateTimeImmutable('first day of this month midnight');
        $eventBegin = $eventBegin
            ->modify('+4 days')
            ->modify('-2 months');

        $eventTime = new Time();
        $eventTime->setPid(Events2Constants::PAGE_STORAGE);
        $eventTime->setTimeBegin('07:30');

        $mainCategory = new Category();
        $mainCategory->setPid(Events2Constants::PAGE_STORAGE);
        $mainCategory->setTitle('Cars');

        $category1 = new Category();
        $category1->setPid(Events2Constants::PAGE_STORAGE);
        $category1->setParent($mainCategory);
        $category1->setTitle('Audi');

        $category2 = new Category();
        $category2->setPid(Events2Constants::PAGE_STORAGE);
        $category2->setParent($mainCategory);
        $category2->setTitle('BMW');

        $categories = new ObjectStorage();
        $categories->attach($category1);

        $event = GeneralUtility::makeInstance(Event::class);
        $event->setPid(Events2Constants::PAGE_STORAGE);
        $event->setEventType('recurring');
        $event->setTopOfList(false);
        $event->setTitle('Week market');
        $event->setTeaser('');
        $event->setEventBegin($eventBegin);
        $event->setEventTime($eventTime);
        $event->setXth(31);
        $event->setWeekday(16);
        $event->setEachWeeks(0);
        $event->setEachMonths(0);
        $event->setRecurringEnd(null);
        $event->setFreeEntry(false);
        $event->addOrganizer($organizer1);
        $event->setLocation($location1);
        $event->setCategories($categories);

        $persistenceManager->add($event);

        $multipleTime1 = new Time();
        $multipleTime1->setPid(Events2Constants::PAGE_STORAGE);
        $multipleTime1->setTimeBegin('12:00');
        $multipleTime2 = new Time();
        $multipleTime2->setPid(Events2Constants::PAGE_STORAGE);
        $multipleTime2->setTimeBegin('20:00');
        $multipleTimes = new ObjectStorage();
        $multipleTimes->attach($multipleTime1);
        $multipleTimes->attach($multipleTime2);

        $recurringEnd = new \DateTimeImmutable('first day of this month midnight');
        $recurringEnd = $recurringEnd
            ->modify('+4 days')
            ->modify('+1 month');

        $categories = new ObjectStorage();
        $categories->attach($category1);

        $event = GeneralUtility::makeInstance(Event::class);
        $event->setPid(Events2Constants::PAGE_STORAGE);
        $event->setEventType('recurring');
        $event->setTopOfList(false);
        $event->setTitle('Multiple times same day');
        $event->setTeaser('');
        $event->setEventBegin($eventBegin);
        $event->setEventTime($eventTime);
        $event->setSameDay(true);
        $event->setMultipleTimes($multipleTimes);
        $event->setXth(31);
        $event->setWeekday(16);
        $event->setEachWeeks(0);
        $event->setEachMonths(0);
        $event->setRecurringEnd($recurringEnd);
        $event->setFreeEntry(false);
        $event->addOrganizer($organizer1);
        $event->setLocation($location1);
        $event->setCategories($categories);

        $persistenceManager->add($event);

        $eventBegin = new \DateTimeImmutable('tomorrow midnight');

        $categories = new ObjectStorage();
        $categories->attach($category1);

        $event = GeneralUtility::makeInstance(Event::class);
        $event->setPid(Events2Constants::PAGE_STORAGE);
        $event->setEventType('single');
        $event->setTopOfList(false);
        $event->setTitle('Morgen');
        $event->setTeaser('Test for findDayByEventAndTimestamp');
        $event->setEventBegin($eventBegin);
        $event->setFreeEntry(false);
        $event->addOrganizer($organizer1);
        $event->setLocation($location1);
        $event->setCategories($categories);

        $persistenceManager->add($event);

        $eventBegin = new \DateTimeImmutable('midnight');
        $eventBegin = $eventBegin->modify('+2 weeks');

        $eventTime = new Time();
        $eventTime->setPid(Events2Constants::PAGE_STORAGE);
        $eventTime->setTimeBegin('09:00');

        $categories = new ObjectStorage();
        $categories->attach($category1);
        $categories->attach($category2);

        $event = GeneralUtility::makeInstance(Event::class);
        $event->setPid(Events2Constants::PAGE_STORAGE);
        $event->setEventType('single');
        $event->setTopOfList(false);
        $event->setTitle('Birthday with time');
        $event->setTeaser('');
        $event->setEventBegin($eventBegin);
        $event->setEventTime($eventTime);
        $event->setFreeEntry(false);
        $event->addOrganizer($organizer1);
        $event->setLocation($location2);
        $event->setCategories($categories);

        $persistenceManager->add($event);

        $categories = new ObjectStorage();
        $categories->attach($category1);

        $event = GeneralUtility::makeInstance(Event::class);
        $event->setPid(Events2Constants::PAGE_STORAGE);
        $event->setEventType('single');
        $event->setTopOfList(false);
        $event->setTitle('Birthday without time');
        $event->setTeaser('');
        $event->setEventBegin($eventBegin);
        $event->setFreeEntry(false);
        $event->addOrganizer($organizer1);
        $event->setLocation($location1);
        $event->setCategories($categories);

        $persistenceManager->add($event);

        $eventBegin = new \DateTimeImmutable('midnight');
        $eventBegin = $eventBegin->modify('-3 days');
        $eventEnd = new \DateTimeImmutable('midnight');
        $eventEnd = $eventEnd->modify('+3 days');

        $categories = new ObjectStorage();
        $categories->attach($mainCategory);

        $event = GeneralUtility::makeInstance(Event::class);
        $event->setPid(Events2Constants::PAGE_STORAGE);
        $event->setEventType('duration');
        $event->setTopOfList(true);
        $event->setTitle('Holiday');
        $event->setTeaser('');
        $event->setEventBegin($eventBegin);
        $event->setEventEnd($eventEnd);
        $event->setFreeEntry(false);
        $event->addOrganizer($organizer2);
        $event->setLocation($location1);
        $event->setCategories($categories);

        $persistenceManager->add($event);

        $eventBegin = new \DateTimeImmutable('midnight');

        $eventTime = new Time();
        $eventTime->setPid(Events2Constants::PAGE_STORAGE);
        $eventTime->setTimeBegin('17:00');
        $eventTime->setTimeEntry('16:30');
        $eventTime->setDuration('03:00');
        $eventTime->setTimeEnd('20:00');

        $categories = new ObjectStorage();
        $categories->attach($category1);
        $categories->attach($category2);

        $event = GeneralUtility::makeInstance(Event::class);
        $event->setPid(Events2Constants::PAGE_STORAGE);
        $event->setEventType('recurring');
        $event->setTopOfList(true);
        $event->setTitle('UserGroup');
        $event->setTeaser('Coding');
        $event->setEventBegin($eventBegin);
        $event->setEventTime($eventTime);
        $event->setXth(10);
        $event->setWeekday(4);
        $event->setEachWeeks(0);
        $event->setEachMonths(0);
        $event->setFreeEntry(true);
        $event->addOrganizer($organizer1);
        $event->setLocation($location2);
        $event->setCategories($categories);

        $persistenceManager->add($event);

        $recurringEnd = new \DateTimeImmutable('first day of this month midnight');
        $recurringEnd = $recurringEnd
            ->modify('+4 days')
            ->modify('+3 months');

        $categories = new ObjectStorage();
        $categories->attach($category1);

        $event = GeneralUtility::makeInstance(Event::class);
        $event->setPid(Events2Constants::PAGE_STORAGE);
        $event->setEventType('recurring');
        $event->setTopOfList(false);
        $event->setTitle('EachMonth');
        $event->setTeaser('');
        $event->setEventBegin($eventBegin);
        $event->setXth(0);
        $event->setWeekday(0);
        $event->setEachWeeks(0);
        $event->setEachMonths(1);
        $event->setRecurringEnd($recurringEnd);
        $event->setFreeEntry(false);
        $event->setLocation($location1);
        $event->setCategories($categories);

        $persistenceManager->add($event);

        $event = GeneralUtility::makeInstance(Event::class);
        $event->setPid(Events2Constants::PAGE_STORAGE);
        $event->setEventType('recurring');
        $event->setTopOfList(false);
        $event->setTitle('EachSecondWeek');
        $event->setTeaser('');
        $event->setEventBegin($eventBegin);
        $event->setXth(0);
        $event->setWeekday(0);
        $event->setEachWeeks(2);
        $event->setEachMonths(0);
        $event->setRecurringEnd($recurringEnd);
        $event->setFreeEntry(false);
        $event->addOrganizer($organizer1);

        $persistenceManager->add($event);

        $categories = new ObjectStorage();
        $categories->attach($category2);

        $event = GeneralUtility::makeInstance(Event::class);
        $event->setPid(Events2Constants::PAGE_STORAGE);
        $event->setEventType('recurring');
        $event->setTopOfList(false);
        $event->setTitle('Recurring event with end');
        $event->setTeaser('');
        $event->setEventBegin($eventBegin);
        $event->setXth(21);
        $event->setWeekday(21);
        $event->setEachWeeks(0);
        $event->setEachMonths(0);
        $event->setRecurringEnd(null);
        $event->setFreeEntry(false);
        $event->addOrganizer($organizer2);
        $event->setLocation($location1);
        $event->setCategories($categories);

        $persistenceManager->add($event);

        $eventBegin = new \DateTimeImmutable('midnight');
        $eventBegin = $eventBegin->modify('+2 days');

        $event = GeneralUtility::makeInstance(Event::class);
        $event->setPid(Events2Constants::PAGE_STORAGE);
        $event->setHidden(true);
        $event->setEventType('single');
        $event->setTopOfList(false);
        $event->setTitle('Hidden Event');
        $event->setTeaser('');
        $event->setEventBegin($eventBegin);
        $event->setFreeEntry(true);
        $event->addOrganizer($organizer1);
        $event->setLocation($location1);
        $event->setCategories($categories);

        $persistenceManager->add($event);

        $eventBegin = new \DateTimeImmutable('midnight');
        $eventBegin = $eventBegin->modify('+3 days');
        $eventEnd = new \DateTimeImmutable('midnight');
        $eventEnd = $eventEnd->modify('+5 days');
        $exceptionDate1 = new \DateTimeImmutable('midnight');
        $exceptionDate1 = $exceptionDate1->modify('+4 days');
        $exceptionDate2 = new \DateTimeImmutable('midnight');
        $exceptionDate2 = $exceptionDate2->modify('+5 days');
        $eventTime = new Time();
        $eventTime->setTimeBegin('18:00');
        $eventTime->setTimeEnd('22:00');
        $exceptionTime1 = new Time();
        $exceptionTime1->setTimeBegin('09:00');
        $exceptionTime1->setTimeEnd('18:30');
        $exceptionTime2 = new Time();
        $exceptionTime2->setTimeBegin('09:00');
        $exceptionTime2->setTimeEnd('12:30');
        $exception1 = new Exception();
        $exception1->setExceptionType('Time');
        $exception1->setExceptionDate($exceptionDate1);
        $exception1->setExceptionTime($exceptionTime1);
        $exception2 = new Exception();
        $exception2->setExceptionType('Time');
        $exception2->setExceptionDate($exceptionDate2);
        $exception2->setExceptionTime($exceptionTime2);
        $exceptions = new ObjectStorage();
        $exceptions->attach($exception1);
        $exceptions->attach($exception2);

        $event = GeneralUtility::makeInstance(Event::class);
        $event->setPid(Events2Constants::PAGE_STORAGE);
        $event->setEventType('duration');
        $event->setEventTime($eventTime);
        $event->setTopOfList(true);
        $event->setTitle('Cool trip');
        $event->setTeaser('Duration with time exceptions. sort_day_time has to be the same for all days');
        $event->setEventBegin($eventBegin);
        $event->setEventEnd($eventEnd);
        $event->addOrganizer($organizer2);
        $event->setLocation($location1);
        $event->setExceptions($exceptions);
        $persistenceManager->add($event);

        $persistenceManager->persistAll();

        $events = $eventRepository->findAll();
        foreach ($events as $event) {
            $dayRelationService->createDayRelations($event->getUid());
        }
    }

    protected function tearDown(): void
    {
        unset(
            $this->dayRepository,
        );

        parent::tearDown();
    }

    protected function setShowHiddenRecords(): void
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect(
            'visibility',
            GeneralUtility::makeInstance(VisibilityAspect::class, false, true),
        );
    }

    #[Test]
    public function findDayByIdentifier(): void
    {
        $day = $this->dayRepository->findByIdentifier(1);

        self::assertInstanceOf(
            Day::class,
            $day,
        );
    }

    #[Test]
    public function findEventsWillFindLessRecordsIfMergeEventsAtSameDayIsActivated(): void
    {
        $allDays = $this->dayRepository->getDaysForListType('list', new Filter());

        $this->dayRepository->setSettings([
            'mergeEventsAtSameDay' => '1',
        ]);
        $allDaysMergedByTime = $this->dayRepository->getDaysForListType('list', new Filter());

        // if merge has worked we MUST have less records now
        self::assertLessThan(
            $allDays->count(),
            $allDaysMergedByTime->count(),
        );
    }

    #[Test]
    public function findEventsWillFindLessRecordsIfMergeRecurringEventsIsActivatedAbutMoreIfMergeEventsAtSameDayIsActivated(): void
    {
        // This is the maximum
        $allDays = $this->dayRepository->getDaysForListType('list', new Filter());

        // These are the days in between
        $this->dayRepository->setSettings([
            'mergeEventsAtSameDay' => '1',
        ]);
        $allDaysMergedByTime = $this->dayRepository->getDaysForListType('list', new Filter());

        // This is the minimum
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
        ]);
        $allDaysMergedByEvent = $this->dayRepository->getDaysForListType('list', new Filter());

        self::assertLessThan(
            $allDays->count(),
            $allDaysMergedByTime->count(),
        );

        self::assertLessThan(
            $allDays->count(),
            $allDaysMergedByEvent->count(),
        );

        self::assertGreaterThan(
            $allDaysMergedByEvent->count(),
            $allDaysMergedByTime->count(),
        );
    }

    #[Test]
    public function findEventsByStoragePids(): void
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
        ]);

        $this->querySettings->setStoragePageIds([Events2Constants::PAGE_STORAGE]);
        $days = $this->dayRepository->getDaysForListType('list', new Filter());
        self::assertSame(
            9,
            $days->count(),
        );

        $this->querySettings->setStoragePageIds([Events2Constants::PAGE_STORAGE]);
        $days = $this->dayRepository->getDaysForListType('list', new Filter());
        self::assertSame(
            2,
            $days->count(),
        );
    }

    #[Test]
    public function findHiddenEventsByStoragePids(): void
    {
        $this->setShowHiddenRecords();

        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
        ]);

        $this->querySettings->setStoragePageIds([Events2Constants::PAGE_STORAGE]);
        $days = $this->dayRepository->getDaysForListType('list', new Filter());
        self::assertSame(
            10,
            $days->count(),
        );
    }

    #[Test]
    public function findEventsByCategories(): void
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
            'categories' => '1',
        ]);
        $days = $this->dayRepository->getDaysForListType('list', new Filter());

        self::assertCount(
            7,
            $days->toArray(),
        );

        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
            'categories' => '2',
        ]);
        $days = $this->dayRepository->getDaysForListType('list', new Filter());
        self::assertCount(
            1,
            $days,
        );

        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
            'categories' => '3',
        ]);
        $days = $this->dayRepository->getDaysForListType('list', new Filter());
        self::assertSame(
            3,
            $days->count(),
        );

        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
            'categories' => '1,2,3',
        ]);
        $days = $this->dayRepository->getDaysForListType('list', new Filter());
        self::assertCount(
            9,
            $days->toArray(),
        );
    }

    #[Test]
    public function findEventsByOrganizer(): void
    {
        // Organizer 1 in Filter and Plugin
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
            'preFilterByOrganizer' => '1',
        ]);
        $days = $this->dayRepository->getDaysForListType('list', new Filter());
        self::assertSame(
            7,
            $days->count(),
        );

        $filter = new Filter();
        $filter->setOrganizer(1);
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
        ]);
        $days = $this->dayRepository->getDaysForListType('list', $filter);
        self::assertSame(
            7,
            $days->count(),
        );

        // Organizer 2 in Filter and Plugin
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
            'preFilterByOrganizer' => '2',
        ]);
        $days = $this->dayRepository->getDaysForListType('list', new Filter());
        self::assertSame(
            3,
            $days->count(),
        );

        $filter = new Filter();
        $filter->setOrganizer(2);
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
        ]);
        $days = $this->dayRepository->getDaysForListType('list', $filter);
        self::assertSame(
            3,
            $days->count(),
        );
    }

    #[Test]
    public function findEventsWithFilterAndTsPreFilterWillFindDaysByFilter(): void
    {
        $filter = new Filter();
        $filter->setOrganizer(1);
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
            'preFilterByOrganizer' => '1',
        ]);
        $days = $this->dayRepository->getDaysForListType('list', $filter);
        self::assertSame(
            7,
            $days->count(),
        );
    }

    #[Test]
    public function findEventsAndLimitResult(): void
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '0',
        ]);

        $days = $this->dayRepository->getDaysForListType('list', new Filter(), 5);
        self::assertCount(
            5,
            $days->toArray(),
        );
    }

    #[Test]
    public function findEventsByTypeListWithTopOfListRecord(): void
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '0',
        ]);

        $days = $this->dayRepository->getDaysForListType('list', new Filter());
        $days->rewind();

        /** @var Day $day */
        $day = $days->current();

        self::assertTrue(
            $day->getEvent()->getTopOfList(),
        );
    }

    #[Test]
    public function findEventsByTypeLatest(): void
    {
        $todayStart = new \DateTimeImmutable('midnight');

        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '0',
        ]);

        $days = $this->dayRepository->getDaysForListType('listLatest', new Filter(), 7);
        self::assertCount(
            7,
            $days->toArray(),
        );

        foreach ($days as $day) {
            self::assertGreaterThanOrEqual(
                $todayStart,
                $day->getDayTime(),
            );
        }
    }

    #[Test]
    public function findEventsByTypeToday(): void
    {
        $todayStart = new \DateTimeImmutable('midnight');
        $todayEnd = new \DateTimeImmutable('tomorrow midnight');

        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '0',
        ]);

        $days = $this->dayRepository->getDaysForListType('listToday', new Filter());
        foreach ($days as $day) {
            self::assertGreaterThanOrEqual(
                $todayStart,
                $day->getDayTime(),
            );
            self::assertLessThan(
                $todayEnd,
                $day->getDayTime(),
            );
        }
    }

    #[Test]
    public function findEventsByTypeRange(): void
    {
        $dateStart = new \DateTimeImmutable('midnight');
        $dateEnd = new \DateTimeImmutable('tomorrow midnight');
        $dateEnd = $dateEnd->modify('+4 weeks');

        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '0',
        ]);

        $days = $this->dayRepository->getDaysForListType('listRange', new Filter());
        foreach ($days as $day) {
            self::assertGreaterThanOrEqual(
                $dateStart,
                $day->getDayTime(),
            );
            self::assertLessThan(
                $dateEnd,
                $day->getDayTime(),
            );
        }
    }

    #[Test]
    public function findEventsByTypeWeek(): void
    {
        $dateStart = new \DateTimeImmutable('this week midnight');
        $dateEnd = new \DateTimeImmutable('this week midnight');
        $dateEnd = $dateEnd->modify('+7 days'); // Everything LESS THAN Monday next Week 00:00:00

        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '0',
        ]);

        $days = $this->dayRepository->getDaysForListType('listWeek', new Filter());
        foreach ($days as $day) {
            self::assertGreaterThanOrEqual(
                $dateStart,
                $day->getDayTime(),
            );
            self::assertLessThan(
                $dateEnd,
                $day->getDayTime(),
            );
        }
    }

    #[Test]
    public function searchEvents(): void
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
        ]);

        $this->querySettings->setStoragePageIds([Events2Constants::PAGE_STORAGE]);
        $days = $this->dayRepository->searchEvents(new Search());
        self::assertCount(
            9,
            $days->toArray(),
        );

        $this->querySettings->setStoragePageIds([Events2Constants::PAGE_STORAGE]);
        $days = $this->dayRepository->searchEvents(new Search());
        self::assertCount(
            2,
            $days->toArray(),
        );
    }

    #[Test]
    public function searchEventsBySearchWord(): void
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '0',
        ]);

        $search = new Search();
        $search->setSearch('birthday');

        $days = $this->dayRepository->searchEvents($search);
        self::assertCount(
            2,
            $days->toArray(),
        );
    }

    #[Test]
    public function searchEventsBySearchWordTeaser(): void
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
        ]);

        $search = new Search();
        $search->setSearch('coding');

        $days = $this->dayRepository->searchEvents($search);
        self::assertCount(
            1,
            $days->toArray(),
        );
    }

    #[Test]
    public function searchEventsByCategory(): void
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
        ]);

        $categoryRepository = $this->objectManager->get(CategoryRepository::class);

        /** @var Category $mainCategory */
        $mainCategory = $categoryRepository->findByIdentifier(2);

        $search = new Search();
        $search->setMainCategory($mainCategory);

        $days = $this->dayRepository->searchEvents($search);
        self::assertCount(
            1,
            $days->toArray(),
        );

        /** @var Category $subCategory */
        $subCategory = $categoryRepository->findByIdentifier(1);

        $search = new Search();
        $search->setMainCategory($mainCategory);
        $search->setSubCategory($subCategory);

        $days = $this->dayRepository->searchEvents($search);
        self::assertCount(
            7,
            $days->toArray(),
        );

        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
            'categories' => '2,3',
        ]);
        $days = $this->dayRepository->searchEvents(new Search());
        self::assertCount(
            4,
            $days->toArray(),
        );
    }

    #[Test]
    public function searchEventsByEventBegin(): void
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
        ]);

        $tomorrow = new \DateTimeImmutable('tomorrow midnight');

        $search = new Search();
        $search->setEventBegin($tomorrow->format('d.m.Y'));

        /** @var Day[] $days */
        $days = $this->dayRepository->searchEvents($search);
        foreach ($days as $day) {
            self::assertGreaterThanOrEqual(
                $tomorrow,
                $day->getDayTime(),
            );
        }
    }

    #[Test]
    public function searchEventsByEventBeginAndEventEnd(): void
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
        ]);

        $tomorrow = new \DateTimeImmutable('tomorrow midnight');
        $nextMonth = new \DateTimeImmutable('next month midnight');

        $search = new Search();
        $search->setEventBegin($tomorrow->format('d.m.Y'));
        $search->setEventEnd($nextMonth->format('d.m.Y'));

        /** @var Day[] $days */
        $days = $this->dayRepository->searchEvents($search);
        foreach ($days as $day) {
            self::assertGreaterThanOrEqual(
                $tomorrow,
                $day->getDayTime(),
            );
            self::assertLessThanOrEqual(
                $nextMonth,
                $day->getDayTime(),
            );
        }
    }

    #[Test]
    public function searchEventsByLocation(): void
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
        ]);

        $locationRepository = $this->objectManager->get(LocationRepository::class);

        /** @var Location $location */
        $location = $locationRepository->findByIdentifier(1);

        $search = new Search();
        $search->setLocation($location);

        $days = $this->dayRepository->searchEvents($search);
        self::assertCount(
            8,
            $days->toArray(),
        );
    }

    #[Test]
    public function searchEventsByFreeEntry(): void
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
        ]);

        $search = new Search();
        $search->setFreeEntry(true);

        $days = $this->dayRepository->searchEvents($search);
        self::assertCount(
            1,
            $days->toArray(),
        );
    }

    #[Test]
    public function findDayByEventAndTimestamp(): void
    {
        $tomorrow = new \DateTimeImmutable('tomorrow midnight');

        // EventUid 4 => Holiday duration
        $day = $this->dayRepository->findDayByEventAndTimestamp(3, (int)$tomorrow->format('U'));
        self::assertSame(
            'Morgen',
            $day->getEvent()->getTitle(),
        );
    }

    /**
     * In case of duration events, the column sort_day_time has to be the same for all generated day records.
     */
    #[Test]
    public function additionalTimeExceptionsForDurationEventsWillNotCreateNewEntryInListView(): void
    {
        $this->querySettings->setStoragePageIds([Events2Constants::PAGE_STORAGE]);
        $days = $this->dayRepository->getDaysForListType('list', new Filter());
        $counter = 0;
        foreach ($days as $day) {
            if ($day->getEvent()->getEventType() === 'duration') {
                ++$counter;
            }
        }
        self::assertSame(
            2,
            $counter,
        );
    }
}
