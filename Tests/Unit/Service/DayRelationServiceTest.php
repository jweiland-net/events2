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
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Service\DayGenerator;
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Service\EventService;
use JWeiland\Events2\Utility\DateTimeUtility;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Test case for class \JWeiland\Events2\Service\DayRelationService
 */
class DayRelationServiceTest extends UnitTestCase
{
    /**
     * @var DayRelationService
     */
    protected $subject;

    /**
     * @var ExtConf|ObjectProphecy
     */
    protected $extConfProphecy;

    /**
     * @var EventRepository|ObjectProphecy
     */
    protected $eventRepositoryProphecy;

    /**
     * @var PersistenceManager|ObjectProphecy
     */
    protected $persistenceManagerProphecy;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->extConfProphecy = $this->prophesize(ExtConf::class);
        $this->extConfProphecy->getRecurringPast()->willReturn(3);
        $this->extConfProphecy->getRecurringFuture()->willReturn(6);

        // needed for getItemsFromTca in DayGenerator
        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['xth']['config']['items'] = [
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.first', 'first'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.second', 'second'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.third', 'third'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.fourth', 'fourth'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.fifth', 'fifth'],
        ];
        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['weekday']['config']['items'] = [
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.monday', 'monday'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.tuesday', 'tuesday'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.wednesday', 'wednesday'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.thursday', 'thursday'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.friday', 'friday'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.saturday', 'saturday'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.sunday', 'sunday'],
        ];

        $this->eventRepositoryProphecy = $this->prophesize(EventRepository::class);
        $this->persistenceManagerProphecy = $this->prophesize(PersistenceManager::class);

        $dayGenerator = new DayGenerator();
        $dayGenerator->injectExtConf($this->extConfProphecy->reveal());
        $dayGenerator->injectDateTimeUtility(new DateTimeUtility());

        $eventService = new EventService();
        $eventService->injectDateTimeUtility(new DateTimeUtility());

