<?php

namespace JWeiland\Events2\Tests\Unit\Service;

/*
 * This file is part of the TYPO3 CMS project.
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
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Service\DayGenerator;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Test case for class \JWeiland\Events2\Service\DayGenerator.
 *
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class DayGeneratorTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Service\DayGenerator
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $extConf = new ExtConf();
        $extConf->setRecurringFuture(12);

        $this->subject = new DayGenerator();
        $this->subject->injectExtConf($extConf);
        $this->subject->injectDateTimeUtility(new DateTimeUtility());

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

    /**
     * tear down.
     */
    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * dataProvider with invalid values for array arguments.
     *
     * @return array
     */
    public function dataProviderWithInvalidValuesForArrayArguments()
    {
        $invalidValues = [];
        $invalidValues['string'] = ['Hello', ['Hello']];
        $invalidValues['integer'] = [123, [123]];
        $invalidValues['boolean'] = [true, [true]];
        $invalidValues['object'] = [new \stdClass()];
        $invalidValues['null'] = [null];

        return $invalidValues;
    }

    /**
     * @test
     *
     * @param mixed $invalidArgument
     * @dataProvider dataProviderWithInvalidValuesForArrayArguments
     * @expectedException \PHPUnit_Framework_Error
     */
    public function initializeWithInvalidArguments($invalidArgument)
    {
        $this->subject->initialize($invalidArgument);
    }

    /**
     * @test
     */
    public function initializeWithEmptyEventRecordResultsInFalse()
    {
        $event = new Event();
        $event->_setProperty('uid', 123);
        $this->assertFalse($this->subject->initialize($event));
    }

    /**
     * @test
     */
    public function initializeWithInvalidEventRecordResultsInFalse()
    {
        $event = new Event();
        $event->_setProperty('uid', 123);
        $this->assertFalse($this->subject->initialize($event));
    }

    /**
     * @test
     */
    public function initializeWithRecurringEventCallsAddRecurringEvents()
    {
        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('recurring');
        $event->setEventBegin(new \DateTime('now'));
        $event->setEventEnd(null);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(0);

        /** @var DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this->getMock(DayGenerator::class, ['addRecurringEvents', 'addException', 'getEventBegin']);
        $dayGenerator->expects($this->once())->method('addRecurringEvents');
        $dayGenerator->expects($this->never())->method('addException');
        $dayGenerator->expects($this->never())->method('getEventBegin');
        $this->assertTrue($dayGenerator->initialize($event));
    }

    /**
     * @test
     */
    public function initializeWithRecurringWeeksCallsAddRecurringWeeks()
    {
        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('recurring');
        $event->setEventBegin(new \DateTime('now'));
        $event->setEventEnd(null);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(1);

        /** @var DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this->getMock(DayGenerator::class, ['addRecurringWeeks', 'addException', 'getEventBegin']);
        $dayGenerator->expects($this->once())->method('addRecurringWeeks');
        $dayGenerator->expects($this->never())->method('addException');
        $dayGenerator->expects($this->never())->method('getEventBegin');
        $this->assertTrue($dayGenerator->initialize($event));
    }

    /**
     * @test
     */
    public function initializeWithRecurringOverEachWeekAddsThreeDaysToStorage()
    {
        $eventBegin = new \DateTime();
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

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$nextWeek->format('U')] = $nextWeek;
        $expectedDays[$eventEnd->format('U')] = $eventEnd;

        /** @var DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this->getMock(DayGenerator::class, ['addException', 'getMaxDateForGeneratedDays']);
        $dayGenerator->injectDateTimeUtility(new DateTimeUtility());
        $dayGenerator->injectExtConf(new ExtConf());
        $dayGenerator->expects($this->never())->method('addException');
        $dayGenerator->expects($this->once())->method('getMaxDateForGeneratedDays')->willReturn($eventEnd);
        $this->assertTrue($dayGenerator->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $days = $dayGenerator->getDateTimeStorage()
        );
        // test for correct TimezoneType, else times are not DST save
        /** @var \DateTime $day */
        foreach ($days as $day) {
            $this->assertSame(3, $day->timezone_type);
        }
    }

    /**
     * @test
     */
    public function initializeWithRecurringOverTwoWeeksAddsDaysToStorage()
    {
        $eventBegin = new \DateTime();
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

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$eventEnd->format('U')] = $eventEnd;

        /** @var DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this->getMock(DayGenerator::class, ['addException', 'getMaxDateForGeneratedDays']);
        $dayGenerator->injectDateTimeUtility(new DateTimeUtility());
        $dayGenerator->injectExtConf(new ExtConf());
        $dayGenerator->expects($this->never())->method('addException');
        $dayGenerator->expects($this->once())->method('getMaxDateForGeneratedDays')->willReturn($eventEnd);
        $this->assertTrue($dayGenerator->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $dayGenerator->getDateTimeStorage()
        );
    }

    /**
     * The earliest switch to wintertime for the next 30 years is 25. october
     *
     * @test
     */
    public function initializeWithRecurringWeeksWillKeepDaylightSavingTime()
    {
        $timestamp = mktime(0, 0, 0, 10, 20, 2017);
        // this test has to build days in past. To allow this we have to set recurring past to a high value
        // Maybe you have to update this value or the year above in future
        $extConf = new ExtConf();
        $extConf->setRecurringPast(60);

        // These dates will be created with timezone_type = 1, which does know the timezone (+02:00) only from the current date
        $eventBegin = new \DateTime(date('c', $timestamp));
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

        $expectedDays = [];
        $expectedDays[$expectedBegin->format('U')] = $expectedBegin;
        $expectedDays[$expectedEnd->format('U')] = $expectedEnd;

        /** @var DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this->getMock(DayGenerator::class, ['addException', 'getMaxDateForGeneratedDays']);
        $dayGenerator->injectDateTimeUtility(new DateTimeUtility());
        $dayGenerator->injectExtConf($extConf);
        $dayGenerator->expects($this->never())->method('addException');
        $dayGenerator->expects($this->once())->method('getMaxDateForGeneratedDays')->willReturn($expectedEnd);
        $this->assertTrue($dayGenerator->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $dayGenerator->getDateTimeStorage()
        );
    }

    /**
     * @test
     */
    public function initializeWithRecurringAndEqualEventBeginAndEventEndResultsInOneDayInStorage()
    {
        $eventBegin = new \DateTime();
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

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            [$eventBegin->format('U') => $eventBegin],
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * @test
     */
    public function initializeWithAnEmptySettingForRecurringsResultsInAddDaysForEachDay()
    {
        $eventBegin = new \DateTime();
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

        $expectedDays = [];
        $tempDate = clone $eventBegin;
        for ($i = 0; $i < 15; ++$i) {
            $expectedDays[$tempDate->format('U')] = clone $tempDate;
            $tempDate->modify('+1 day');
        }

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * @test
     */
    public function initializeWithGivenWeekdaysResultsInAddedDaysInStorage()
    {
        $eventBegin = new \DateTime();
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

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * This test also checks for leap years.
     *
     * @test
     */
    public function initializeWithGivenXthsResultsInAddedDaysInStorage()
    {
        // this date has to be updated each 6 month.
        // Set it to a month which starts with a thursday
        $eventBegin = new \DateTime('17.02.2018');
        $eventBegin->modify('midnight');
        $recurringEnd = clone $eventBegin;
        $recurringEnd->modify('+22 days'); // 08.03.2017

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('recurring');
        $event->setEventBegin($eventBegin);
        $event->setRecurringEnd($recurringEnd);
        $event->setXth(21); // 1st, 3rd, 5th
        $event->setWeekday(18); // tu, fr
        $event->setEachWeeks(0);

        $tempDate = clone $eventBegin;
        $expectedDays = [];
        $tempDate->modify('+3 day'); // start with sa
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add 3rd tu 20.01
        $tempDate->modify('+10 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add 5th fr 30.01
        $tempDate->modify('+4 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add 1st tu 03.02
        //$eventBegin->modify('+3 day');
        //$expectedDays[$eventBegin->format('U')] = clone $eventBegin; // add 1st fr 06.03
        ksort($expectedDays);

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * @test
     */
    public function initializeWithEventEndAddsDaysToStorage()
    {
        $eventBegin = new \DateTime();
        $eventBegin->modify('midnight');
        $recurringEnd = new \DateTime();
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

        $tempDate = clone $eventBegin;
        $expectedDays = [];
        for ($i = 0; $i < 5; ++$i) {
            $expectedDays[$tempDate->format('U')] = clone $tempDate;
            $tempDate->modify('+1 day');
        }

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * @test
     */
    public function initializeWithEventBeginAddsOneDayToStorage()
    {
        $eventBegin = new \DateTime();
        $eventBegin->modify('midnight');

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('single');
        $event->setEventBegin($eventBegin);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(0);

        $expectedDays = [$eventBegin->format('U') => $eventBegin];

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * @test
     */
    public function initializeWithExceptionsInEventCallsAddExceptions()
    {
        $eventBegin = new \DateTime();
        $eventBegin->modify('midnight');

        $exception = new Exception();
        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->setEventType('single');
        $event->setEventBegin($eventBegin);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(0);
        $event->setExceptions($exceptions);

        /** @var DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this->getMock(DayGenerator::class, ['addRecurringEvents', 'addDayToStorage', 'addExceptions']);
        $dayGenerator->injectDateTimeUtility(new DateTimeUtility());
        $dayGenerator->expects($this->never())->method('addRecurringEvents');
        $dayGenerator->expects($this->once())->method('addDayToStorage')->with($eventBegin);
        $dayGenerator->expects($this->once())->method('addExceptions');
        $this->assertTrue($dayGenerator->initialize($event));
    }

    /**
     * @test
     */
    public function initializeWithAddExceptionAddsOneDayInStorage()
    {
        $timestamp = mktime(0, 0, 0);

        // These dates will be created with timezone_type = 1, which does know the timezone (+02:00) only from the current date
        $eventBegin = new \DateTime(date('c', $timestamp));
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
        $event->setEventType('single');
        $event->setEventBegin($eventBegin);
        $event->setXth(31);
        $event->setWeekday(127);
        $event->setEachWeeks(0);
        $event->setExceptions($exceptions);

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$tomorrow->format('U')] = $tomorrow;

        $this->assertTrue($this->subject->initialize($event));
        $dateTimeStorage = $this->subject->getDateTimeStorage();

        // assertEquals will only check for correct dates, but not for different timezoneTypes
        $this->assertEquals(
            $expectedDays,
            $dateTimeStorage
        );

        /** @var \DateTime $dateTime */
        foreach ($dateTimeStorage as $dateTime) {
            $this->assertSame(3, $dateTime->timezone_type);
        }
    }

    /**
     * @test
     */
    public function initializeWithRemoveExceptionRemovesOneDayFromStorage()
    {
        $eventBegin = new \DateTime();
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
        $event->setExceptions($exceptions);

        $expectedDays = [];
        $expectedDays[$tomorrow->format('U')] = $tomorrow;

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * @test
     */
    public function initializeWithTimeExceptionDoNotAddDayToStorage()
    {
        $eventBegin = new \DateTime();
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
        $event->setExceptions($exceptions);

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$tomorrow->format('U')] = $tomorrow;

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * @test
     */
    public function initializeWithInfoExceptionDoNotAddDayToStorage()
    {
        $eventBegin = new \DateTime();
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
        $event->setExceptions($exceptions);

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$tomorrow->format('U')] = $tomorrow;

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * @test
     *
     * @expectedException \Exception
     */
    public function initializeWithInvalidExceptionThrowsException()
    {
        $eventBegin = new \DateTime();
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
        $event->setExceptions($exceptions);

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$tomorrow->format('U')] = $tomorrow;

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorage()
        );
    }

    /**
     * @test
     */
    public function getDayStorageResultsInSortedArray()
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
        $this->assertSame(
            $expectedArray,
            $this->subject->getDateTimeStorage()
        );
    }
}
