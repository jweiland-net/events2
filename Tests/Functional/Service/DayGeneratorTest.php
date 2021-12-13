<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Service;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Service\DayGenerator;
use JWeiland\Events2\Utility\DateTimeUtility;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Test case for class \JWeiland\Events2\Service\DayGenerator.
 */
class DayGeneratorTest extends FunctionalTestCase
{
    protected DayGenerator $subject;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2'
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $extConf = new ExtConf(new ExtensionConfiguration());
        $extConf->setRecurringFuture(12);

        $this->subject = new DayGenerator(
            GeneralUtility::makeInstance(EventDispatcher::class),
            $extConf,
            new DateTimeUtility()
        );

        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['xth']['config']['items'] = [
            ['first', 'first'],
            ['second', 'second'],
            ['third', 'third'],
            ['fourth', 'fourth'],
            ['fifth', 'fifth'],
        ];

        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['weekday']['config']['items'] = [
            ['monday', 'monday'],
            ['tuesday', 'tuesday'],
            ['wednesday', 'wednesday'],
            ['thursday', 'thursday'],
            ['friday', 'friday'],
            ['saturday', 'saturday'],
            ['sunday', 'sunday'],
        ];
    }

    protected function tearDown(): void
    {
        unset($this->subject);

        parent::tearDown();
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithEmptyEventRecordResultsInFalse(): void
    {
        $event = new Event();
        $event->_setProperty('uid', 123);

        self::assertFalse($this->subject->initialize($event));
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithInvalidEventRecordResultsInFalse(): void
    {
        $event = new Event();
        $event->_setProperty('uid', 123);

        self::assertFalse($this->subject->initialize($event));
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithRecurringEventCallsAddRecurringEvents(): void
    {
        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('recurring');
        $event->setEventBegin(new \DateTimeImmutable('now'));
        $event->setEventEnd(null);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(0);
        $event->setEachMonths(0);

        /** @var DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this
            ->getMockBuilder(DayGenerator::class)
            ->setConstructorArgs([
                GeneralUtility::makeInstance(EventDispatcher::class),
                new ExtConf(new ExtensionConfiguration()),
                new DateTimeUtility()
            ])
            ->setMethods(['addRecurringEvents', 'addException', 'getEventBegin'])
            ->getMock();

        $dayGenerator->expects(self::once())->method('addRecurringEvents');
        $dayGenerator->expects(self::never())->method('addException');
        $dayGenerator->expects(self::never())->method('getEventBegin');

        self::assertTrue($dayGenerator->initialize($event));
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithRecurringWeeksCallsAddRecurrings(): void
    {
        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('recurring');
        $event->setEventBegin(new \DateTimeImmutable('now'));
        $event->setEventEnd(null);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(1);
        $event->setEachMonths(0);

        /** @var DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this
            ->getMockBuilder(DayGenerator::class)
            ->setConstructorArgs([
                GeneralUtility::makeInstance(EventDispatcher::class),
                new ExtConf(new ExtensionConfiguration()),
                new DateTimeUtility()
            ])
            ->setMethods(['addRecurrings', 'addException', 'getEventBegin'])
            ->getMock();

        $dayGenerator->expects(self::once())->method('addRecurrings');
        $dayGenerator->expects(self::never())->method('addException');
        $dayGenerator->expects(self::never())->method('getEventBegin');

        self::assertTrue($dayGenerator->initialize($event));
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithRecurringWeeksResetsDayToMidnight(): void
    {
        $eventBegin = new \DateTimeImmutable('yesterday 15:38:24');
        $recurringEnd = new \DateTimeImmutable('+6 weeks 15:38:24');

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('recurring');
        $event->setEventBegin($eventBegin);
        $event->setRecurringEnd($recurringEnd);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(1);
        $event->setEachMonths(0);

        /** @var DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = new DayGenerator(
            GeneralUtility::makeInstance(EventDispatcher::class),
            new ExtConf(new ExtensionConfiguration()),
            new DateTimeUtility()
        );

        self::assertTrue($dayGenerator->initialize($event));

        $days = $dayGenerator->getDateTimeStorage();

        foreach ($days as $day) {
            self::assertSame(
                '00:00:00',
                $day->format('H:i:s')
            );
        }
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithRecurringOverEachWeekAddsThreeDaysToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin->modify('midnight');
        $nextWeek = clone $eventBegin;
        $nextWeek->modify('+7 days');
        $eventEnd = clone $eventBegin;
        $eventEnd->modify('+14 days');

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('recurring');
        $event->setEventBegin($eventBegin);
        $event->setEventEnd($eventEnd);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(1);
        $event->setEachMonths(0);

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$nextWeek->format('U')] = $nextWeek;
        $expectedDays[$eventEnd->format('U')] = $eventEnd;

        /** @var DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this
            ->getMockBuilder(DayGenerator::class)
            ->setConstructorArgs([
                GeneralUtility::makeInstance(EventDispatcher::class),
                new ExtConf(new ExtensionConfiguration()),
                new DateTimeUtility()
            ])
            ->setMethods(['addException', 'getDateToStopCalculatingTo'])
            ->getMock();
        $dayGenerator->expects(self::never())->method('addException');
        $dayGenerator->expects(self::once())->method('getDateToStopCalculatingTo')->willReturn($eventEnd);

        self::assertTrue($dayGenerator->initialize($event));
        self::assertEquals(
            $expectedDays,
            $days = $dayGenerator->getDateTimeStorage()
        );
        // test for correct TimezoneType, else times are not DST save
        foreach ($days as $day) {
            self::assertIsArray($day->getTimezone()->getLocation());
        }
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithRecurringOverTwoWeeksAddsDaysToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin->modify('midnight');
        $eventEnd = clone $eventBegin;
        $eventEnd->modify('+14 days');

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('recurring');
        $event->setEventBegin($eventBegin);
        $event->setEventEnd($eventEnd);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(2);
        $event->setEachMonths(0);

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$eventEnd->format('U')] = $eventEnd;

        /** @var DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this
            ->getMockBuilder(DayGenerator::class)
            ->setConstructorArgs([
                GeneralUtility::makeInstance(EventDispatcher::class),
                new ExtConf(new ExtensionConfiguration()),
                new DateTimeUtility()
            ])
            ->setMethods(['addException', 'getDateToStopCalculatingTo'])
            ->getMock();

        $dayGenerator->expects(self::never())->method('addException');
        $dayGenerator->expects(self::once())->method('getDateToStopCalculatingTo')->willReturn($eventEnd);

        self::assertTrue($dayGenerator->initialize($event));
        self::assertEquals(
            $expectedDays,
            $dayGenerator->getDateTimeStorage()
        );
    }

    /**
     * The earliest switch to wintertime for the next 30 years is 25. october
     *
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithRecurringWeeksWillKeepDaylightSavingTime(): void
    {
        $timestamp = mktime(0, 0, 0, 10, 20, 2017);
        // this test has to build days in past. To allow this we have to set recurring past to a high value
        // Maybe you have to update this value or the year above in future
        $extConf = new ExtConf(new ExtensionConfiguration());
        $extConf->setRecurringPast(60);

        // These dates will be created with timezone_type = 1, which does know the timezone (+02:00) only from the current date
        $eventBegin = new \DateTimeImmutable(date('c', $timestamp));
        $eventEnd = clone $eventBegin;
        $eventEnd->modify('+14 days');

        // adding a correct timezone (Europe/Berlin) will know the timezone from now on until forever
        $expectedBegin = clone $eventBegin;
        $expectedBegin->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $expectedEnd = clone $expectedBegin;
        $expectedEnd->modify('+14 days');

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('recurring');
        $event->setEventBegin($eventBegin);
        $event->setEventEnd($eventEnd);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(2);
        $event->setEachMonths(0);

        $expectedDays = [];
        $expectedDays[$expectedBegin->format('U')] = $expectedBegin;
        $expectedDays[$expectedEnd->format('U')] = $expectedEnd;

        /** @var DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this
            ->getMockBuilder(DayGenerator::class)
            ->setConstructorArgs([
                GeneralUtility::makeInstance(EventDispatcher::class),
                $extConf,
                new DateTimeUtility()
            ])
            ->setMethods(['addException', 'getDateToStopCalculatingTo'])
            ->getMock();

        $dayGenerator->expects(self::never())->method('addException');
        $dayGenerator->expects(self::once())->method('getDateToStopCalculatingTo')->willReturn($expectedEnd);

        self::assertTrue($dayGenerator->initialize($event));
        self::assertEquals(
            $expectedDays,
            $dayGenerator->getDateTimeStorage()
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithRecurringOverEachMonthAddsThreeDaysToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin->modify('midnight')->modify('first day of this month')->modify('+25 days');
        $nextMonth = clone $eventBegin;
        $nextMonth->modify('+1 months');
        $recurringEnd = clone $eventBegin;
        $recurringEnd->modify('+2 months');

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('recurring');
        $event->setEventBegin($eventBegin);
        $event->setRecurringEnd($recurringEnd);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(0);
        $event->setEachMonths(1);

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$nextMonth->format('U')] = $nextMonth;
        $expectedDays[$recurringEnd->format('U')] = $recurringEnd;

        $extConf = new ExtConf(new ExtensionConfiguration());
        $extConf->setRecurringPast(1);

        /** @var DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this
            ->getMockBuilder(DayGenerator::class)
            ->setConstructorArgs([
                GeneralUtility::makeInstance(EventDispatcher::class),
                $extConf,
                new DateTimeUtility()
            ])
            ->setMethods(['addException', 'getDateToStopCalculatingTo'])
            ->getMock();
        $dayGenerator->expects(self::never())->method('addException');
        $dayGenerator->expects(self::once())->method('getDateToStopCalculatingTo')->willReturn($recurringEnd);

        self::assertTrue($dayGenerator->initialize($event));
        self::assertEquals(
            $expectedDays,
            $days = $dayGenerator->getDateTimeStorage()
        );
        // test for correct TimezoneType, else times are not DST save
        foreach ($days as $day) {
            self::assertIsArray($day->getTimezone()->getLocation());
        }
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithRecurringOverTwoMonthsAddsDaysToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin->modify('midnight');
        $eventEnd = clone $eventBegin;
        $eventEnd->modify('+2 months');

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('recurring');
        $event->setEventBegin($eventBegin);
        $event->setEventEnd($eventEnd);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(0);
        $event->setEachMonths(2);

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$eventEnd->format('U')] = $eventEnd;

        /** @var DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this
            ->getMockBuilder(DayGenerator::class)
            ->setConstructorArgs([
                GeneralUtility::makeInstance(EventDispatcher::class),
                new ExtConf(new ExtensionConfiguration()),
                new DateTimeUtility()
            ])
            ->setMethods(['addException', 'getDateToStopCalculatingTo'])
            ->getMock();

        $dayGenerator->expects(self::never())->method('addException');
        $dayGenerator->expects(self::once())->method('getDateToStopCalculatingTo')->willReturn($eventEnd);

        self::assertTrue($dayGenerator->initialize($event));
        self::assertEquals(
            $expectedDays,
            $dayGenerator->getDateTimeStorage()
        );
    }

    /**
     * There is a special condition in DayGenerator::getDateToStartCalculatingFrom why we have to do this test
     *
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithRecurringOverTwoMonthsAndVeryEarlyEventDateAddsDayToStorage(): void
    {
        $extConf = new ExtConf(new ExtensionConfiguration());
        $extConf->setRecurringPast(3);

        $eventBegin = new \DateTimeImmutable();
        $eventBegin->modify('-4 months');
        $eventBegin->modify('midnight');
        $eventEnd = clone $eventBegin;
        $eventEnd->modify('+2 months');

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('recurring');
        $event->setEventBegin($eventBegin);
        $event->setRecurringEnd($eventEnd);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(0);
        $event->setEachMonths(2);

        $expectedDays = [];
        $expectedDays[$eventEnd->format('U')] = $eventEnd;

        $dayGenerator = new DayGenerator(
            GeneralUtility::makeInstance(EventDispatcher::class),
            $extConf,
            new DateTimeUtility()
        );

        self::assertTrue($dayGenerator->initialize($event));
        self::assertEquals(
            $expectedDays,
            $dayGenerator->getDateTimeStorage()
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithRecurringOverEachMonthAndTwoWeeksAddsDaysToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin->modify('midnight');
        $nextEvent = clone $eventBegin;
        $nextEvent->modify('+1 months');
        $nextEvent->modify('+2 weeks');
        $lastEvent = clone $nextEvent;
        $lastEvent->modify('+1 months');
        $lastEvent->modify('+2 weeks');
        $eventEnd = clone $eventBegin;
        $eventEnd->modify('+3 months');

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('recurring');
        $event->setEventBegin($eventBegin);
        $event->setEventEnd($eventEnd);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(2);
        $event->setEachMonths(1);

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$nextEvent->format('U')] = $nextEvent;
        $expectedDays[$lastEvent->format('U')] = $lastEvent;

        /** @var DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this
            ->getMockBuilder(DayGenerator::class)
            ->setConstructorArgs([
                GeneralUtility::makeInstance(EventDispatcher::class),
                new ExtConf(new ExtensionConfiguration()),
                new DateTimeUtility()
            ])
            ->setMethods(['addException', 'getDateToStopCalculatingTo'])
            ->getMock();

        $dayGenerator->expects(self::never())->method('addException');
        $dayGenerator->expects(self::once())->method('getDateToStopCalculatingTo')->willReturn($eventEnd);

        self::assertTrue($dayGenerator->initialize($event));
        self::assertEquals(
            $expectedDays,
            $dayGenerator->getDateTimeStorage()
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithRecurringAndEqualEventBeginAndEventEndResultsInOneDayInStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin->modify('midnight');
        $recurringEnd = clone $eventBegin;

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('recurring');
        $event->setEventBegin($eventBegin);
        $event->setRecurringEnd($recurringEnd);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(0);
        $event->setEachMonths(0);

        self::assertTrue($this->subject->initialize($event));
        self::assertEquals(
            [$eventBegin->format('U') => $eventBegin],
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithAnEmptySettingForRecurringsResultsInAddDaysForEachDay(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin->modify('midnight');
        $recurringEnd = clone $eventBegin;
        $recurringEnd->modify('+14 days');

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('recurring');
        $event->setEventBegin($eventBegin);
        $event->setRecurringEnd($recurringEnd);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(0);
        $event->setEachMonths(0);

        $expectedDays = [];
        $tempDate = clone $eventBegin;
        for ($i = 0; $i < 15; ++$i) {
            $expectedDays[$tempDate->format('U')] = clone $tempDate;
            $tempDate->modify('+1 day');
        }

        self::assertTrue($this->subject->initialize($event));
        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithGivenWeekdaysResultsInAddedDaysInStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin->modify('midnight');
        $eventBegin->modify('next saturday');
        $recurringEnd = clone $eventBegin;
        $recurringEnd->modify('+8 days');

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('recurring');
        $event->setEventBegin($eventBegin);
        $event->setRecurringEnd($recurringEnd);
        $event->setXth(31); // all
        $event->setWeekday(87); // mo, tu, we, fr, su
        $event->setEachWeeks(0);
        $event->setEachMonths(0);

        $tempDate = clone $eventBegin;
        $expectedDays = [];
        $tempDate->modify('+1 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add sunday
        $tempDate->modify('+1 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add monday
        $tempDate->modify('+1 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add tuesday
        $tempDate->modify('+1 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add wednesday
        $tempDate->modify('+2 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add friday
        $tempDate->modify('+2 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add sunday
        ksort($expectedDays);

        self::assertTrue($this->subject->initialize($event));
        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * This test also checks for leap years.
     *
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithGivenXthsResultsInAddedDaysInStorage(): void
    {
        // $eventBegin has to start with a month beginning with a thursday
        $eventBegin = new \DateTimeImmutable('now');
        $eventBegin->modify('first day of next month');
        while ((int)$eventBegin->format('N') !== 4) {
            $eventBegin->modify('next month');
        }
        $eventBegin->modify('midnight');
        $eventBegin->modify('+16 days'); // first day of month (1) + 16 = 17. of month. Must be saturday
        $recurringEnd = clone $eventBegin;
        $recurringEnd->modify('+22 days'); // 07th or 08th of next month. Regarding, if month has 30 or 31 days

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('recurring');
        $event->setEventBegin($eventBegin);
        $event->setRecurringEnd($recurringEnd);
        $event->setXth(21); // 1st, 3rd, 5th
        $event->setWeekday(18); // tuesday, friday
        $event->setEachWeeks(0);
        $event->setEachMonths(0);

        $tempDate = clone $eventBegin;
        $expectedDays = [];
        $tempDate->modify('+3 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add 3rd tuesday 20th of month
        $tempDate->modify('+10 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add 5th friday 30th of month
        $tempDate->modify('+4 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add 1st tuesday 3rd of next month
        $tempDate->modify('+3 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add 1st friday 6th of next month
        ksort($expectedDays);

        self::assertTrue($this->subject->initialize($event));
        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithEventEndAddsDaysToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin->modify('midnight');
        $recurringEnd = new \DateTimeImmutable();
        $recurringEnd->modify('midnight');
        $recurringEnd->modify('+4 days');

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('recurring');
        $event->setEventBegin($eventBegin);
        $event->setRecurringEnd($recurringEnd);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(0);
        $event->setEachMonths(0);

        $tempDate = clone $eventBegin;
        $expectedDays = [];
        for ($i = 0; $i < 5; ++$i) {
            $expectedDays[$tempDate->format('U')] = clone $tempDate;
            $tempDate->modify('+1 day');
        }

        self::assertTrue($this->subject->initialize($event));
        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithEventBeginAddsOneDayToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin->modify('midnight');

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('single');
        $event->setEventBegin($eventBegin);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(0);
        $event->setEachMonths(0);

        $expectedDays = [$eventBegin->format('U') => $eventBegin];

        self::assertTrue($this->subject->initialize($event));
        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithExceptionsInEventCallsAddExceptions(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin->modify('midnight');

        $exception = new Exception();
        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('duration');
        $event->setEventBegin($eventBegin);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(0);
        $event->setEachMonths(0);
        $event->setExceptions($exceptions);

        /** @var DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this
            ->getMockBuilder(DayGenerator::class)
            ->setConstructorArgs([
                GeneralUtility::makeInstance(EventDispatcher::class),
                new ExtConf(new ExtensionConfiguration()),
                new DateTimeUtility()
            ])
            ->setMethods(['addRecurringEvents', 'addDayToStorage', 'addExceptions'])
            ->getMock();
        $dayGenerator->expects(self::never())->method('addRecurringEvents');
        $dayGenerator->expects(self::once())->method('addDayToStorage')->with($eventBegin);
        $dayGenerator->expects(self::once())->method('addExceptions');
        self::assertTrue($dayGenerator->initialize($event));
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithAddExceptionAddsOneDayInStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin->modify('midnight');
        $recurringEnd = new \DateTimeImmutable();
        $recurringEnd->modify('midnight');
        $recurringEnd->modify('+4 days');
        $tomorrow = clone $eventBegin;
        $tomorrow->modify('tomorrow');

        $exceptionTime = new Time();
        $exceptionTime->setTimeBegin('18:00');

        $exception = new Exception();
        $exception->setExceptionType('Add');
        $exception->setExceptionDate($tomorrow);
        $exception->setExceptionTime($exceptionTime);
        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('recurring');
        $event->setEventBegin($eventBegin);
        $event->setRecurringEnd($recurringEnd);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(0);
        $event->setEachMonths(0);
        $event->setExceptions($exceptions);

        $tempDate = clone $eventBegin;
        $expectedDays = [];
        for ($i = 0; $i < 5; ++$i) {
            $expectedDays[$tempDate->format('U')] = clone $tempDate;
            $tempDate->modify('+1 day');
        }

        self::assertTrue($this->subject->initialize($event));
        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * @test
     */
    public function initializeWithAddExceptionOutOfAllowedRangeDoesNotAddDayInStorage(): void
    {
        $timestamp = mktime(0, 0, 0);

        // These dates will be created with timezone_type = 1, which does know the timezone (+02:00) only from the current date
        $eventBegin = new \DateTimeImmutable(date('c', $timestamp));
        $lastYear = clone $eventBegin;
        $lastYear->modify('-1 year');

        $exceptionTime = new Time();
        $exceptionTime->setTimeBegin('18:00');

        $exception = new Exception();
        $exception->setExceptionType('Add');
        $exception->setExceptionDate($lastYear);
        $exception->setExceptionTime($exceptionTime);
        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('single');
        $event->setEventBegin($eventBegin);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(0);
        $event->setEachMonths(0);
        $event->setExceptions($exceptions);

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = $eventBegin;

        self::assertTrue($this->subject->initialize($event));
        $dateTimeStorage = $this->subject->getDateTimeStorage();

        // assertEquals will only check for correct dates, but not for different timezoneTypes
        self::assertEquals(
            $expectedDays,
            $dateTimeStorage
        );

        foreach ($dateTimeStorage as $dateTime) {
            self::assertIsArray($dateTime->getTimezone()->getLocation());
        }
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithRemoveExceptionRemovesOneDayFromStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin->modify('midnight');
        $tomorrow = clone $eventBegin;
        $tomorrow->modify('tomorrow');

        $exception = new Exception();
        $exception->setExceptionType('Remove');
        $exception->setExceptionDate($eventBegin);
        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('duration');
        $event->setEventBegin($eventBegin);
        $event->setEventEnd($tomorrow);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(0);
        $event->setEachMonths(0);
        $event->setExceptions($exceptions);

        $expectedDays = [];
        $expectedDays[$tomorrow->format('U')] = $tomorrow;

        self::assertTrue($this->subject->initialize($event));
        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithTimeExceptionDoNotAddDayToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin->modify('midnight');
        $tomorrow = clone $eventBegin;
        $tomorrow->modify('tomorrow');

        $exception = new Exception();
        $exception->setExceptionType('Time');
        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('duration');
        $event->setEventBegin($eventBegin);
        $event->setEventEnd($tomorrow);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(0);
        $event->setEachMonths(0);
        $event->setExceptions($exceptions);

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$tomorrow->format('U')] = $tomorrow;

        self::assertTrue($this->subject->initialize($event));
        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function initializeWithInfoExceptionDoNotAddDayToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin->modify('midnight');
        $tomorrow = clone $eventBegin;
        $tomorrow->modify('tomorrow');

        $exception = new Exception();
        $exception->setExceptionType('Info');
        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('duration');
        $event->setEventBegin($eventBegin);
        $event->setEventEnd($tomorrow);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(0);
        $event->setEachMonths(0);
        $event->setExceptions($exceptions);

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$tomorrow->format('U')] = $tomorrow;

        self::assertTrue($this->subject->initialize($event));
        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     *
     * @expectedException \Exception
     */
    public function initializeWithInvalidExceptionThrowsException(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin->modify('midnight');
        $tomorrow = clone $eventBegin;
        $tomorrow->modify('tomorrow');

        $exception = new Exception();
        $exception->setExceptionType('Invalid Value');
        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('duration');
        $event->setEventBegin($eventBegin);
        $event->setEventEnd($tomorrow);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(0);
        $event->setEachMonths(0);
        $event->setExceptions($exceptions);

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$tomorrow->format('U')] = $tomorrow;

        self::assertTrue($this->subject->initialize($event));
        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * @test
     */
    public function getDayStorageResultsInSortedArray(): void
    {
        $this->subject->setDateTimeStorage([
            4 => 'TestValue',
            2 => 'TestValue',
            0 => 'TestValue',
            3 => 'TestValue',
            1 => 'TestValue',
        ]);
        $expectedArray = [
            0 => 'TestValue',
            1 => 'TestValue',
            2 => 'TestValue',
            3 => 'TestValue',
            4 => 'TestValue',
        ];
        self::assertSame(
            $expectedArray,
            $this->subject->getDateTimeStorage()
        );
    }
}
