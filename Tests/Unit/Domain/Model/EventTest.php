<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Domain\Model;

use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Organizer;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Domain\Repository\UserRepository;
use JWeiland\Events2\Tests\Unit\Domain\Traits\TestTypo3PropertiesTrait;
use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Test case.
 */
class EventTest extends UnitTestCase
{
    use ProphecyTrait;

    use TestTypo3PropertiesTrait;

    protected Event $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new Event();
        $this->subject->initializeObject();
    }

    protected function tearDown(): void
    {
        unset($this->subject);

        parent::tearDown();
    }

    /**
     * @test
     */
    public function getTitleInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getTitle(),
        );
    }

    /**
     * @test
     */
    public function setTitleSetsTitle(): void
    {
        $this->subject->setTitle('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getTitle(),
        );
    }

    /**
     * @test
     */
    public function getTopOfListInitiallyReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->getTopOfList(),
        );
    }

    /**
     * @test
     */
    public function setTopOfListSetsTopOfList(): void
    {
        $this->subject->setTopOfList(true);

        self::assertTrue(
            $this->subject->getTopOfList(),
        );
    }

    /**
     * @test
     */
    public function getTeaserInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getTeaser(),
        );
    }

    /**
     * @test
     */
    public function setTeaserSetsTeaser(): void
    {
        $this->subject->setTeaser('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getTeaser(),
        );
    }

    /**
     * @test
     */
    public function setEventBeginSetsEventBegin(): void
    {
        $date = new \DateTimeImmutable();
        $this->subject->setEventBegin($date);

        self::assertEquals(
            $date,
            $this->subject->getEventBegin(),
        );
    }

    /**
     * @test
     */
    public function getEventTimeInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getEventTime());
    }

    /**
     * @test
     */
    public function getEventTimeWithoutAnyTimesInAnyRelationsResultsInTimeOfCurrentEvent(): void
    {
        $time = new Time();
        $time->setTimeBegin('09:34');
        $this->subject->setEventTime($time);

        self::assertEquals(
            $time,
            $this->subject->getEventTime(),
        );
    }

    /**
     * @test
     */
    public function setEventTimeSetsEventTime(): void
    {
        $instance = new Time();
        $this->subject->setEventTime($instance);

        self::assertSame(
            $instance,
            $this->subject->getEventTime(),
        );
    }

    /**
     * @test
     */
    public function getDaysOfEventsTakingDaysWithEqualDaysReturnsZero(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');
        $eventEnd = new \DateTimeImmutable('midnight');
        $eventEnd->modify('+20 seconds');
        $this->subject->setEventBegin($eventBegin);
        $this->subject->setEventEnd($eventEnd);

        self::assertSame(
            0,
            $this->subject->getDaysOfEventsTakingDays(),
        );
    }

    /**
     * @test
     */
    public function getDaysOfEventsTakingDaysWithNoneEventEndResultsInZero(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $this->subject->setEventBegin($eventBegin);

        self::assertSame(
            0,
            $this->subject->getDaysOfEventsTakingDays(),
        );
    }

    /**
     * @test
     */
    public function getDaysOfEventsTakingDaysWithDifferentDatesResultsInFourDays(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventEnd = new \DateTimeImmutable('+4 days'); // f.e. monday: mo + 4 = 5 days: mo->tu->we->th->fr
        $this->subject->setEventBegin($eventBegin);
        $this->subject->setEventEnd($eventEnd);

        self::assertSame(
            5,
            $this->subject->getDaysOfEventsTakingDays(),
        );
    }

    /**
     * @test
     */
    public function getEventEndInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getEventEnd());
    }

    /**
     * @test
     */
    public function setEventEndSetsEventEnd(): void
    {
        $instance = new \DateTimeImmutable();
        $this->subject->setEventEnd($instance);

        self::assertEquals(
            $instance,
            $this->subject->getEventEnd(),
        );
    }

    /**
     * @test
     */
    public function getSameDayInitiallyReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->getSameDay(),
        );
    }

    /**
     * @test
     */
    public function setSameDaySetsSameDay(): void
    {
        $this->subject->setSameDay(true);

        self::assertTrue(
            $this->subject->getSameDay(),
        );
    }

    /**
     * @test
     */
    public function getMultipleTimesInitiallyReturnsObjectStorage(): void
    {
        self::assertEquals(
            new ObjectStorage(),
            $this->subject->getMultipleTimes(),
        );
    }

    /**
     * @test
     */
    public function setMultipleTimesSetsMultipleTimes(): void
    {
        $instance = new ObjectStorage();
        $this->subject->setMultipleTimes($instance);

        self::assertSame(
            $instance,
            $this->subject->getMultipleTimes(),
        );
    }

    /**
     * @test
     */
    public function getXthInitiallyReturnsZero(): void
    {
        self::assertSame(
            0,
            $this->subject->getXth(),
        );
    }

    /**
     * @test
     */
    public function setXthSetsXth(): void
    {
        $this->subject->setXth(123456);

        self::assertSame(
            123456,
            $this->subject->getXth(),
        );
    }

    /**
     * @test
     */
    public function getWeekdayInitiallyReturnsZero(): void
    {
        self::assertSame(
            0,
            $this->subject->getWeekday(),
        );
    }

    /**
     * @test
     */
    public function setWeekdaySetsWeekday(): void
    {
        $this->subject->setWeekday(123456);

        self::assertSame(
            123456,
            $this->subject->getWeekday(),
        );
    }

    /**
     * @test
     */
    public function getDifferentTimesInitiallyReturnsObjectStorage(): void
    {
        self::assertEquals(
            new ObjectStorage(),
            $this->subject->getDifferentTimes(),
        );
    }

    /**
     * @test
     */
    public function setDifferentTimesSetsDifferentTimes(): void
    {
        $object = new Time();
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);
        $this->subject->setDifferentTimes($objectStorage);

        self::assertSame(
            $objectStorage,
            $this->subject->getDifferentTimes(),
        );
    }

    /**
     * @test
     */
    public function addDifferentTimeAddsOneDifferentTime(): void
    {
        $objectStorage = new ObjectStorage();
        $this->subject->setDifferentTimes($objectStorage);

        $object = new Time();
        $this->subject->addDifferentTime($object);

        $objectStorage->attach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getDifferentTimes(),
        );
    }

    /**
     * @test
     */
    public function removeDifferentTimeRemovesOneDifferentTime(): void
    {
        $object = new Time();

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);

        $this->subject->setDifferentTimes($objectStorage);

        $this->subject->removeDifferentTime($object);
        $objectStorage->detach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getDifferentTimes(),
        );
    }

    /**
     * @test
     */
    public function getEachWeeksInitiallyReturnsZero(): void
    {
        self::assertSame(
            0,
            $this->subject->getEachWeeks(),
        );
    }

    /**
     * @test
     */
    public function setEachWeeksSetsEachWeeks(): void
    {
        $this->subject->setEachWeeks(123456);

        self::assertSame(
            123456,
            $this->subject->getEachWeeks(),
        );
    }

    /**
     * @test
     */
    public function getEachMonthsInitiallyReturnsZero(): void
    {
        self::assertSame(
            0,
            $this->subject->getEachMonths(),
        );
    }

    /**
     * @test
     */
    public function setEachMonthsSetsEachMonths(): void
    {
        $this->subject->setEachMonths(123456);

        self::assertSame(
            123456,
            $this->subject->getEachMonths(),
        );
    }

    /**
     * @test
     */
    public function getRecurringEndInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getRecurringEnd(),
        );
    }

    /**
     * @test
     */
    public function setRecurringEndSetsRecurringEnd(): void
    {
        $date = new \DateTimeImmutable();
        $this->subject->setRecurringEnd($date);

        self::assertEquals(
            $date,
            $this->subject->getRecurringEnd(),
        );
    }

    /**
     * @test
     */
    public function getExceptionsInitiallyReturnsObjectStorage(): void
    {
        self::assertEquals(
            new ObjectStorage(),
            $this->subject->getExceptions(),
        );
    }

    /**
     * @test
     */
    public function setExceptionsSetsExceptions(): void
    {
        $object = new Exception();

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);

        $this->subject->setExceptions($objectStorage);

        self::assertSame(
            $objectStorage,
            $this->subject->getExceptions(),
        );
    }

    /**
     * @test
     */
    public function addExceptionAddsOneDifferentTime(): void
    {
        $objectStorage = new ObjectStorage();
        $this->subject->setExceptions($objectStorage);

        $object = new Exception();
        $this->subject->addException($object);

        $objectStorage->attach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getExceptions(),
        );
    }

    /**
     * @test
     */
    public function removeExceptionRemovesOneException(): void
    {
        $object = new Exception();

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);

        $this->subject->setExceptions($objectStorage);

        $this->subject->removeException($object);
        $objectStorage->detach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getExceptions(),
        );
    }

    /**
     * @test
     */
    public function getExceptionsForDateReturnZeroExceptions(): void
    {
        self::assertEquals(
            new ObjectStorage(),
            $this->subject->getExceptionsForDate(new \DateTimeImmutable()),
        );
    }

    /**
     * @test
     */
    public function getExceptionsForDateWithRemoveExceptionReturnsZeroExceptionsForAdd(): void
    {
        $date = new \DateTimeImmutable('midnight');

        $exception = new Exception();
        $exception->setExceptionType('Remove');
        $exception->setExceptionDate($date);

        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

        $this->subject->setExceptions($exceptions);

        self::assertEquals(
            new ObjectStorage(),
            $this->subject->getExceptionsForDate($date, 'Add'),
        );
    }

    /**
     * @test
     */
    public function getExceptionsForDateWithRemoveExceptionReturnsOneRemoveException(): void
    {
        $date = new \DateTimeImmutable('midnight');

        $exception = new Exception();
        $exception->setExceptionType('Remove');
        $exception->setExceptionDate($date);

        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

        $this->subject->setEventType('duration');
        $this->subject->setExceptions($exceptions);

        $expectedExceptions = new ObjectStorage();
        $expectedExceptions->attach($exception);

        self::assertEquals(
            $expectedExceptions,
            $this->subject->getExceptionsForDate($date, 'Remove'),
        );
    }

    /**
     * @test
     */
    public function getExceptionsForDateWithRemoveExceptionWithNonNormalizedDateReturnsOneRemoveException(): void
    {
        $date = new \DateTimeImmutable('now'); // date must be sanitized to midnight in getExceptionsForDate

        $exception = new Exception();
        $exception->setExceptionType('Remove');
        $exception->setExceptionDate($date);

        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

        $this->subject->setEventType('recurring');
        $this->subject->setExceptions($exceptions);

        $expectedExceptions = new ObjectStorage();
        $expectedExceptions->attach($exception);

        self::assertEquals(
            $expectedExceptions,
            $this->subject->getExceptionsForDate($date, 'Remove'),
        );
    }

    /**
     * @test
     */
    public function getExceptionsForDateWithDifferentExceptionsReturnsAddException(): void
    {
        $date = new \DateTimeImmutable('midnight');

        $removeException = new Exception();
        $removeException->setExceptionType('Remove');
        $removeException->setExceptionDate($date);

        $addException = new Exception();
        $addException->setExceptionType('Add');
        $addException->setExceptionDate($date);

        $exceptions = new ObjectStorage();
        $exceptions->attach($removeException);
        $exceptions->attach($addException);

        $this->subject->setEventType('duration');
        $this->subject->setExceptions($exceptions);

        $expectedAddExceptions = new ObjectStorage();
        $expectedAddExceptions->attach($addException);

        self::assertEquals(
            $expectedAddExceptions,
            $this->subject->getExceptionsForDate($date, 'Add'),
        );
    }

    /**
     * @test
     */
    public function getExceptionsForDateWithExceptionsOfDifferentDatesReturnsAddException(): void
    {
        $firstDate = new \DateTimeImmutable('midnight');
        $secondDate = new \DateTimeImmutable('tomorrow midnight');

        $firstAddException = new Exception();
        $firstAddException->setExceptionType('Add');
        $firstAddException->setExceptionDate($firstDate);

        $secondAddException = new Exception();
        $secondAddException->setExceptionType('Add');
        $secondAddException->setExceptionDate($secondDate);

        $exceptions = new ObjectStorage();
        $exceptions->attach($firstAddException);
        $exceptions->attach($secondAddException);

        $this->subject->setEventType('recurring');
        $this->subject->setExceptions($exceptions);

        $expectedAddExceptions = new ObjectStorage();
        $expectedAddExceptions->attach($firstAddException);

        self::assertEquals(
            $expectedAddExceptions,
            $this->subject->getExceptionsForDate($firstDate, 'Add'),
        );
    }

    /**
     * This test also checks against lowercased and multiple spaces in list of exception types
     *
     * @test
     */
    public function getExceptionsForDateWithExceptionsOfDifferentDatesReturnsDifferentExceptions(): void
    {
        $firstDate = new \DateTimeImmutable('midnight');
        $secondDate = new \DateTimeImmutable('tomorrow midnight');

        $firstAddException = new Exception();
        $firstAddException->setExceptionType('Add');
        $firstAddException->setExceptionDate($firstDate);

        $secondAddException = new Exception();
        $secondAddException->setExceptionType('Add');
        $secondAddException->setExceptionDate($secondDate);

        $timeException = new Exception();
        $timeException->setExceptionType('Time');
        $timeException->setExceptionDate($firstDate);

        $infoException = new Exception();
        $infoException->setExceptionType('Info');
        $infoException->setExceptionDate($firstDate);

        $exceptions = new ObjectStorage();
        $exceptions->attach($firstAddException);
        $exceptions->attach($secondAddException);
        $exceptions->attach($timeException);
        $exceptions->attach($infoException);

        $this->subject->setEventType('duration');
        $this->subject->setExceptions($exceptions);

        $expectedExceptions = new ObjectStorage();
        $expectedExceptions->attach($firstAddException);
        $expectedExceptions->attach($timeException);
        $expectedExceptions->attach($infoException);

        self::assertEquals(
            $expectedExceptions,
            $this->subject->getExceptionsForDate($firstDate, 'add, time,  info'),
        );
    }

    /**
     * @test
     */
    public function getDetailInformationInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getDetailInformation(),
        );
    }

    /**
     * @test
     */
    public function setDetailInformationSetsDetailInformation(): void
    {
        $this->subject->setDetailInformation('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getDetailInformation(),
        );
    }

    /**
     * @test
     */
    public function getFreeEntryInitiallyReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->getFreeEntry(),
        );
    }

    /**
     * @test
     */
    public function setFreeEntrySetsFreeEntry(): void
    {
        $this->subject->setFreeEntry(true);

        self::assertTrue(
            $this->subject->getFreeEntry(),
        );
    }

    /**
     * @test
     */
    public function getTicketLinkInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getTicketLink());
    }

    /**
     * @test
     */
    public function setTicketLinkSetsTicketLink(): void
    {
        $instance = new Link();
        $this->subject->setTicketLink($instance);

        self::assertSame(
            $instance,
            $this->subject->getTicketLink(),
        );
    }

    /**
     * @test
     */
    public function getCategoriesInitiallyReturnsObjectStorage(): void
    {
        self::assertEquals(
            new ObjectStorage(),
            $this->subject->getCategories(),
        );
    }

    /**
     * @test
     */
    public function setCategoriesSetsCategories(): void
    {
        $object = new Category();

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);

        $this->subject->setCategories($objectStorage);

        self::assertSame(
            $objectStorage,
            $this->subject->getCategories(),
        );
    }

    /**
     * @test
     */
    public function addCategoryAddsOneCategory(): void
    {
        $objectStorage = new ObjectStorage();
        $this->subject->setCategories($objectStorage);

        $object = new Category();
        $this->subject->addCategory($object);

        $objectStorage->attach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getCategories(),
        );
    }

    /**
     * @test
     */
    public function removeCategoryRemovesOneCategory(): void
    {
        $object = new Category();

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);

        $this->subject->setCategories($objectStorage);

        $this->subject->removeCategory($object);
        $objectStorage->detach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getCategories(),
        );
    }

    /**
     * @test
     */
    public function getCategoryListReturnsCommaSeparatedList(): void
    {
        for ($i = 1; $i < 4; ++$i) {
            /* @var Category|\PHPUnit_Framework_MockObject_MockObject|AccessibleMockObjectInterface $category */
            $category = $this->getAccessibleMock(Category::class, ['dummy']);
            $category->_set('uid', $i);
            $this->subject->addCategory($category);
        }
        self::assertSame(
            [1, 2, 3],
            $this->subject->getCategoryUids(),
        );
    }

    /**
     * @test
     */
    public function getDaysInitiallyReturnsObjectStorage(): void
    {
        self::assertEquals(
            new ObjectStorage(),
            $this->subject->getDays(),
        );
    }

    /**
     * @test
     */
    public function setDaysSetsDays(): void
    {
        $object = new Day();

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);

        $this->subject->setDays($objectStorage);

        self::assertSame(
            $objectStorage,
            $this->subject->getDays(),
        );
    }

    /**
     * @test
     */
    public function addDayAddsOneDifferentTime(): void
    {
        $objectStorage = new ObjectStorage();
        $this->subject->setDays($objectStorage);

        $object = new Day();
        $this->subject->addDay($object);

        $objectStorage->attach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getDays(),
        );
    }

    /**
     * @test
     */
    public function removeDayRemovesOneDay(): void
    {
        $object = new Day();

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);

        $this->subject->setDays($objectStorage);

        $this->subject->removeDay($object);
        $objectStorage->detach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getDays(),
        );
    }

    /**
     * @test
     */
    public function getFutureDatesGroupedAndSortedReturnsFutureDatesOnly(): void
    {
        $yesterday = new \DateTimeImmutable('yesterday');
        $today = new \DateTimeImmutable('now');
        $future = new \DateTimeImmutable('tomorrow');

        $yesterdayDay = new Day();
        $yesterdayDay->setDay(new \DateTimeImmutable('yesterday midnight'));
        $yesterdayDay->setDayTime($yesterday);

        $todayDay = new Day();
        $todayDay->setDay(new \DateTimeImmutable('midnight'));
        $todayDay->setDayTime($today);

        $futureDay = new Day();
        $futureDay->setDay(new \DateTimeImmutable('tomorrow midnight'));
        $futureDay->setDayTime($future);

        $days = new ObjectStorage();
        $days->attach($yesterdayDay);
        $days->attach($todayDay);
        $days->attach($futureDay);

        $this->subject->setDays($days);
        $futureDays = $this->subject->getFutureDatesGroupedAndSorted();

        self::assertCount(
            2,
            $futureDays,
        );
    }

    /**
     * @test
     */
    public function getFutureDatesGroupedAndSortedReturnsDatesGroupedAndSorted(): void
    {
        $today1 = new \DateTimeImmutable('now 12:00:00');
        $today2 = new \DateTimeImmutable('now 20:00:00');
        $future1 = new \DateTimeImmutable('tomorrow 12:00:00');
        $future2 = new \DateTimeImmutable('tomorrow 20:00:00');

        $today1Day = new Day();
        $today1Day->setDay(new \DateTimeImmutable('midnight'));
        $today1Day->setDayTime($today1);

        $today2Day = new Day();
        $today2Day->setDay(new \DateTimeImmutable('midnight'));
        $today2Day->setDayTime($today2);

        $future1Day = new Day();
        $future1Day->setDay(new \DateTimeImmutable('tomorrow midnight'));
        $future1Day->setDayTime($future1);

        $future2Day = new Day();
        $future2Day->setDay(new \DateTimeImmutable('tomorrow midnight'));
        $future2Day->setDayTime($future2);

        $days = new ObjectStorage();
        $days->attach($future2Day);
        $days->attach($today1Day);
        $days->attach($future1Day);
        $days->attach($today2Day);

        $this->subject->setDays($days);
        $futureDays = $this->subject->getFutureDatesGroupedAndSorted();

        self::assertCount(
            2,
            $futureDays,
        );

        self::assertSame(
            sprintf(
                '%d,%d',
                $today1->modify('midnight')->format('U'),
                $future1->modify('midnight')->format('U'),
            ),
            implode(',', array_keys($futureDays)),
        );

        // Check, if pointer of array was moved to position 1
        self::assertEquals(
            $today1->modify('midnight'),
            current($futureDays),
        );
    }

    /**
     * @test
     */
    public function getFutureDatesIncludingRemovedGroupedAndSortedReturnsFutureDatesSorted(): void
    {
        $yesterday = new \DateTimeImmutable('yesterday');

        $today1 = new \DateTimeImmutable('now 12:00:00');
        $today2 = new \DateTimeImmutable('now 20:00:00');

        $future1 = new \DateTimeImmutable('tomorrow 12:00:00');
        $future2 = new \DateTimeImmutable('tomorrow 20:00:00');

        $yesterdayDay = new Day();
        $yesterdayDay->setDay(new \DateTimeImmutable('yesterday midnight'));
        $yesterdayDay->setDayTime($yesterday);

        $today1Day = new Day();
        $today1Day->setDay(new \DateTimeImmutable('midnight'));
        $today1Day->setDayTime($today1);

        $today2Day = new Day();
        $today2Day->setDay(new \DateTimeImmutable('midnight'));
        $today2Day->setDayTime($today2);

        $future1Day = new Day();
        $future1Day->setDay(new \DateTimeImmutable('tomorrow midnight'));
        $future1Day->setDayTime($future1);

        $future2Day = new Day();
        $future2Day->setDay(new \DateTimeImmutable('tomorrow midnight'));
        $future2Day->setDayTime($future2);

        $days = new ObjectStorage();
        $days->attach($future1Day);
        $days->attach($future2Day);
        $days->attach($today1Day);
        $days->attach($today2Day);
        $days->attach($yesterdayDay);

        $exception = new Exception();
        $exception->setExceptionType('remove');
        $exception->setExceptionDate(new \DateTimeImmutable('tomorrow midnight'));

        $this->subject->setDays($days);
        $this->subject->addException($exception);
        $futureDays = $this->subject->getFutureDatesGroupedAndSorted();

        self::assertCount(
            2,
            $futureDays,
        );

        self::assertSame(
            sprintf(
                '%d,%d',
                $today1->modify('midnight')->format('U'),
                $future1->modify('midnight')->format('U'),
            ),
            implode(',', array_keys($futureDays)),
        );

        // Check, if pointer of array was moved to position 1
        self::assertEquals(
            $today1->modify('midnight'),
            current($futureDays),
        );
    }

    /**
     * @test
     */
    public function getLocationInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getLocation());
    }

    /**
     * @test
     */
    public function setLocationSetsLocation(): void
    {
        $instance = new Location();
        $this->subject->setLocation($instance);

        self::assertSame(
            $instance,
            $this->subject->getLocation(),
        );
    }

    /**
     * @test
     */
    public function getOrganizersInitiallyReturnsObjectStorage(): void
    {
        self::assertEquals(
            new ObjectStorage(),
            $this->subject->getOrganizers(),
        );
    }

    /**
     * @test
     */
    public function setOrganizersSetsOrganizers(): void
    {
        $object = new Organizer();

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);

        $this->subject->setOrganizers($objectStorage);

        self::assertSame(
            $objectStorage,
            $this->subject->getOrganizers(),
        );
    }

    /**
     * @test
     */
    public function addOrganizerAddsOneOrganizer(): void
    {
        $objectStorage = new ObjectStorage();
        $this->subject->setOrganizers($objectStorage);

        $object = new Organizer();
        $this->subject->addOrganizer($object);

        $objectStorage->attach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getOrganizers(),
        );
    }

    /**
     * @test
     */
    public function removeOrganizerRemovesOneOrganizer(): void
    {
        $object = new Organizer();

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);

        $this->subject->setOrganizers($objectStorage);

        $this->subject->removeOrganizer($object);
        $objectStorage->detach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getOrganizers(),
        );
    }

    /**
     * @test
     */
    public function getFirstOrganizerWillReturnFirstOrganizer(): void
    {
        $organizer1 = new Organizer();
        $organizer1->setOrganizer('First Organizer');

        $this->subject->addOrganizer($organizer1);

        $organizer2 = new Organizer();
        $organizer2->setOrganizer('Second Organizer');

        $this->subject->addOrganizer($organizer2);

        self::assertSame(
            $organizer1,
            $this->subject->getFirstOrganizer(),
        );
    }

    /**
     * @return array<string, array<int|bool>>
     */
    public function events2OrganizerDataProvider(): array
    {
        return [
            'User with valid organizer will return true' => [2, true],
            'User with invalid organizer will return false' => [5, false],
            'User with non given organizer will return false' => [0, false],
        ];
    }

    /**
     * @test
     * @dataProvider events2OrganizerDataProvider
     */
    public function isCurrentUserAllowedOrganizerReturnsTrue(
        int $organizerUid,
        bool $expected,
    ): void {
        /** @var UserRepository|ObjectProphecy $userRepositoryProphecy */
        $userRepositoryProphecy = $this->prophesize(UserRepository::class);
        $userRepositoryProphecy
            ->getFieldFromUser('tx_events2_organizer')
            ->shouldBeCalled()
            ->willReturn($organizerUid);

        GeneralUtility::addInstance(
            UserRepository::class,
            $userRepositoryProphecy->reveal(),
        );

        $organizer1 = new Organizer();
        $organizer1->_setProperty('uid', 1);
        $organizer1->setOrganizer('First Organizer');

        $this->subject->addOrganizer($organizer1);

        $organizer2 = new Organizer();
        $organizer2->_setProperty('uid', 2);
        $organizer2->setOrganizer('Second Organizer');

        $this->subject->addOrganizer($organizer2);

        self::assertSame(
            $expected,
            $this->subject->getIsCurrentUserAllowedOrganizer(),
        );
    }

    /**
     * @test
     */
    public function getImagesInitiallyReturnsArray(): void
    {
        self::assertEquals(
            [],
            $this->subject->getImages(),
        );
    }

    /**
     * @test
     */
    public function setImagesSetsImages(): void
    {
        $object = new Time();

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);

        $this->subject->setImages($objectStorage);

        self::assertSame(
            [0 => $object],
            $this->subject->getImages(),
        );
    }

    /**
     * @test
     */
    public function getVideoLinkInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getVideoLink());
    }

    /**
     * @test
     */
    public function setVideoLinkSetsVideoLink(): void
    {
        $instance = new Link();
        $this->subject->setVideoLink($instance);

        self::assertSame(
            $instance,
            $this->subject->getVideoLink(),
        );
    }

    /**
     * @test
     */
    public function getDownloadLinksInitiallyReturnsObjectStorage(): void
    {
        self::assertEquals(
            new ObjectStorage(),
            $this->subject->getDownloadLinks(),
        );
    }

    /**
     * @test
     */
    public function setDownloadLinksSetsDownloadLinks(): void
    {
        $object = new Link();

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);

        $this->subject->setDownloadLinks($objectStorage);

        self::assertSame(
            $objectStorage,
            $this->subject->getDownloadLinks(),
        );
    }

    /**
     * @test
     */
    public function addDownloadLinkAddsOneDownloadLink(): void
    {
        $objectStorage = new ObjectStorage();
        $this->subject->setDownloadLinks($objectStorage);

        $object = new Link();
        $this->subject->addDownloadLink($object);

        $objectStorage->attach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getDownloadLinks(),
        );
    }

    /**
     * @test
     */
    public function removeDownloadLinkRemovesOneDownloadLink(): void
    {
        $object = new Link();

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);

        $this->subject->setDownloadLinks($objectStorage);

        $this->subject->removeDownloadLink($object);
        $objectStorage->detach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getDownloadLinks(),
        );
    }
}
