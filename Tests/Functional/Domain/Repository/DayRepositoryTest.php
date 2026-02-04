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
use JWeiland\Events2\Domain\Model\Enums\AttendanceModeEnum;
use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Search;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for DayRepository
 */
class DayRepositoryTest extends FunctionalTestCase
{
    use InsertEventTrait;

    protected DayRepository $dayRepository;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'phpTimeZone' => Events2Constants::PHP_TIMEZONE,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $querySettings = $this->get(QuerySettingsInterface::class);
        $querySettings->setStoragePageIds([Events2Constants::PAGE_STORAGE]);

        $this->dayRepository = $this->get(DayRepository::class);
        $this->dayRepository->setDefaultQuerySettings($querySettings);
    }

    protected function tearDown(): void
    {
        unset(
            $this->dayRepository,
        );

        parent::tearDown();
    }

    #[Test]
    public function findByIdentifierWillReturnDay(): void
    {
        $this->insertEvent(
            title: 'Birthday',
            eventBegin: new \DateTimeImmutable('today midnight'),
        );
        $this->createDayRelations();

        $day = $this->dayRepository->findByIdentifier(1);

        self::assertInstanceOf(
            Day::class,
            $day,
        );
        self::assertSame(
            'Birthday',
            $day->getEvent()->getTitle(),
        );
    }

    #[Test]
    public function getDaysForListTypeWillReturnDays(): void
    {
        $this->insertEvent(
            title: 'Event Yesterday',
            eventBegin: new \DateTimeImmutable('yesterday midnight'),
        );
        $this->insertEvent(
            title: 'Event Today',
            eventBegin: new \DateTimeImmutable('today midnight'),
        );
        $this->insertEvent(
            title: 'Event in 2 weeks',
            eventBegin: new \DateTimeImmutable('+2 weeks midnight'),
        );
        $this->createDayRelations();

        $days = $this->dayRepository->getDaysForListType('list', new Filter())->toArray();

        self::assertCount(
            2,
            $days,
        );
    }

    #[Test]
    public function getDaysForListLatestTypeWillReturnDays(): void
    {
        $this->insertEvent(
            title: 'Event Yesterday',
            eventBegin: new \DateTimeImmutable('yesterday midnight'),
        );
        $this->insertEvent(
            title: 'Event Today',
            eventBegin: new \DateTimeImmutable('today midnight'),
        );
        $this->insertEvent(
            title: 'Event in 2 weeks',
            eventBegin: new \DateTimeImmutable('+2 weeks midnight'),
        );
        $this->createDayRelations();

        $days = $this->dayRepository->getDaysForListType('listLatest', new Filter())->toArray();

        self::assertCount(
            2,
            $days,
        );
    }

    #[Test]
    public function getDaysForListTodayTypeWillReturnDays(): void
    {
        $this->insertEvent(
            title: 'Event Yesterday',
            eventBegin: new \DateTimeImmutable('yesterday midnight'),
        );
        $this->insertEvent(
            title: 'Event Today',
            eventBegin: new \DateTimeImmutable('today midnight'),
        );
        $this->insertEvent(
            title: 'Event in 2 weeks',
            eventBegin: new \DateTimeImmutable('+2 weeks midnight'),
        );
        $this->createDayRelations();

        $days = $this->dayRepository->getDaysForListType('listToday', new Filter())->toArray();

        self::assertCount(
            1,
            $days,
        );
    }

    #[Test]
    public function getDaysForListWeekTypeWillReturnDays(): void
    {
        $this->insertEvent(
            title: 'Event Monday',
            eventBegin: new \DateTimeImmutable('monday this week midnight'),
        );
        $this->insertEvent(
            title: 'Event Today',
            eventBegin: new \DateTimeImmutable('today midnight'),
        );
        $this->insertEvent(
            title: 'Event in 2 weeks',
            eventBegin: new \DateTimeImmutable('+2 weeks midnight'),
        );
        $this->createDayRelations();

        $days = $this->dayRepository->getDaysForListType('listWeek', new Filter())->toArray();

        self::assertCount(
            2,
            $days,
        );
    }

    #[Test]
    public function getDaysForListRangeTypeWillReturnDays(): void
    {
        $this->insertEvent(
            title: 'Event Yesterday',
            eventBegin: new \DateTimeImmutable('yesterday midnight'),
        );
        $this->insertEvent(
            title: 'Event Today',
            eventBegin: new \DateTimeImmutable('today midnight'),
        );
        $this->insertEvent(
            title: 'Event in 2 weeks',
            eventBegin: new \DateTimeImmutable('+2 weeks midnight'),
        );
        $this->createDayRelations();

        $days = $this->dayRepository->getDaysForListType('listRange', new Filter())->toArray();

        self::assertCount(
            2,
            $days,
        );
    }

    #[Test]
    public function getDaysForListTypeWillReturnLimitedDays(): void
    {
        $this->insertEvent(
            title: 'Event Yesterday',
            eventBegin: new \DateTimeImmutable('yesterday midnight'),
        );
        $this->insertEvent(
            title: 'Event Today',
            eventBegin: new \DateTimeImmutable('today midnight'),
        );
        $this->insertEvent(
            title: 'Event Tomorrow',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
        );
        $this->insertEvent(
            title: 'Event next week',
            eventBegin: new \DateTimeImmutable('+1 week midnight'),
        );
        $this->createDayRelations();

        $days = $this->dayRepository->getDaysForListType('list', new Filter(), 2)->toArray();

        self::assertCount(
            2,
            $days,
        );
    }

    #[Test]
    public function getDaysForListTypeWithCategoryWillReturnDays(): void
    {
        $this->insertEvent(
            title: 'Event Today',
            eventBegin: new \DateTimeImmutable('today midnight'),
        );
        $this->insertEvent(
            title: 'Event Tomorrow',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            categories: [2],
        );
        $this->insertEvent(
            title: 'Event next week',
            eventBegin: new \DateTimeImmutable('+1 week midnight'),
        );
        $this->createDayRelations();

        $this->dayRepository->setSettings([
            'categories' => '2',
        ]);

        $days = $this->dayRepository->getDaysForListType('list', new Filter())->toArray();

        self::assertCount(
            1,
            $days,
        );
        self::assertSame(
            'Event Tomorrow',
            $days[0]->getEvent()->getTitle(),
        );
    }

    #[Test]
    public function getDaysForListTypeWithFilteredOrganizerWillReturnDays(): void
    {
        $this->insertEvent(
            title: 'Event Today',
            eventBegin: new \DateTimeImmutable('today midnight'),
        );
        $this->insertEvent(
            title: 'Event Tomorrow',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            organizer: 'jweiland.net',
        );
        $this->insertEvent(
            title: 'Event next week',
            eventBegin: new \DateTimeImmutable('+1 week midnight'),
        );
        $this->createDayRelations();

        $this->dayRepository->setSettings([
            'preFilterByOrganizer' => '1',
        ]);

        $days = $this->dayRepository->getDaysForListType('list', new Filter())->toArray();

        self::assertCount(
            1,
            $days,
        );
        self::assertSame(
            'Event Tomorrow',
            $days[0]->getEvent()->getTitle(),
        );
    }

    #[Test]
    public function getDaysForListTypeWillReturnDaysWithPreFilterOrganizerPriority(): void
    {
        $this->insertEvent(
            title: 'Event Today',
            eventBegin: new \DateTimeImmutable('today midnight'),
        );
        $this->insertEvent(
            title: 'Event Tomorrow',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            organizer: 'jweiland.net',
        );
        $this->insertEvent(
            title: 'Event next week',
            eventBegin: new \DateTimeImmutable('+1 week midnight'),
            organizer: 'TYPO3',
        );
        $this->createDayRelations();

        $this->dayRepository->setSettings([
            'preFilterByOrganizer' => '2',
        ]);

        $filter = new Filter();
        $filter->setOrganizer(1);

        $days = $this->dayRepository->getDaysForListType('list', $filter)->toArray();

        self::assertCount(
            1,
            $days,
        );
        self::assertSame(
            'Event next week',
            $days[0]->getEvent()->getTitle(),
        );
    }

    #[Test]
    public function getDaysForListTypeWithOrganizerWillReturnDays(): void
    {
        $this->insertEvent(
            title: 'Event Today',
            eventBegin: new \DateTimeImmutable('today midnight'),
        );
        $this->insertEvent(
            title: 'Event Tomorrow',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            organizer: 'jweiland.net',
        );
        $this->insertEvent(
            title: 'Event next week',
            eventBegin: new \DateTimeImmutable('+1 week midnight'),
            organizer: 'TYPO3',
        );
        $this->createDayRelations();

        $filter = new Filter();
        $filter->setOrganizer(2);

        $days = $this->dayRepository->getDaysForListType('list', $filter)->toArray();

        self::assertCount(
            1,
            $days,
        );
        self::assertSame(
            'Event next week',
            $days[0]->getEvent()->getTitle(),
        );
    }

    #[Test]
    public function getDaysForListTypeWithTimestampWillReturnDays(): void
    {
        $today = new \DateTimeImmutable('today midnight');

        $this->insertEvent(
            title: 'Event Today',
            eventBegin: $today,
        );
        $this->insertEvent(
            title: 'Event Tomorrow',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
        );
        $this->insertEvent(
            title: 'Event next week',
            eventBegin: new \DateTimeImmutable('+1 week midnight'),
        );
        $this->createDayRelations();

        $filter = new Filter();
        $filter->setTimestamp((int)$today->format('U'));

        $days = $this->dayRepository->getDaysForListType('list', $filter)->toArray();

        self::assertCount(
            1,
            $days,
        );
        self::assertSame(
            'Event Today',
            $days[0]->getEvent()->getTitle(),
        );
    }

    #[Test]
    public function getDaysForListTypeWithRecurringEventWillReturnDays(): void
    {
        $this->insertEvent(
            title: 'Event Each Day',
            eventBegin: new \DateTimeImmutable('today midnight'),
            additionalFields: [
                'event_type' => 'recurring',
                'recurring_end' => (int)(new \DateTimeImmutable('+1 month midnight'))->format('U'),
                'xth' => 31,
                'weekday' => 127,
            ],
        );
        $this->createDayRelations();

        $days = $this->dayRepository->getDaysForListType('list', new Filter())->toArray();

        // Should be around 28-32
        self::assertGreaterThan(
            20,
            count($days),
        );
    }

    #[Test]
    public function getDaysForListTypeWithRecurringEventWillReturnMergedDays(): void
    {
        $this->insertEvent(
            title: 'Event Each Day',
            eventBegin: new \DateTimeImmutable('today midnight'),
            additionalFields: [
                'event_type' => 'recurring',
                'recurring_end' => (int)(new \DateTimeImmutable('+1 month midnight'))->format('U'),
                'xth' => 31,
                'weekday' => 127,
            ],
        );
        $this->createDayRelations();

        $this->dayRepository->setSettings([
            'mergeRecurringEvents' => '1',
        ]);

        $days = $this->dayRepository->getDaysForListType('list', new Filter())->toArray();

        self::assertCount(
            1,
            $days,
        );
    }

    #[Test]
    public function getDaysForListTypeWithRecurringEventAndMultipleTimesWillReturnDays(): void
    {
        $eventUid = $this->insertEvent(
            title: 'Event Each Day',
            eventBegin: new \DateTimeImmutable('today midnight'),
            additionalFields: [
                'event_type' => 'recurring',
                'recurring_end' => (int)(new \DateTimeImmutable('+1 month midnight'))->format('U'),
                'xth' => 31,
                'weekday' => 127,
                'multiple_times' => 2,
            ],
        );

        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_time');
        $connection->insert(
            'tx_events2_domain_model_time',
            [
                'pid' => Events2Constants::PAGE_STORAGE,
                'type' => 'multiple_times',
                'time_begin' => '08:00',
                'event' => $eventUid,
                'exception' => 0,
            ],
        );

        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_time');
        $connection->insert(
            'tx_events2_domain_model_time',
            [
                'pid' => Events2Constants::PAGE_STORAGE,
                'type' => 'multiple_times',
                'time_begin' => '20:00',
                'event' => $eventUid,
                'exception' => 0,
            ],
        );

        $this->createDayRelations();

        $days = $this->dayRepository->getDaysForListType('list', new Filter())->toArray();

        // Should be around 56-64
        self::assertGreaterThan(
            50,
            count($days),
        );
    }

    #[Test]
    public function getDaysForListTypeWithRecurringEventAndMultipleTimesWillReturnMergedDays(): void
    {
        $eventUid = $this->insertEvent(
            title: 'Event Each Day',
            eventBegin: new \DateTimeImmutable('today midnight'),
            additionalFields: [
                'event_type' => 'recurring',
                'recurring_end' => (int)(new \DateTimeImmutable('+1 month midnight'))->format('U'),
                'xth' => 31,
                'weekday' => 127,
                'multiple_times' => 2,
            ],
        );

        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_time');
        $connection->insert(
            'tx_events2_domain_model_time',
            [
                'pid' => Events2Constants::PAGE_STORAGE,
                'type' => 'multiple_times',
                'time_begin' => '08:00',
                'event' => $eventUid,
                'exception' => 0,
            ],
        );

        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_time');
        $connection->insert(
            'tx_events2_domain_model_time',
            [
                'pid' => Events2Constants::PAGE_STORAGE,
                'type' => 'multiple_times',
                'time_begin' => '20:00',
                'event' => $eventUid,
                'exception' => 0,
            ],
        );

        $this->createDayRelations();

        $this->dayRepository->setSettings([
            'mergeEventsAtSameDay' => '1',
        ]);

        $days = $this->dayRepository->getDaysForListType('list', new Filter())->toArray();

        // Should be around 28-32, but not around 56-64
        self::assertLessThan(
            40,
            count($days),
        );
    }

    #[Test]
    public function searchEventsWillReturnDays(): void
    {
        $this->insertEvent(
            title: 'Event Tomorrow',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
        );
        $this->createDayRelations();

        $search = new Search();
        $search->setSearch('event');

        $days = $this->dayRepository->searchEvents($search)->toArray();

        self::assertSame(
            'Event Tomorrow',
            $days[0]->getEvent()->getTitle(),
        );
    }

    #[Test]
    public function searchEventsWithCategoryWillReturnDays(): void
    {
        $this->insertEvent(
            title: 'Event Tomorrow',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            categories: [2],
        );
        $this->createDayRelations();

        $category = new Category();
        $category->_setProperty('uid', 2);

        $search = new Search();
        $search->setMainCategory($category);

        $days = $this->dayRepository->searchEvents($search)->toArray();

        self::assertSame(
            'Event Tomorrow',
            $days[0]->getEvent()->getTitle(),
        );
    }

    #[Test]
    public function searchEventsWithPreConfiguredCategoriesWillReturnDays(): void
    {
        $this->insertEvent(
            title: 'Event Tomorrow Audi',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            categories: [2],
        );
        $this->insertEvent(
            title: 'Event Tomorrow BMW',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            categories: [3],
        );
        $this->createDayRelations();

        $search = new Search();
        $search->setSearch('event');

        $this->dayRepository->setSettings([
            'categories' => '2,3',
        ]);

        $days = $this->dayRepository->searchEvents($search)->toArray();

        self::assertCount(
            2,
            $days,
        );
    }

    #[Test]
    public function searchEventsWithEventBeginWillReturnDays(): void
    {
        $this->insertEvent(
            title: 'Event Yesterday',
            eventBegin: new \DateTimeImmutable('yesterday midnight'),
        );
        $this->insertEvent(
            title: 'Event Today',
            eventBegin: new \DateTimeImmutable('today midnight'),
        );
        $this->insertEvent(
            title: 'Event Tomorrow',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
        );
        $this->createDayRelations();

        $search = new Search();
        $search->setEventBegin((new \DateTimeImmutable('-1 week midnight'))->format('d.m.Y'));

        $days = $this->dayRepository->searchEvents($search)->toArray();

        self::assertCount(
            3,
            $days,
        );
    }

    #[Test]
    public function searchEventsWithLocationWillReturnDays(): void
    {
        $this->insertEvent(
            title: 'Event Tomorrow',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            location: 'Stuttgart',
        );
        $this->createDayRelations();

        $location = new Location();
        $location->_setProperty('uid', 1);
        $location->setLocation('Stuttgart');

        $search = new Search();
        $search->setLocation($location);

        $days = $this->dayRepository->searchEvents($search)->toArray();

        self::assertSame(
            'Event Tomorrow',
            $days[0]->getEvent()->getTitle(),
        );
    }

    #[Test]
    public function searchEventsWithInPersonAttendanceWillReturnDays(): void
    {
        $this->insertEvent(
            title: 'Event No Attendance',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: [
                'attendance_mode' => AttendanceModeEnum::EMPTY->value,
            ],
        );
        $this->insertEvent(
            title: 'Event In Person',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: [
                'attendance_mode' => AttendanceModeEnum::IN_PERSON->value,
            ],
        );
        $this->insertEvent(
            title: 'Event Online',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: [
                'attendance_mode' => AttendanceModeEnum::ONLINE->value,
            ],
        );
        $this->createDayRelations();

        $search = new Search();
        $search->setAttendanceMode(AttendanceModeEnum::IN_PERSON);

        $days = $this->dayRepository->searchEvents($search)->toArray();

        self::assertCount(
            1,
            $days,
        );
        self::assertSame(
            'Event In Person',
            $days[0]->getEvent()->getTitle(),
        );
    }

    #[Test]
    public function searchEventsWithFreeEntryWillReturnDays(): void
    {
        $this->insertEvent(
            title: 'Event Tomorrow Free',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: [
                'free_entry' => '1',
            ],
        );
        $this->insertEvent(
            title: 'Event Tomorrow Paid',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
        );
        $this->createDayRelations();

        $search = new Search();
        $search->setFreeEntry(true);

        $days = $this->dayRepository->searchEvents($search)->toArray();

        self::assertCount(
            1,
            $days,
        );
        self::assertSame(
            'Event Tomorrow Free',
            $days[0]->getEvent()->getTitle(),
        );
    }
}
