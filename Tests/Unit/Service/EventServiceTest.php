<?php

namespace JWeiland\Events2\Tests\Unit\Service;

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

use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Service\EventService;
use JWeiland\Events2\Utility\DateTimeUtility;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Test case for class \JWeiland\Events2\Service\EventService
 */
class EventServiceTest extends UnitTestCase
{
    /**
     * @var EventService
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = new EventService();
        $this->subject->injectDateTimeUtility(new DateTimeUtility());
    }

    /**
     * tear down.
     */
    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getExceptionsForDateReturnZeroExceptions()
    {
        $event = new Event();

        $this->assertEquals(
            new \SplObjectStorage(),
            $this->subject->getExceptionsForDate($event, new \DateTime())
        );
    }

    /**
     * @test
     */
    public function getExceptionsForDateWithRemoveExceptionReturnsZeroExceptionsForAdd()
    {
        $date = new \DateTime('midnight');

        $exception = new Exception();
        $exception->setExceptionType('Remove');
        $exception->setExceptionDate($date);

        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

        $event = new Event();
        $event->setExceptions($exceptions);

        $this->assertEquals(
            new \SplObjectStorage(),
            $this->subject->getExceptionsForDate($event, $date, 'Add')
        );
    }

    /**
     * @test
     */
    public function getExceptionsForDateWithRemoveExceptionReturnsOneRemoveException()
    {
        $date = new \DateTime('midnight');

        $exception = new Exception();
        $exception->setExceptionType('Remove');
        $exception->setExceptionDate($date);

        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

        $event = new Event();
        $event->setExceptions($exceptions);

        $expectedExceptions = new \SplObjectStorage();
        $expectedExceptions->attach($exception);

        $this->assertEquals(
            $expectedExceptions,
            $this->subject->getExceptionsForDate($event, $date, 'Remove')
        );
    }

    /**
     * @test
     */
    public function getExceptionsForDateWithRemoveExceptionWithNonNormalizedDateReturnsOneRemoveException()
    {
        $date = new \DateTime('now'); // date must be sanitized to midnight in getExceptionsForDate

        $exception = new Exception();
        $exception->setExceptionType('Remove');
        $exception->setExceptionDate($date);

        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

        $event = new Event();
        $event->setExceptions($exceptions);

        $expectedExceptions = new \SplObjectStorage();
        $expectedExceptions->attach($exception);

        $this->assertEquals(
            $expectedExceptions,
            $this->subject->getExceptionsForDate($event, $date, 'Remove')
        );
    }

    /**
     * @test
     */
    public function getExceptionsForDateWithDifferentExceptionsReturnsAddException()
    {
        $date = new \DateTime('midnight');

        $removeException = new Exception();
        $removeException->setExceptionType('Remove');
        $removeException->setExceptionDate($date);
        $addException = new Exception();
        $addException->setExceptionType('Add');
        $addException->setExceptionDate($date);

        $exceptions = new ObjectStorage();
        $exceptions->attach($removeException);
        $exceptions->attach($addException);

        $event = new Event();
        $event->setExceptions($exceptions);

        $expectedAddExceptions = new \SplObjectStorage();
        $expectedAddExceptions->attach($addException);

        $this->assertEquals(
            $expectedAddExceptions,
            $this->subject->getExceptionsForDate($event, $date, 'Add')
        );
    }

    /**
     * @test
     */
    public function getExceptionsForDateWithExceptionsOfDifferentDatesReturnsAddException()
    {
        $firstDate = new \DateTime('midnight');
        $secondDate = new \DateTime('midnight');
        $secondDate->modify('tomorrow');

        $firstAddException = new Exception();
        $firstAddException->setExceptionType('Add');
        $firstAddException->setExceptionDate($firstDate);
        $secondAddException = new Exception();
        $secondAddException->setExceptionType('Add');
        $secondAddException->setExceptionDate($secondDate);

        $exceptions = new ObjectStorage();
        $exceptions->attach($firstAddException);
        $exceptions->attach($secondAddException);

        $event = new Event();
        $event->setExceptions($exceptions);

        $expectedAddExceptions = new \SplObjectStorage();
        $expectedAddExceptions->attach($firstAddException);

        $this->assertEquals(
            $expectedAddExceptions,
            $this->subject->getExceptionsForDate($event, $firstDate, 'Add')
        );
    }