        $this->subject = new DayRelationService();
        $this->subject->injectExtConf($this->extConfProphecy->reveal());
        $this->subject->injectDayGenerator($dayGenerator);
        $this->subject->injectEventRepository($this->eventRepositoryProphecy->reveal());
        $this->subject->injectPersistenceManager($this->persistenceManagerProphecy->reveal());
        $this->subject->injectEventService($eventService);
        $this->subject->injectDateTimeUtility(new DateTimeUtility());
    }

    /**
     * tear down.
     */
    public function tearDown()
    {
        unset($this->extConfProphecy);
        unset($this->eventRepositoryProphecy);
        unset($this->persistenceManagerProphecy);
        unset($this->subject);
    }

    /**
     * @test
     */
    public function createDayRelationsWithEmptyEventWillNeverCallAnyQuery()
    {
        $this->persistenceManagerProphecy->update(Argument::cetera())->shouldNotBeCalled();
        $this->persistenceManagerProphecy->persistAll(Argument::cetera())->shouldNotBeCalled();

        $this->eventRepositoryProphecy->findHiddenEntryByUid(123)->shouldBeCalled()->willReturn(null);

        $this->subject->createDayRelations(123);
    }

    /**
     * An event with none configured start/end dates will result in zero days
     * So all related days have to be deleted
     * But addDay will not be called
     *
     * @test
     */
    public function createDayRelationsWithNonConfiguredEventDoesNotCallAddDay()
    {
        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setPid(321);
        $event->addDay(new Day());

        $this->eventRepositoryProphecy->findHiddenEntryByUid(123)->willReturn($event);
        $this->subject->createDayRelations(123);

        $event->setDays(new ObjectStorage());
        $this->persistenceManagerProphecy->update($event)->shouldBeCalled();
        $this->persistenceManagerProphecy->persistAll()->shouldBeCalled();
    }

    /**
     * Test a simple recurring event with no time/exception and whatever records
     * In that case day, day_time and sort_day_time will all be equal
     *
     * @test
     */
    public function createDayRelationsWithRecurringEvent()
    {
        $yesterday = new \DateTime();
        $yesterday->modify('yesterday midnight');
        $today = new \DateTime();
        $today->modify('midnight');
        $tomorrow = new \DateTime();
        $tomorrow->modify('tomorrow midnight');

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setPid(321);
        $event->setEventType('recurring');
        $event->setEventBegin($yesterday);
        $event->setRecurringEnd($tomorrow);
        $event->setXth(31);
        $event->setWeekday(127);

        $this->eventRepositoryProphecy->findHiddenEntryByUid(123)->willReturn($event);
        $this->subject->createDayRelations(123);

        $this->persistenceManagerProphecy->update($event)->shouldBeCalled();
        $this->persistenceManagerProphecy->persistAll()->shouldBeCalled();
        $this->assertCount(3, $event->getDays());

        /** @var Day $day */
        $days = ['yesterday', 'today', 'tomorrow'];
        foreach ($event->getDays()->toArray() as $key => $day) {
            $this->assertEquals(${$days[$key]}, $day->getDay());
            $this->assertEquals(${$days[$key]}, $day->getDayTime());
            $this->assertEquals(${$days[$key]}, $day->getSortDayTime());
            $this->assertEquals(${$days[$key]}, $day->getSameDayTime());
        }
    }

    /**
     * Test a recurring event with time record which is equal for all days
     * In that case day differs from day_time and sort_day_time
     * day_time and sort_day_time are equal
     *
     * @test
     */
    public function createDayRelationsWithRecurringEventAndTime()
    {
        $yesterday = new \DateTime();
        $yesterday->modify('yesterday midnight');
        $yesterdayLaunch = new \DateTime();
        $yesterdayLaunch->modify('yesterday 12:30');
        $today = new \DateTime();
        $today->modify('midnight');
        $todayLaunch = new \DateTime();
        $todayLaunch->modify('12:30');
        $tomorrow = new \DateTime();
        $tomorrow->modify('tomorrow midnight');
        $tomorrowLaunch = new \DateTime();
        $tomorrowLaunch->modify('tomorrow 12:30');

        $time = new Time();
        $time->setTimeBegin('12:30');

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setPid(321);
        $event->setEventType('recurring');
        $event->setEventBegin($yesterday);
        $event->setRecurringEnd($tomorrow);
        $event->setEventTime($time);
        $event->setXth(31);
        $event->setWeekday(127);

        $this->eventRepositoryProphecy->findHiddenEntryByUid(123)->willReturn($event);
        $this->subject->createDayRelations(123);

        $this->persistenceManagerProphecy->update($event)->shouldBeCalled();
        $this->persistenceManagerProphecy->persistAll()->shouldBeCalled();
        $this->assertCount(3, $event->getDays());

        /** @var Day $day */
        $days = ['yesterday', 'today', 'tomorrow'];
        foreach ($event->getDays()->toArray() as $key => $day) {
            $this->assertEquals(${$days[$key]}, $day->getDay());
            $this->assertEquals(${$days[$key] . 'Launch'}, $day->getDayTime());
            $this->assertEquals(${$days[$key] . 'Launch'}, $day->getSortDayTime());
            $this->assertEquals(${$days[$key] . 'Launch'}, $day->getSameDayTime());
        }
    }

    /**
     * Test a recurring event with multiple time records for same day
     * In that case day is current day at midnight
     * day_time is current day morning and within a second record current day evening
     * sort_day_time is current day morning and within a second record current day evening
     *
     * @test
     */
    public function createDayRelationsWithRecurringEventAndMultipleTimesAtSameDay()
    {
        $yesterday = new \DateTime();
        $yesterday->modify('yesterday midnight');
        $yesterdayMorning = new \DateTime();
        $yesterdayMorning->modify('yesterday 08:00');
        $yesterdayEvening = new \DateTime();
        $yesterdayEvening->modify('yesterday 20:15');
        $today = new \DateTime();
        $today->modify('midnight');
        $todayMorning = new \DateTime();
        $todayMorning->modify('08:00');
        $todayEvening = new \DateTime();
        $todayEvening->modify('20:15');
        $tomorrow = new \DateTime();
        $tomorrow->modify('tomorrow midnight');
        $tomorrowMorning = new \DateTime();
        $tomorrowMorning->modify('tomorrow 08:00');
        $tomorrowEvening = new \DateTime();
        $tomorrowEvening->modify('tomorrow 20:15');

        $timeBegin = new Time();
        $timeBegin->setTimeBegin('08:00');
        $timeEvening = new Time();
        $timeEvening->setTimeBegin('20:15');

        $multipleTimes = new ObjectStorage();
        $multipleTimes->attach($timeEvening);

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setPid(321);
        $event->setEventType('recurring');
        $event->setEventBegin($yesterday);
        $event->setRecurringEnd($tomorrow);
        $event->setEventTime($timeBegin);
        $event->setSameDay(true);
        $event->setMultipleTimes($multipleTimes);
        $event->setXth(31);
        $event->setWeekday(127);

        $this->eventRepositoryProphecy->findHiddenEntryByUid(123)->willReturn($event);
        $this->subject->createDayRelations(123);

        $this->persistenceManagerProphecy->update($event)->shouldBeCalled();
        $this->persistenceManagerProphecy->persistAll()->shouldBeCalled();
        $this->assertCount(6, $event->getDays());

        /** @var Day $day */
        $days = ['yesterday', 'yesterday', 'today', 'today', 'tomorrow', 'tomorrow'];
        foreach ($event->getDays()->toArray() as $key => $day) {
            $methodName = $key % 2 ? 'Evening' : 'Morning';
            $this->assertEquals(${$days[$key]}, $day->getDay());
            $this->assertEquals(${$days[$key] . $methodName}, $day->getDayTime());
            $this->assertEquals(${$days[$key] . $methodName}, $day->getSortDayTime());
            $this->assertEquals(${$days[$key] . 'Morning'}, $day->getSameDayTime());
        }
    }

    /**
     * Test is the same test as above.
     * But getRecurringPast will return 0 month.
     * So only future events are allowed
     *
     * @test
     */
    public function createDayRelationsWithRecurringEventAndMultipleTimesAtSameDayFuture()
    {
        $this->extConfProphecy->getRecurringPast()->willReturn(0);

        $yesterday = new \DateTime();
        $yesterday->modify('yesterday midnight');
        $yesterdayMorning = new \DateTime();
        $yesterdayMorning->modify('yesterday 08:00');
        $yesterdayEvening = new \DateTime();
        $yesterdayEvening->modify('yesterday 20:15');
        $today = new \DateTime();
        $today->modify('midnight');
        $todayMorning = new \DateTime();
        $todayMorning->modify('08:00');
        $todayEvening = new \DateTime();
        $todayEvening->modify('20:15');
        $tomorrow = new \DateTime();
        $tomorrow->modify('tomorrow midnight');
        $tomorrowMorning = new \DateTime();
        $tomorrowMorning->modify('tomorrow 08:00');
        $tomorrowEvening = new \DateTime();
        $tomorrowEvening->modify('tomorrow 20:15');

        $timeBegin = new Time();
        $timeBegin->setTimeBegin('08:00');
        $timeEvening = new Time();
        $timeEvening->setTimeBegin('20:15');

        $multipleTimes = new ObjectStorage();
        $multipleTimes->attach($timeEvening);

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setPid(321);
        $event->setEventType('recurring');
        $event->setEventBegin($yesterday);
        $event->setRecurringEnd($tomorrow);
        $event->setEventTime($timeBegin);
        $event->setSameDay(true);
        $event->setMultipleTimes($multipleTimes);
        $event->setXth(31);
        $event->setWeekday(127);

        $this->eventRepositoryProphecy->findHiddenEntryByUid(123)->willReturn($event);
        $this->subject->createDayRelations(123);

        $this->persistenceManagerProphecy->update($event)->shouldBeCalled();
        $this->persistenceManagerProphecy->persistAll()->shouldBeCalled();
        $this->assertCount(4, $event->getDays());

        /** @var Day $day */
        $days = ['today', 'today', 'tomorrow', 'tomorrow'];
        foreach ($event->getDays()->toArray() as $key => $day) {
            $methodName = $key % 2 ? 'Evening' : 'Morning';
            $this->assertEquals(${$days[$key]}, $day->getDay());
            $this->assertEquals(${$days[$key] . $methodName}, $day->getDayTime());
            $this->assertEquals(${$days[$key] . $methodName}, $day->getSortDayTime());
            $this->assertEquals(${$days[$key] . 'Morning'}, $day->getSameDayTime());
        }
    }

    /**
     * Test a recurring event with different times for weekday
     * In that case day is current day at midnight
     * day_time and sort_day_time are equal
     *
     * @test
     */
    public function createDayRelationsWithRecurringEventAndDifferentTimes()
    {
        $tuesday = new \DateTime();
        $tuesday->modify('last tuesday midnight');
        $tuesdayMorning = clone $tuesday;
        $tuesdayMorning->modify('08:00');
        $wednesday = clone $tuesday;
        $wednesday->modify('+1 day');
        $wednesdayEvening = clone $wednesday;
        $wednesdayEvening->modify('20:15');
        $thursday = clone $wednesday;
        $thursday->modify('+1 day');
        $thursdayMorning = clone $thursday;
        $thursdayMorning->modify('08:00');

        $timeBegin = new Time();
        $timeBegin->setTimeBegin('08:00');
        $timeEvening = new Time();
        $timeEvening->setTimeBegin('20:15');
        $timeEvening->setWeekday('wednesday');

        $multipleTimes = new ObjectStorage();
        $multipleTimes->attach($timeEvening);

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setPid(321);
        $event->setEventType('recurring');
        $event->setEventBegin($tuesday);
        $event->setRecurringEnd($thursday);
        $event->setEventTime($timeBegin);
        $event->setSameDay(true);
        $event->setDifferentTimes($multipleTimes);
        $event->setXth(31);
        $event->setWeekday(127);

        $this->eventRepositoryProphecy->findHiddenEntryByUid(123)->willReturn($event);
        $this->subject->createDayRelations(123);

        $this->persistenceManagerProphecy->update($event)->shouldBeCalled();
        $this->persistenceManagerProphecy->persistAll()->shouldBeCalled();
        $this->assertCount(3, $event->getDays());

        /** @var Day $day */
        $days = ['tuesday', 'wednesday', 'thursday'];
        foreach ($event->getDays()->toArray() as $key => $day) {
            $methodName = $key === 1 ? 'Evening' : 'Morning';
            $this->assertEquals(${$days[$key]}, $day->getDay());
            $this->assertEquals(${$days[$key] . $methodName}, $day->getDayTime());
            $this->assertEquals(${$days[$key] . $methodName}, $day->getSortDayTime());
            $this->assertEquals(${$days[$key] . $methodName}, $day->getSameDayTime());
        }
    }

    /**
     * Test a recurring event with exception times for one special day
     * In that case day is current day at midnight
     * day_time and sort_day_time are equal
     *
     * @test
     */
    public function createDayRelationsWithRecurringEventAndExceptionTimes()
    {
        $tuesday = new \DateTime();
        $tuesday->modify('last tuesday midnight');
        $wednesday = clone $tuesday;
        $wednesday->modify('+1 day');
        $wednesdayEvening = clone $wednesday;
        $wednesdayEvening->modify('20:15');
        $thursday = clone $wednesday;
        $thursday->modify('+1 day');
        $friday = clone $thursday;
        $friday->modify('+1 day');
        $fridayLaunch = clone $friday;
        $fridayLaunch->modify('12:30');

        $timeLaunch = new Time();
        $timeLaunch->setTimeBegin('12:30');
        $timeEvening = new Time();
        $timeEvening->setTimeBegin('20:15');
        $timeEvening->setWeekday('wednesday');

        $exception1 = new Exception();
        $exception1->setExceptionType('Time');
        $exception1->setExceptionDate($wednesday);
        $exception1->setExceptionTime($timeEvening);
        $exception2 = new Exception();
        $exception2->setExceptionType('Add');
        $exception2->setExceptionDate($friday);
        $exception2->setExceptionTime($timeLaunch);

        $exceptions = new ObjectStorage();
        $exceptions->attach($exception1);
        $exceptions->attach($exception2);

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setPid(321);
        $event->setEventType('recurring');
        $event->setEventBegin($tuesday);
        $event->setRecurringEnd($friday);
        $event->setSameDay(true);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setExceptions($exceptions);

        $this->eventRepositoryProphecy->findHiddenEntryByUid(123)->willReturn($event);
        $this->subject->createDayRelations(123);

        $this->persistenceManagerProphecy->update($event)->shouldBeCalled();
        $this->persistenceManagerProphecy->persistAll()->shouldBeCalled();
        $this->assertCount(4, $event->getDays());

        /** @var Day $day */
        $days = ['tuesday', 'wednesday', 'thursday', 'friday'];
        foreach ($event->getDays()->toArray() as $key => $day) {
            switch ($key) {
                case 1:
                    $methodName = 'Evening';
                    break;
                case 3:
                    $methodName = 'Launch';
                    break;
                default:
                    $methodName = '';
            }
            $this->assertEquals(${$days[$key]}, $day->getDay());
            $this->assertEquals(${$days[$key] . $methodName}, $day->getDayTime());
            $this->assertEquals(${$days[$key] . $methodName}, $day->getSortDayTime());
            $this->assertEquals(${$days[$key] . $methodName}, $day->getSameDayTime());
        }
    }

    /**
     * Test a recurring event with multiple exception times for one special day
     * In that case day is current day at midnight
     * day_time is current day + specified time
     * sort_day_time is current day + specified time
     *
     * @test
     */
    public function createDayRelationsWithRecurringEventAndMultipleExceptionTimes()
    {
        $tuesday = new \DateTime();
        $tuesday->modify('last tuesday midnight');
        $wednesday = clone $tuesday;
        $wednesday->modify('+1 day');
        $wednesdayMorning = clone $wednesday;
        $wednesdayMorning->modify('08:00');
        $wednesdayEvening = clone $wednesday;
        $wednesdayEvening->modify('20:15');
        $thursday = clone $wednesday;
        $thursday->modify('+1 day');
        $friday = clone $thursday;
        $friday->modify('+1 day');
        $fridayLaunch = clone $friday;
        $fridayLaunch->modify('12:30');

        $timeMorning = new Time();
        $timeMorning->setTimeBegin('08:00');
        $timeLaunch = new Time();
        $timeLaunch->setTimeBegin('12:30');
        $timeEvening = new Time();
        $timeEvening->setTimeBegin('20:15');

        $exception1 = new Exception();
        $exception1->setExceptionType('Add');
        $exception1->setExceptionDate($wednesday);
        $exception1->setExceptionTime($timeMorning);
        $exception2 = new Exception();
        $exception2->setExceptionType('Time');
        $exception2->setExceptionDate($wednesday);
        $exception2->setExceptionTime($timeEvening);
        $exception3 = new Exception();
        $exception3->setExceptionType('Add');
        $exception3->setExceptionDate($friday);
        $exception3->setExceptionTime($timeLaunch);

        $exceptions = new ObjectStorage();
        $exceptions->attach($exception1);
        $exceptions->attach($exception2);
        $exceptions->attach($exception3);

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setPid(321);
        $event->setEventType('recurring');
        $event->setEventBegin($tuesday);
        $event->setRecurringEnd($friday);
        $event->setSameDay(true);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setExceptions($exceptions);

        $this->eventRepositoryProphecy->findHiddenEntryByUid(123)->willReturn($event);
        $this->subject->createDayRelations(123);

        $this->persistenceManagerProphecy->update($event)->shouldBeCalled();
        $this->persistenceManagerProphecy->persistAll()->shouldBeCalled();
        $this->assertCount(5, $event->getDays());

        /** @var Day $day */
        $days = ['tuesday', 'wednesday', 'wednesday', 'thursday', 'friday'];
        $sameDayMethods = ['tuesday', 'wednesdayMorning', 'wednesdayMorning', 'thursday', 'fridayLaunch'];
        foreach ($event->getDays()->toArray() as $key => $day) {
            switch ($key) {
                case 1:
                    $methodName = 'Morning';
                    break;
                case 2:
                    $methodName = 'Evening';
                    break;
                case 4:
                    $methodName = 'Launch';
                    break;
                default:
                    $methodName = '';
            }
            $this->assertEquals(${$days[$key]}, $day->getDay());
            $this->assertEquals(${$days[$key] . $methodName}, $day->getDayTime());
            $this->assertEquals(${$days[$key] . $methodName}, $day->getSortDayTime());
            $this->assertEquals(${$sameDayMethods[$key]}, $day->getSameDayTime());
        }
    }

    /**
     * @test
     */
    public function createDayRelationsWithSingleEvent()
    {
        $nextWeek = new \DateTime();
        $nextWeek->modify('+1 week midnight');

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setPid(321);
        $event->setEventType('single');
        $event->setEventBegin($nextWeek);

        $this->eventRepositoryProphecy->findHiddenEntryByUid(123)->willReturn($event);
        $this->subject->createDayRelations(123);

        $this->persistenceManagerProphecy->update($event)->shouldBeCalled();
        $this->persistenceManagerProphecy->persistAll()->shouldBeCalled();
        $this->assertCount(1, $event->getDays());

        /** @var Day $day */
        $days = ['nextWeek'];
        foreach ($event->getDays()->toArray() as $key => $day) {
            $this->assertEquals(${$days[$key]}, $day->getDay());
            $this->assertEquals(${$days[$key]}, $day->getDayTime());
            $this->assertEquals(${$days[$key]}, $day->getSortDayTime());
            $this->assertEquals(${$days[$key]}, $day->getSameDayTime());
        }
    }

    /**
     * @test
     */
    public function createDayRelationsWithSingleEventAndTime()
    {
        $nextWeek = new \DateTime();
        $nextWeek->modify('+1 week midnight');
        $nextWeekMidnight = clone $nextWeek;
        $nextWeekMidnight->modify('23:59');

        $time = new Time();
        $time->setTimeBegin('23:59');

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setPid(321);
        $event->setEventType('single');
        $event->setEventBegin($nextWeek);
        $event->setEventTime($time);

        $this->eventRepositoryProphecy->findHiddenEntryByUid(123)->willReturn($event);
        $this->subject->createDayRelations(123);

        $this->persistenceManagerProphecy->update($event)->shouldBeCalled();
        $this->persistenceManagerProphecy->persistAll()->shouldBeCalled();
        $this->assertCount(1, $event->getDays());

        /** @var Day $day */
        $days = ['nextWeek'];
        foreach ($event->getDays()->toArray() as $key => $day) {
            $this->assertEquals(${$days[$key]}, $day->getDay());
            $this->assertEquals(${$days[$key] . 'Midnight'}, $day->getDayTime());
            $this->assertEquals(${$days[$key] . 'Midnight'}, $day->getSortDayTime());
            $this->assertEquals(${$days[$key] . 'Midnight'}, $day->getSameDayTime());
        }
    }

    /**
     * @test
     */
    public function createDayRelationsWithDurationEvent()
    {
        $today = new \DateTime();
        $today->modify('midnight');
        $tomorrow = clone $today;
        $tomorrow->modify('+1 day');
        $in2days = clone $today;
        $in2days->modify('+2 days');

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setPid(321);
        $event->setEventType('duration');
        $event->setEventBegin($today);
        $event->setEventEnd($in2days);

        $this->eventRepositoryProphecy->findHiddenEntryByUid(123)->willReturn($event);
        $this->subject->createDayRelations(123);

        $this->persistenceManagerProphecy->update($event)->shouldBeCalled();
        $this->persistenceManagerProphecy->persistAll()->shouldBeCalled();
        $this->assertCount(3, $event->getDays());

        /** @var Day $day */
        $days = ['today', 'tomorrow', 'in2days'];
        foreach ($event->getDays()->toArray() as $key => $day) {
            $this->assertEquals(${$days[$key]}, $day->getDay());
            $this->assertEquals(${$days[$key]}, $day->getDayTime());
            $this->assertEquals($today, $day->getSortDayTime());
            $this->assertEquals($today, $day->getSameDayTime());
        }
    }

    /**
     * @test
     */
    public function createDayRelationsWithDurationEventWithTime()
    {
        $today = new \DateTime();
        $today->modify('midnight');
        $todayMorning = new \DateTime();
        $todayMorning->modify('08:12');
        $tomorrow = clone $today;
        $tomorrow->modify('+1 day');
        $tomorrowMorning = clone $todayMorning;
        $tomorrowMorning->modify('+1 day');
        $in2days = clone $today;
        $in2days->modify('+2 days');
        $in2daysMorning = clone $tomorrowMorning;
        $in2daysMorning->modify('+1 day');

        $time = new Time();
        $time->setTimeBegin('08:12');

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setPid(321);
        $event->setEventType('duration');
        $event->setEventBegin($today);
        $event->setEventEnd($in2days);
        $event->setEventTime($time);

        $this->eventRepositoryProphecy->findHiddenEntryByUid(123)->willReturn($event);
        $this->subject->createDayRelations(123);

        $this->persistenceManagerProphecy->update($event)->shouldBeCalled();
        $this->persistenceManagerProphecy->persistAll()->shouldBeCalled();
        $this->assertCount(3, $event->getDays());

        /** @var Day $day */
        $days = ['today', 'tomorrow', 'in2days'];
        foreach ($event->getDays()->toArray() as $key => $day) {
            $this->assertEquals(${$days[$key]}, $day->getDay());
            $this->assertEquals(${$days[$key] . 'Morning'}, $day->getDayTime());
            $this->assertEquals($todayMorning, $day->getSortDayTime());
            $this->assertEquals($todayMorning, $day->getSameDayTime());
        }
    }
}
