<?php

namespace JWeiland\Events2\Tests\Functional\Repository;

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

use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
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
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Functional test for DayRepository
 */
class DayRepositoryTest extends FunctionalTestCase
{
    /**
     * @var DayRepository
     */
    protected $dayRepository;

    /**
     * @var QuerySettingsInterface
     */
    protected $querySettings;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2',
        'typo3conf/ext/maps2'
    ];

    public function setUp()
    {
        parent::setUp();

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->dayRepository = $this->objectManager->get(DayRepository::class);
        $this->querySettings = $this->objectManager->get(QuerySettingsInterface::class);
        $this->querySettings->setStoragePageIds([11, 40]);
        $this->dayRepository->setDefaultQuerySettings($this->querySettings);
        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $dayRelationService = $this->objectManager->get(DayRelationService::class);
        $eventRepository = $this->objectManager->get(EventRepository::class);
        $eventRepository->setDefaultQuerySettings($this->querySettings);

        // As we need day related records, we can not use XML import functionality
        $organizer1 = new Organizer();
        $organizer1->setPid(11);
        $organizer1->setOrganizer('Stefan');
        $organizer2 = new Organizer();
        $organizer2->setPid(11);
        $organizer2->setOrganizer('Petra');

        $location1 = new Location();
        $location1->setPid(11);
        $location1->setLocation('Market');
        $location2 = new Location();
        $location2->setPid(11);
        $location2->setLocation('Hospital');

        $eventBegin = new \DateTime('midnight');
        $eventBegin->modify('first day of this month')->modify('+4 days')->modify('-2 months');

        $eventTime = new Time();
        $eventTime->setPid(11);
        $eventTime->setTimeBegin('07:30');

        $mainCategory = new Category();
        $mainCategory->setPid(11);
        $mainCategory->setTitle('Cars');

        $category1 = new Category();
        $category1->setPid(11);
        $category1->setParent($mainCategory);
        $category1->setTitle('Audi');

        $category2 = new Category();
        $category2->setPid(11);
        $category2->setParent($mainCategory);
        $category2->setTitle('BMW');

        $event = new Event();
        $event->setPid(11);
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
        $event->setOrganizer($organizer1);
        $event->setLocation($location1);
        $persistenceManager->add($event);

        $multipleTime1 = new Time();
        $multipleTime1->setPid(11);
        $multipleTime1->setTimeBegin('12:00');
        $multipleTime2 = new Time();
        $multipleTime2->setPid(11);
        $multipleTime2->setTimeBegin('20:00');
        $multipleTimes = new ObjectStorage();
        $multipleTimes->attach($multipleTime1);
        $multipleTimes->attach($multipleTime2);

        $recurringEnd = new \DateTime('midnight');
        $recurringEnd->modify('first day of this month')->modify('+4 days')->modify('+1 month');

        $event = new Event();
        $event->setPid(11);
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
        $event->setOrganizer($organizer1);
        $event->setLocation($location1);
        $persistenceManager->add($event);

        $eventBegin = new \DateTime('tomorrow midnight');

        $event = new Event();
        $event->setPid(11);
        $event->setEventType('single');
        $event->setTopOfList(false);
        $event->setTitle('Morgen');
        $event->setTeaser('Test for findDayByEventAndTimestamp');
        $event->setEventBegin($eventBegin);
        $event->setFreeEntry(false);
        $event->setOrganizer($organizer1);
        $event->setLocation($location1);
        $persistenceManager->add($event);

        $eventBegin = new \DateTime('midnight');
        $eventBegin->modify('+2 weeks');
        $eventTime = new Time();
        $eventTime->setPid(11);
        $eventTime->setTimeBegin('09:00');

        $categories = new ObjectStorage();
        $categories->attach($category1);

        $event = new Event();
        $event->setPid(11);
        $event->setEventType('single');
        $event->setTopOfList(false);
        $event->setTitle('Birthday with time');
        $event->setTeaser('');
        $event->setEventBegin($eventBegin);
        $event->setEventTime($eventTime);
        $event->setFreeEntry(false);
        $event->setOrganizer($organizer1);
        $event->setLocation($location2);
        $event->setCategories($categories);
        $persistenceManager->add($event);

        $categories = new ObjectStorage();
        $categories->attach($category1);

        $event = new Event();
        $event->setPid(11);
        $event->setEventType('single');
        $event->setTopOfList(false);
        $event->setTitle('Birthday without time');
        $event->setTeaser('');
        $event->setEventBegin($eventBegin);
        $event->setFreeEntry(false);
        $event->setOrganizer($organizer1);
        $event->setLocation($location1);
        $event->setCategories($categories);
        $persistenceManager->add($event);

        $eventBegin = new \DateTime('midnight');
        $eventBegin->modify('-3 days');
        $eventEnd = new \DateTime('midnight');
        $eventEnd->modify('+3 days');

        $event = new Event();
        $event->setPid(11);
        $event->setEventType('duration');
        $event->setTopOfList(true);
        $event->setTitle('Holiday');
        $event->setTeaser('');
        $event->setEventBegin($eventBegin);
        $event->setEventEnd($eventEnd);
        $event->setFreeEntry(false);
        $event->setOrganizer($organizer2);
        $event->setLocation($location1);
        $persistenceManager->add($event);

        $eventBegin = new \DateTime('midnight');

        $eventTime = new Time();
        $eventTime->setPid(11);
        $eventTime->setTimeBegin('17:00');
        $eventTime->setTimeEntry('16:30');
        $eventTime->setDuration('03:00');
        $eventTime->setTimeEnd('20:00');

        $categories = new ObjectStorage();
        $categories->attach($category1);
        $categories->attach($category2);

        $event = new Event();
        $event->setPid(11);
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
        $event->setOrganizer($organizer1);
        $event->setLocation($location2);
        $event->setCategories($categories);
        $persistenceManager->add($event);

        $recurringEnd = new \DateTime('midnight');
        $recurringEnd->modify('first day of this month')->modify('+4 days')->modify('+3 months');

        $categories = new ObjectStorage();
        $categories->attach($category2);

        $event = new Event();
        $event->setPid(40);
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

        $categories = new ObjectStorage();
        $categories->attach($mainCategory);

        $event = new Event();
        $event->setPid(40);
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
        $event->setOrganizer($organizer1);
        $event->setCategories($categories);
        $persistenceManager->add($event);

        $event = new Event();
        $event->setPid(11);
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
        $event->setOrganizer($organizer2);
        $event->setLocation($location1);
        $persistenceManager->add($event);

        $persistenceManager->persistAll();

        $events = $eventRepository->findAll();
        foreach ($events as $event) {
            $dayRelationService->createDayRelations($event->getUid());
        }
    }

    public function tearDown()
    {
        unset($this->dayRepository);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function findDayByIdentifier()
    {
        $day = $this->dayRepository->findByIdentifier(1);

        $this->assertInstanceOf(
            Day::class,
            $day
        );
    }

    /**
     * @test
     */
    public function findEventsWillFindLessRecordsIfMergeEventsAtSameDayIsActivated()
    {
        $allDays = $this->dayRepository->findEvents('list', new Filter());

        $this->dayRepository->setSettings([
            'mergeEventsAtSameDay' => 1
        ]);
        $allDaysMergedByTime = $this->dayRepository->findEvents('list', new Filter());

        // if merge has worked we MUST have less records now
        $this->assertLessThan(
            $allDays->count(),
            $allDaysMergedByTime->count()
        );
    }

    /**
     * @test
     */
    public function findEventsWillFindLessRecordsIfMergeRecurringEventsIsActivatedAbutMoreIfMergeEventsAtSameDayIsActivated()
    {
        // This is the maximum
        $allDays = $this->dayRepository->findEvents('list', new Filter());

        // These are the days in between
        $this->dayRepository->setSettings([
            'mergeEventsAtSameDay' => 1
        ]);
        $allDaysMergedByTime = $this->dayRepository->findEvents('list', new Filter());

        // This is the minimum
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1
        ]);
        $allDaysMergedByEvent = $this->dayRepository->findEvents('list', new Filter());

        $this->assertLessThan(
            $allDays->count(),
            $allDaysMergedByTime->count()
        );

        $this->assertLessThan(
            $allDays->count(),
            $allDaysMergedByEvent->count()
        );

        $this->assertGreaterThan(
            $allDaysMergedByEvent->count(),
            $allDaysMergedByTime->count()
        );
    }

    /**
     * @test
     */
    public function findEventsByStoragePids()
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1
        ]);

        $this->querySettings->setStoragePageIds([11]);
        $days = $this->dayRepository->findEvents('list', new Filter());
        $this->assertSame(
            8,
            $days->count()
        );

        $this->querySettings->setStoragePageIds([40]);
        $days = $this->dayRepository->findEvents('list', new Filter());
        $this->assertSame(
            2,
            $days->count()
        );
    }

    /**
     * @test
     */
    public function findEventsByCategories()
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1,
            'categories' => '1'
        ]);
        $days = $this->dayRepository->findEvents('list', new Filter());
        $this->assertSame(
            3,
            $days->count()
        );

        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1,
            'categories' => '2'
        ]);
        $days = $this->dayRepository->findEvents('list', new Filter());
        $this->assertSame(
            1,
            $days->count()
        );

        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1,
            'categories' => '3'
        ]);
        $days = $this->dayRepository->findEvents('list', new Filter());
        $this->assertSame(
            2,
            $days->count()
        );

        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1,
            'categories' => '1,2,3'
        ]);
        $days = $this->dayRepository->findEvents('list', new Filter());
        $this->assertSame(
            6,
            $days->count()
        );
    }

    /**
     * @test
     */
    public function findEventsByOrganizer()
    {
        // Organizer 1 in Filter and Plugin
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1,
            'preFilterByOrganizer' => '1'
        ]);
        $days = $this->dayRepository->findEvents('list', new Filter());
        $this->assertSame(
            7,
            $days->count()
        );

        $filter = new Filter();
        $filter->setOrganizer(1);
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1
        ]);
        $days = $this->dayRepository->findEvents('list', $filter);
        $this->assertSame(
            7,
            $days->count()
        );

        // Organizer 2 in Filter and Plugin
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1,
            'preFilterByOrganizer' => '2'
        ]);
        $days = $this->dayRepository->findEvents('list', new Filter());
        $this->assertSame(
            2,
            $days->count()
        );

        $filter = new Filter();
        $filter->setOrganizer(2);
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1
        ]);
        $days = $this->dayRepository->findEvents('list', $filter);
        $this->assertSame(
            2,
            $days->count()
        );
    }

    /**
     * @test
     */
    public function findEventsWithFilterAndTsPreFilterWillFindDaysByFilter()
    {
        $filter = new Filter();
        $filter->setOrganizer(1);
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1,
            'preFilterByOrganizer' => '1'
        ]);
        $days = $this->dayRepository->findEvents('list', $filter);
        $this->assertSame(
            7,
            $days->count()
        );
    }

    /**
     * @test
     */
    public function findEventsAndLimitResult()
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 0
        ]);

        $days = $this->dayRepository->findEvents('list', new Filter(), 5);
        $this->assertSame(
            5,
            count($days->toArray())
        );
    }

    /**
     * @test
     */
    public function findEventsByTypeListWithTopOfListRecord()
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 0
        ]);

        $days = $this->dayRepository->findEvents('list', new Filter());
        $days->rewind();

        /** @var Day $day */
        $day = $days->current();

        $this->assertSame(
            true,
            $day->getEvent()->getTopOfList()
        );
    }

    /**
     * @test
     */
    public function findEventsByTypeLatest()
    {
        $todayStart = new \DateTime('midnight');

        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 0
        ]);

        $days = $this->dayRepository->findEvents('latest', new Filter(), 7);
        $this->assertSame(
            7,
            count($days->toArray())
        );

        foreach ($days as $day) {
            $this->assertGreaterThanOrEqual(
                $todayStart,
                $day->getDayTime()
            );
        }
    }

    /**
     * @test
     */
    public function findEventsByTypeToday()
    {
        $todayStart = new \DateTime('midnight');
        $todayEnd = new \DateTime('tomorrow midnight');

        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 0
        ]);

        /** @var Day[] $days */
        $days = $this->dayRepository->findEvents('today', new Filter());
        foreach ($days as $day) {
            $this->assertGreaterThanOrEqual(
                $todayStart,
                $day->getDayTime()
            );
            $this->assertLessThan(
                $todayEnd,
                $day->getDayTime()
            );
        }
    }

    /**
     * @test
     */
    public function findEventsByTypeRange()
    {
        $dateStart = new \DateTime('midnight');
        $dateEnd = new \DateTime('tomorrow midnight');
        $dateEnd->modify('+4 weeks');

        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 0
        ]);

        /** @var Day[] $days */
        $days = $this->dayRepository->findEvents('range', new Filter());
        foreach ($days as $day) {
            $this->assertGreaterThanOrEqual(
                $dateStart,
                $day->getDayTime()
            );
            $this->assertLessThan(
                $dateEnd,
                $day->getDayTime()
            );
        }
    }

    /**
     * @test
     */
    public function findEventsByTypeWeek()
    {
        $dateStart = new \DateTime('midnight');
        $dateEnd = new \DateTime('midnight');
        $dateEnd->modify('+7 days');

        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 0
        ]);

        /** @var Day[] $days */
        $days = $this->dayRepository->findEvents('thisWeek', new Filter());
        foreach ($days as $day) {
            $this->assertGreaterThanOrEqual(
                $dateStart,
                $day->getDayTime()
            );
            $this->assertLessThan(
                $dateEnd,
                $day->getDayTime()
            );
        }
    }

    /**
     * @test
     */
    public function searchEvents()
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1
        ]);

        $this->querySettings->setStoragePageIds([11]);
        $days = $this->dayRepository->searchEvents(new Search());
        $this->assertSame(
            8,
            count($days->toArray())
        );

        $this->querySettings->setStoragePageIds([40]);
        $days = $this->dayRepository->searchEvents(new Search());
        $this->assertSame(
            2,
            count($days->toArray())
        );
    }

    /**
     * @test
     */
    public function searchEventsBySearchWord()
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 0
        ]);

        $search = new Search();
        $search->setSearch('birthday');

        $days = $this->dayRepository->searchEvents($search);
        $this->assertSame(
            2,
            count($days->toArray())
        );
    }

    /**
     * @test
     */
    public function searchEventsBySearchWordTeaser()
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1
        ]);

        $search = new Search();
        $search->setSearch('coding');

        $days = $this->dayRepository->searchEvents($search);
        $this->assertSame(
            1,
            count($days->toArray())
        );
    }

    /**
     * @test
     */
    public function searchEventsByCategory()
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1
        ]);

        $categoryRepository = $this->objectManager->get(CategoryRepository::class);

        /** @var Category $mainCategory */
        $mainCategory = $categoryRepository->findByIdentifier(2);

        $search = new Search();
        $search->setMainCategory($mainCategory);

        $days = $this->dayRepository->searchEvents($search);
        $this->assertSame(
            1,
            count($days->toArray())
        );

        /** @var Category $subCategory */
        $subCategory = $categoryRepository->findByIdentifier(1);

        $search = new Search();
        $search->setMainCategory($mainCategory);
        $search->setSubCategory($subCategory);

        $days = $this->dayRepository->searchEvents($search);
        $this->assertSame(
            3,
            count($days->toArray())
        );

        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1,
            'categories' => '2,3'
        ]);
        $days = $this->dayRepository->searchEvents(new Search());
        $this->assertSame(
            3,
            count($days->toArray())
        );
    }

    /**
     * @test
     */
    public function searchEventsByEventBegin()
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1
        ]);

        $tomorrow = new \DateTime('tomorrow midnight');

        $search = new Search();
        $search->setEventBegin($tomorrow->format('d.m.Y'));

        /** @var Day[] $days */
        $days = $this->dayRepository->searchEvents($search);
        foreach ($days as $day) {
            $this->assertGreaterThanOrEqual(
                $tomorrow,
                $day->getDayTime()
            );
        }
    }

    /**
     * @test
     */
    public function searchEventsByEventBeginAndEventEnd()
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1
        ]);

        $tomorrow = new \DateTime('tomorrow midnight');
        $nextMonth = new \DateTime('midnight');
        $nextMonth->modify('next month');

        $search = new Search();
        $search->setEventBegin($tomorrow->format('d.m.Y'));
        $search->setEventEnd($nextMonth->format('d.m.Y'));

        /** @var Day[] $days */
        $days = $this->dayRepository->searchEvents($search);
        foreach ($days as $day) {
            $this->assertGreaterThanOrEqual(
                $tomorrow,
                $day->getDayTime()
            );
            $this->assertLessThanOrEqual(
                $nextMonth,
                $day->getDayTime()
            );
        }
    }

    /**
     * @test
     */
    public function searchEventsByLocation()
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1
        ]);

        $locationRepository = $this->objectManager->get(LocationRepository::class);

        /** @var Location $location */
        $location = $locationRepository->findByIdentifier(1);

        $search = new Search();
        $search->setLocation($location);

        $days = $this->dayRepository->searchEvents($search);
        $this->assertSame(
            7,
            count($days->toArray())
        );
    }

    /**
     * @test
     */
    public function searchEventsByFreeEntry()
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1
        ]);

        $search = new Search();
        $search->setFreeEntry(true);

        $days = $this->dayRepository->searchEvents($search);
        $this->assertSame(
            1,
            count($days->toArray())
        );
    }

    /**
     * @test
     */
    public function findByTimestamp()
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1
        ]);

        $twoWeeks = new \DateTime('midnight');
        $twoWeeks->modify('+2 weeks'); // two birthday records

        $days = $this->dayRepository->findByTimestamp($twoWeeks->format('U'));
        $this->assertGreaterThanOrEqual( // in last week of month we have 3 because of UserGroup
            2,
            count($days->toArray())
        );
    }

    /**
     * @test
     */
    public function findByTimestampAndCategory()
    {
        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => 1,
            'categories' => '1'
        ]);

        $twoWeeks = new \DateTime('midnight');
        $twoWeeks->modify('+2 weeks'); // two birthday records

        $days = $this->dayRepository->findByTimestamp($twoWeeks->format('U'));
        $this->assertGreaterThanOrEqual( // in last week of month we have 3 because of UserGroup
            2,
            count($days->toArray())
        );
    }

    /**
     * @test
     */
    public function findDayByEventAndTimestamp()
    {
        $tomorrow = new \DateTime('tomorrow midnight');

        // EventUid 4 => Holiday duration
        $day = $this->dayRepository->findDayByEventAndTimestamp(3, $tomorrow->format('U'));
        $this->assertInstanceOf(
            Day::class,
            $day
        );
        $this->assertSame(
            'Morgen',
            $day->getEvent()->getTitle()
        );
    }
}