    /**
     * This test also checks against lowercased and multiple spaces in list of exception types
     *
     * @test
     */
    public function getExceptionsForDateWithExceptionsOfDifferentDatesReturnsDifferentExceptions()
    {
        $firstDate = new \DateTime('midnight');
        $secondDate = new \DateTime('midnight');
        $secondDate->modify('tomorrow');

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

        $event = new Event();
        $event->setExceptions($exceptions);

        $expectedExceptions = new \SplObjectStorage();
        $expectedExceptions->attach($firstAddException);
        $expectedExceptions->attach($timeException);
        $expectedExceptions->attach($infoException);

        $this->assertEquals(
            $expectedExceptions,
            $this->subject->getExceptionsForDate($event, $firstDate, 'add, time,  info')
        );
    }

    /**
     * @test
     */
    public function getTimesForDateWithoutAnyTimesReturnsNoTimes()
    {
        $firstDate = new \DateTime('midnight');
        $secondDate = new \DateTime('midnight');
        $secondDate->modify('tomorrow');

        $firstAddException = new Exception();
        $firstAddException->setExceptionType('Add');
        $firstAddException->setExceptionDate($firstDate);
        $secondAddException = new Exception();
        $secondAddException->setExceptionType('Add');
        $secondAddException->setExceptionDate($secondDate);

        $exceptions = new ObjectStorage();
        $exceptions->attach($firstAddException);
        $exceptions->attach($secondAddException);

        $event = new Event();
        $event->setExceptions($exceptions);

        $expectedTimes = new \SplObjectStorage();

        $this->assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate)
        );
    }

    /**
     * @test
     */
    public function getTimesForDateWithExceptionsWithTimeReturnsTimes()
    {
        $firstDate = new \DateTime('midnight');
        $secondDate = new \DateTime('midnight');
        $secondDate->modify('tomorrow');

        $time = new Time();
        $time->setTimeBegin('10:30');

        $firstAddException = new Exception();
        $firstAddException->setExceptionType('Add');
        $firstAddException->setExceptionDate($firstDate);
        $firstAddException->setExceptionTime($time);
        $secondAddException = new Exception();
        $secondAddException->setExceptionType('Add');
        $secondAddException->setExceptionDate($secondDate);
        $secondAddException->setExceptionTime($time);

        $exceptions = new ObjectStorage();
        $exceptions->attach($firstAddException);
        $exceptions->attach($secondAddException);

        $event = new Event();
        $event->setExceptions($exceptions);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);

        $this->assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate)
        );
    }

    /**
     * @test
     */
    public function getTimesForDateWithDifferentExceptionTypesWithTimeReturnsTimes()
    {
        $firstDate = new \DateTime('midnight');

        $time = new Time();
        $time->setTimeBegin('10:30');

        $firstAddException = new Exception();
        $firstAddException->setExceptionType('Add');
        $firstAddException->setExceptionDate($firstDate);
        $firstAddException->setExceptionTime($time);
        $secondAddException = new Exception();
        $secondAddException->setExceptionType('Info');
        $secondAddException->setExceptionDate($firstDate);
        $secondAddException->setExceptionTime($time);

        $exceptions = new ObjectStorage();
        $exceptions->attach($firstAddException);
        $exceptions->attach($secondAddException);

        $event = new Event();
        $event->setExceptions($exceptions);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);
        $expectedTimes->attach($time);

        $this->assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate)
        );
    }

    /**
     * @test
     */
    public function getTimesForDateWithDifferentTimesAndSingleEventReturnsNoTimes()
    {
        $firstDate = new \DateTime('midnight');

        $time = new Time();
        $time->setTimeBegin('10:30');

        $times = new ObjectStorage();
        $times->attach($time);

        $event = new Event();
        $event->setEventType('single');
        $event->setDifferentTimes($times);

        $expectedTimes = new \SplObjectStorage();

        $this->assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate)
        );
    }

    /**
     * @test
     */
    public function getTimesForDateWithDifferentTimesOnDifferentWeekdayAndRecurringEventReturnsNoTimes()
    {
        $firstDate = new \DateTime('midnight');
        $secondDate = new \DateTime('midnight');
        $secondDate->modify('tomorrow');

        $time = new Time();
        $time->setTimeBegin('10:30');
        $time->setWeekday($secondDate->format('l'));

        $times = new ObjectStorage();
        $times->attach($time);

        $event = new Event();
        $event->setEventType('recurring');
        $event->setDifferentTimes($times);

        $expectedTimes = new \SplObjectStorage();

        $this->assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate)
        );
    }

    /**
     * @test
     */
    public function getTimesForDateWithDifferentTimesAndRecurringEventReturnsTimes()
    {
        $firstDate = new \DateTime('midnight');

        $time = new Time();
        $time->setTimeBegin('10:30');
        $time->setWeekday($firstDate->format('l'));

        $times = new ObjectStorage();
        $times->attach($time);

        $event = new Event();
        $event->setEventType('recurring');
        $event->setDifferentTimes($times);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);

        $this->assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate)
        );
    }

    /**
     * @test
     */
    public function getTimesForDateWithDifferentTimesAndSingleEventReturnsOneTime()
    {
        $firstDate = new \DateTime('midnight');

        $time = new Time();
        $time->setTimeBegin('10:30');

        $times = new ObjectStorage();
        $times->attach($time);

        $event = new Event();
        $event->setEventType('single');
        $event->setDifferentTimes($times);
        $event->setEventTime($time);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);

        $this->assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate)
        );
    }

    /**
     * @test
     */
    public function getTimesForDateWithEventTimeReturnsOneTime()
    {
        $firstDate = new \DateTime('midnight');

        $time = new Time();
        $time->setTimeBegin('10:30');

        $event = new Event();
        $event->setEventType('single');
        $event->setEventTime($time);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);

        $this->assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate)
        );
    }

    /**
     * @test
     */
    public function getTimesForDateWithExceptionsWithoutTimesButWithEventTimeReturnsOneTime()
    {
        $firstDate = new \DateTime('midnight');
        $secondDate = new \DateTime('midnight');
        $secondDate->modify('tomorrow');

        $time = new Time();
        $time->setTimeBegin('10:30');

        $firstAddException = new Exception();
        $firstAddException->setExceptionType('Add');
        $firstAddException->setExceptionDate($firstDate);
        $secondAddException = new Exception();
        $secondAddException->setExceptionType('Add');
        $secondAddException->setExceptionDate($secondDate);

        $exceptions = new ObjectStorage();
        $exceptions->attach($firstAddException);
        $exceptions->attach($secondAddException);

        $event = new Event();
        $event->setExceptions($exceptions);
        $event->setEventTime($time);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);

        $this->assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate)
        );
    }

    /**
     * @test
     */
    public function getSortedTimesForDateWithoutTimesReturnsEmptyStorage()
    {
        $date = new \DateTime('midnight');

        $event = new Event();

        $expectedTimes = new \SplObjectStorage();

        $this->assertEquals(
            $expectedTimes,
            $this->subject->getSortedTimesForDate($event, $date)
        );
    }

    /**
     * @test
     */
    public function getSortedTimesForDateWithEventTimeReturnsOneSortedTime()
    {
        $date = new \DateTime('midnight');

        $time = new Time();
        $time->setTimeBegin('10:30');

        $event = new Event();
        $event->setEventTime($time);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);

        $this->assertEquals(
            $expectedTimes,
            $this->subject->getSortedTimesForDate($event, $date)
        );
    }

    /**
     * @test
     */
    public function getSortedTimesForDateWithDifferentTimesReturnsSortedTimes()
    {
        $date = new \DateTime('midnight');
        $tomorrow = new \DateTime('midnight');
        $tomorrow->modify('tomorrow');

        $time1 = new Time();
        $time1->setWeekday($date->format('l'));
        $time1->setTimeBegin('23:56');
        $time2 = new Time();
        $time2->setWeekday($date->format('l'));
        $time2->setTimeBegin('12:34');
        $time3 = new Time();
        $time3->setWeekday($date->format('l'));
        $time3->setTimeBegin('00:34');
        $time4 = new Time();
        $time4->setWeekday($tomorrow->format('l'));
        $time4->setTimeBegin('10:35');

        $times = new ObjectStorage();
        $times->attach($time1);
        $times->attach($time2);
        $times->attach($time3);
        $times->attach($time4);

        $event = new Event();
        $event->setEventType('recurring');
        $event->setDifferentTimes($times);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time3);
        $expectedTimes->attach($time2);
        $expectedTimes->attach($time1);

        $this->assertEquals(
            $expectedTimes,
            $this->subject->getSortedTimesForDate($event, $date)
        );
    }

    /**
     * @test
     */
    public function getSortedTimesForDateWithTimeExceptionsReturnsSortedTimes()
    {
        $date = new \DateTime('midnight');
        $tomorrow = new \DateTime('midnight');
        $tomorrow->modify('tomorrow');

        $time1 = new Time();
        $time1->setTimeBegin('23:56');
        $time2 = new Time();
        $time2->setTimeBegin('12:34');
        $time3 = new Time();
        $time3->setTimeBegin('00:34');
        $time4 = new Time();
        $time4->setTimeBegin('10:35');

        $exception1 = new Exception();
        $exception1->setExceptionType('Add');
        $exception1->setExceptionDate($date);
        $exception1->setExceptionTime($time1);
        $exception2 = new Exception();
        $exception2->setExceptionType('Time');
        $exception2->setExceptionDate($date);
        $exception2->setExceptionTime($time2);
        $exception3 = new Exception();
        $exception3->setExceptionType('Add');
        $exception3->setExceptionDate($date);
        $exception3->setExceptionTime($time3);
        $exception4 = new Exception();
        $exception4->setExceptionType('Add');
        $exception4->setExceptionDate($tomorrow);
        $exception4->setExceptionTime($time4);

        $exceptions = new ObjectStorage();
        $exceptions->attach($exception1);
        $exceptions->attach($exception2);
        $exceptions->attach($exception3);
        $exceptions->attach($exception4);

        $event = new Event();
        $event->setExceptions($exceptions);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time3);
        $expectedTimes->attach($time2);
        $expectedTimes->attach($time1);

        $this->assertEquals(
            $expectedTimes,
            $this->subject->getSortedTimesForDate($event, $date)
        );
    }

    /**
     * @test
     */
    public function getNextDayForEventWithoutEventReturnsFalse()
    {
        /** @var EventRepository|ObjectProphecy $eventRepository */
        $eventRepository = $this->prophesize(EventRepository::class);
        $eventRepository
            ->findByIdentifier(1)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->subject->injectEventRepository($eventRepository->reveal());

        $this->assertFalse(
            $this->subject->getNextDayForEvent(1)
        );
    }

    /**
     * @test
     */
    public function getNextDayForEventWithEventButWithoutFutureDaysReturnsFalse()
    {
        $yesterday = new \DateTime('yesterday midnight');
        $yesterdayWithTime = new \DateTime('yesterday');

        $day = new Day();
        $day->setDay($yesterday);
        $day->setDayTime($yesterdayWithTime);
        $day->setSortDayTime($yesterdayWithTime);

        $days = new ObjectStorage();
        $days->attach($day);

        $event = new Event();
        $event->setDays($days);

        /** @var EventRepository|ObjectProphecy $eventRepository */
        $eventRepository = $this->prophesize(EventRepository::class);
        $eventRepository
            ->findByIdentifier(1)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->subject->injectEventRepository($eventRepository->reveal());

        $this->assertFalse(
            $this->subject->getNextDayForEvent(1)
        );
    }

    /**
     * @test
     */
    public function getNextDayForEventWithEventWithFutureDayReturnsDay()
    {
        $tomorrow = new \DateTime('tomorrow midnight');
        $tomorrowWithTime = new \DateTime('tomorrow');

        $day = new Day();
        $day->setDay($tomorrow);
        $day->setDayTime($tomorrowWithTime);
        $day->setSortDayTime($tomorrowWithTime);

        $days = new ObjectStorage();
        $days->attach($day);

        $event = new Event();
        $event->setDays($days);

        /** @var EventRepository|ObjectProphecy $eventRepository */
        $eventRepository = $this->prophesize(EventRepository::class);
        $eventRepository
            ->findByIdentifier(1)
            ->shouldBeCalled()
            ->willReturn($event);

        $this->subject->injectEventRepository($eventRepository->reveal());

        $this->assertEquals(
            $day->getDay(),
            $this->subject->getNextDayForEvent(1)
        );
    }

    /**
     * This test also tests re-sorting of days
     *
     * @test
     */
    public function getNextDayForEventWithEventWithFutureDaysReturnsNextDay()
    {
        $tomorrow = new \DateTime('tomorrow midnight');
        $tomorrowWithTime = new \DateTime('tomorrow');
        $nextWeek = new \DateTime('next week midnight');
        $nextWeekWithTime = new \DateTime('next week');
        $nextMonth = new \DateTime('next month midnight');
        $nextMonthWithTime = new \DateTime('next month');

        $day1 = new Day();
        $day1->setDay($nextMonth);
        $day1->setDayTime($nextMonthWithTime);
        $day1->setSortDayTime($nextMonthWithTime);
        $day2 = new Day();
        $day2->setDay($nextWeek);
        $day2->setDayTime($nextWeekWithTime);
        $day2->setSortDayTime($nextWeekWithTime);
        $day3 = new Day();
        $day3->setDay($tomorrow);
        $day3->setDayTime($tomorrowWithTime);
        $day3->setSortDayTime($tomorrowWithTime);

        $days = new ObjectStorage();
        $days->attach($day1);
        $days->attach($day2);
        $days->attach($day3);

        $event = new Event();
        $event->setEventType('recurring');
        $event->setDays($days);

        /** @var EventRepository|ObjectProphecy $eventRepository */
        $eventRepository = $this->prophesize(EventRepository::class);
        $eventRepository
            ->findByIdentifier(1)
            ->shouldBeCalled()
            ->willReturn($event);

        $this->subject->injectEventRepository($eventRepository->reveal());

        $this->assertEquals(
            $day3->getDay(),
            $this->subject->getNextDayForEvent(1)
        );
    }

    /**
     * @test
     */
    public function getLastDayForEventWithEventWithFutureDayReturnsDay()
    {
        $tomorrow = new \DateTime('tomorrow midnight');
        $tomorrowWithTime = new \DateTime('tomorrow');

        $day = new Day();
        $day->setDay($tomorrow);
        $day->setDayTime($tomorrowWithTime);
        $day->setSortDayTime($tomorrowWithTime);

        $days = new ObjectStorage();
        $days->attach($day);

        $event = new Event();
        $event->setDays($days);

        /** @var EventRepository|ObjectProphecy $eventRepository */
        $eventRepository = $this->prophesize(EventRepository::class);
        $eventRepository
            ->findByIdentifier(1)
            ->shouldBeCalled()
            ->willReturn($event);

        $this->subject->injectEventRepository($eventRepository->reveal());

        $this->assertEquals(
            $day->getDay(),
            $this->subject->getLastDayForEvent(1)
        );
    }

    /**
     * This test also tests re-sorting of days
     *
     * @test
     */
    public function getLastDayForEventWithEventWithFutureDaysReturnsLastDay()
    {
        $tomorrow = new \DateTime('tomorrow midnight');
        $tomorrowWithTime = new \DateTime('tomorrow');
        $nextWeek = new \DateTime('next week midnight');
        $nextWeekWithTime = new \DateTime('next week');
        $nextMonth = new \DateTime('next month midnight');
        $nextMonthWithTime = new \DateTime('next month');

        $day1 = new Day();
        $day1->setDay($nextMonth);
        $day1->setDayTime($nextMonthWithTime);
        $day1->setSortDayTime($nextMonthWithTime);
        $day2 = new Day();
        $day2->setDay($nextWeek);
        $day2->setDayTime($nextWeekWithTime);
        $day2->setSortDayTime($nextWeekWithTime);
        $day3 = new Day();
        $day3->setDay($tomorrow);
        $day3->setDayTime($tomorrowWithTime);
        $day3->setSortDayTime($tomorrowWithTime);

        $days = new ObjectStorage();
        $days->attach($day1);
        $days->attach($day2);
        $days->attach($day3);

        $event = new Event();
        $event->setEventType('recurring');
        $event->setDays($days);

        /** @var EventRepository|ObjectProphecy $eventRepository */
        $eventRepository = $this->prophesize(EventRepository::class);
        $eventRepository
            ->findByIdentifier(1)
            ->shouldBeCalled()
            ->willReturn($event);

        $this->subject->injectEventRepository($eventRepository->reveal());

        $this->assertEquals(
            $day1->getDay(),
            $this->subject->getLastDayForEvent(1)
        );
    }
}
