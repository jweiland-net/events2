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
use JWeiland\Events2\Service\DayGenerator;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Tests\UnitTestCase;

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
        $this->subject = new DayGenerator();
        $this->subject->injectExtConf(new ExtConf());
        $this->subject->injectDateTimeUtility(new DateTimeUtility());

        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['xth']['config']['items'] = array(
            array('first', 'first'),
            array('second', 'second'),
            array('third', 'third'),
            array('fourth', 'fourth'),
            array('fifth', 'fifth'),
        );

        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['weekday']['config']['items'] = array(
            array('monday', 'monday'),
            array('tuesday', 'tuesday'),
            array('wednesday', 'wednesday'),
            array('thursday', 'thursday'),
            array('friday', 'friday'),
            array('saturday', 'saturday'),
            array('sunday', 'sunday'),
        );
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
        $invalidValues = array();
        $invalidValues['string'] = array('Hello', array('Hello'));
        $invalidValues['integer'] = array(123, array(123));
        $invalidValues['boolean'] = array(true, array(true));
        $invalidValues['object'] = array(new \stdClass());
        $invalidValues['null'] = array(null);

        return $invalidValues;
    }

    /**
     * @test
     *
     * @param $event
     * @param $expectedEvent
     * @dataProvider dataProviderWithInvalidValuesForArrayArguments
     */
    public function setEventRecordSetsEvent($event, $expectedEvent = array())
    {
        $this->subject->setEventRecord($event);
        $this->assertSame(
            $expectedEvent,
            $this->subject->getEventRecord()
        );
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
    public function initializeWithEventRecordSetEventRecord()
    {
        $event = array(
            'uid' => 123,
        );
        $this->subject->initialize($event);
        $this->assertSame(
            $event,
            $this->subject->getEventRecord()
        );
    }

    /**
     * @test
     */
    public function initializeWithEmptyEventRecordResultsInFalse()
    {
        $event = array(
            'uid' => 123,
        );
        $this->assertFalse($this->subject->initialize($event));
    }

    /**
     * @test
     */
    public function initializeWithInvalidEventRecordResultsInFalse()
    {
        $event = array(
            'uid' => 123,
        );
        $this->assertFalse($this->subject->initialize($event));
    }

    /**
     * @test
     */
    public function initializeWithRecurringEventCallsAddRecurringEvents()
    {
        $event = array(
            'event_type' => 'recurring',
            'event_begin' => 123456789,
            'event_end' => 0,
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 0,
            'exceptions' => 0,
        );
        /** @var \JWeiland\Events2\Service\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this->getMock('JWeiland\\Events2\\Service\\DayGenerator', array('addRecurringEvents', 'addException', 'getEventBegin'));
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
        $event = array(
            'event_type' => 'recurring',
            'event_begin' => 123456789,
            'event_end' => 0,
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 1,
            'exceptions' => 0,
        );
        /** @var \JWeiland\Events2\Service\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this->getMock('JWeiland\\Events2\\Service\\DayGenerator', array('addRecurringWeeks', 'addException', 'getEventBegin'));
        $dayGenerator->expects($this->once())->method('addRecurringWeeks');
        $dayGenerator->expects($this->never())->method('addException');
        $dayGenerator->expects($this->never())->method('getEventBegin');
        $this->assertTrue($dayGenerator->initialize($event));
    }

    /**
     * @test
     */
    public function initializeWithRecurringWeeksAddsEventBeginToDayStorage()
    {
        $eventBegin = new \DateTime();
        $eventBegin->modify('midnight');
        $eventEnd = new \DateTime();
        $eventEnd->modify('midnight');
        $eventEnd->modify('tomorrow');

        $event = array(
            'event_type' => 'recurring',
            'event_begin' => $eventBegin->format('U'),
            'event_end' => $eventEnd->format('U'),
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 1,
            'exceptions' => 0,
        );

        $expectedDays = array($eventBegin->format('U') => $eventBegin);

        /** @var \JWeiland\Events2\Service\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this->getMock('JWeiland\\Events2\\Service\\DayGenerator', array('addException', 'getMaxDateForGeneratedDays'));
        $dayGenerator->injectDateTimeUtility(new DateTimeUtility());
        $dayGenerator->injectExtConf(new ExtConf());
        $dayGenerator->expects($this->never())->method('addException');
        $dayGenerator->expects($this->once())->method('getMaxDateForGeneratedDays')->willReturn($eventEnd);
        $this->assertTrue($dayGenerator->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $dayGenerator->getDayStorage()
        );
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

        $event = array(
            'event_type' => 'recurring',
            'event_begin' => $eventBegin->format('U'),
            'event_end' => $eventEnd->format('U'),
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 1,
            'exceptions' => 0,
        );

        $expectedDays = array();
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$nextWeek->format('U')] = $nextWeek;
        $expectedDays[$eventEnd->format('U')] = $eventEnd;

        /** @var \JWeiland\Events2\Service\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this->getMock('JWeiland\\Events2\\Service\\DayGenerator', array('addException', 'getMaxDateForGeneratedDays'));
        $dayGenerator->injectDateTimeUtility(new DateTimeUtility());
        $dayGenerator->injectExtConf(new ExtConf());
        $dayGenerator->expects($this->never())->method('addException');
        $dayGenerator->expects($this->once())->method('getMaxDateForGeneratedDays')->willReturn($eventEnd);
        $this->assertTrue($dayGenerator->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $days = $dayGenerator->getDayStorage()
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

        $event = array(
            'event_type' => 'recurring',
            'event_begin' => $eventBegin->format('U'),
            'event_end' => $eventEnd->format('U'),
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 2,
            'exceptions' => 0,
        );

        $expectedDays = array();
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$eventEnd->format('U')] = $eventEnd;

        /** @var \JWeiland\Events2\Service\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this->getMock('JWeiland\\Events2\\Service\\DayGenerator', array('addException', 'getMaxDateForGeneratedDays'));
        $dayGenerator->injectDateTimeUtility(new DateTimeUtility());
        $dayGenerator->injectExtConf(new ExtConf());
        $dayGenerator->expects($this->never())->method('addException');
        $dayGenerator->expects($this->once())->method('getMaxDateForGeneratedDays')->willReturn($eventEnd);
        $this->assertTrue($dayGenerator->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $dayGenerator->getDayStorage()
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

        $event = array(
            'event_type' => 'recurring',
            'event_begin' => $eventBegin->format('U'),
            'recurring_end' => $recurringEnd->format('U'),
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 0,
            'exceptions' => 0,
        );

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            array($eventBegin->format('U') => $eventBegin),
            $this->subject->getDayStorage()
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

        $event = array(
            'event_type' => 'recurring',
            'event_begin' => $eventBegin->format('U'),
            'recurring_end' => $recurringEnd->format('U'),
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 0,
            'exceptions' => 0,
        );

        $expectedDays = array();
        for ($i = 0; $i < 15; ++$i) {
            $expectedDays[$eventBegin->format('U')] = clone $eventBegin;
            $eventBegin->modify('+1 day');
        }

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $this->subject->getDayStorage()
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

        $event = array(
            'event_type' => 'recurring',
            'event_begin' => $eventBegin->format('U'),
            'recurring_end' => $recurringEnd->format('U'),
            'xth' => 0, // all
            'weekday' => 87, // mo, tu, we, fr, su
            'each_weeks' => 0,
            'exceptions' => 0,
        );

        $expectedDays = array();
        $eventBegin->modify('+1 day');
        $expectedDays[$eventBegin->format('U')] = clone $eventBegin; // add sunday
        $eventBegin->modify('+1 day');
        $expectedDays[$eventBegin->format('U')] = clone $eventBegin; // add monday
        $eventBegin->modify('+1 day');
        $expectedDays[$eventBegin->format('U')] = clone $eventBegin; // add tuesday
        $eventBegin->modify('+1 day');
        $expectedDays[$eventBegin->format('U')] = clone $eventBegin; // add wednesday
        $eventBegin->modify('+2 day');
        $expectedDays[$eventBegin->format('U')] = clone $eventBegin; // add friday
        $eventBegin->modify('+2 day');
        $expectedDays[$eventBegin->format('U')] = clone $eventBegin; // add sunday
        ksort($expectedDays);

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $this->subject->getDayStorage()
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
        $eventBegin = new \DateTime('17.09.2016');
        $eventBegin->modify('midnight');
        $recurringEnd = clone $eventBegin;
        $recurringEnd->modify('+20 days'); // 06.02.1015

        $event = array(
            'event_type' => 'recurring',
            'event_begin' => $eventBegin->format('U'),
            'recurring_end' => $recurringEnd->format('U'),
            'xth' => 21, // 1st, 3rd, 5th
            'weekday' => 18, // tu, fr
            'each_weeks' => 0,
            'exceptions' => 0,
        );

        $expectedDays = array();
        $eventBegin->modify('+3 day'); // start with sa
        $expectedDays[$eventBegin->format('U')] = clone $eventBegin; // add 3rd tu 20.01
        $eventBegin->modify('+10 day');
        $expectedDays[$eventBegin->format('U')] = clone $eventBegin; // add 5th fr 30.01
        $eventBegin->modify('+4 day');
        $expectedDays[$eventBegin->format('U')] = clone $eventBegin; // add 1st tu 03.02
        $eventBegin->modify('+3 day');
        $expectedDays[$eventBegin->format('U')] = clone $eventBegin; // add 1st fr 06.03
        ksort($expectedDays);

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $this->subject->getDayStorage()
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

        $event = array(
            'event_type' => 'recurring',
            'event_begin' => $eventBegin->format('U'),
            'recurring_end' => $recurringEnd->format('U'),
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 0,
            'exceptions' => 0,
        );

        $expectedDays = array();
        for ($i = 0; $i < 5; ++$i) {
            $expectedDays[$eventBegin->format('U')] = clone $eventBegin;
            $eventBegin->modify('+1 day');
        }

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $this->subject->getDayStorage()
        );
    }

    /**
     * @test
     */
    public function initializeWithEventBeginAddsOneDayToStorage()
    {
        $eventBegin = new \DateTime();
        $eventBegin->modify('midnight');

        $event = array(
            'event_type' => 'single',
            'event_begin' => $eventBegin->format('U'),
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 0,
            'exceptions' => 0,
        );

        $expectedDays = array($eventBegin->format('U') => $eventBegin);

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $this->subject->getDayStorage()
        );
    }

    /**
     * @test
     */
    public function initializeWithExceptionsInEventCallsAddExceptions()
    {
        $eventBegin = new \DateTime();
        $eventBegin->modify('midnight');

        $event = array(
            'event_type' => 'single',
            'event_begin' => $eventBegin->format('U'),
            'event_end' => 0,
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 0,
            'exceptions' => array(
                0 => 'TestValue',
            ),
        );

        /** @var \JWeiland\Events2\Service\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this->getMock('JWeiland\\Events2\\Service\\DayGenerator', array('addRecurringEvents', 'addDayToStorage', 'addExceptions'));
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
        $eventBegin = new \DateTime();
        $eventBegin->modify('midnight');
        $tomorrow = clone $eventBegin;
        $tomorrow->modify('tomorrow');

        $event = array(
            'event_type' => 'single',
            'event_begin' => $eventBegin->format('U'),
            'event_end' => 0,
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 0,
            'exceptions' => array(
                0 => array(
                    'exception_type' => 'Add',
                    'exception_date' => $tomorrow->format('U'),
                ),
            ),
        );

        $expectedDays = array();
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$tomorrow->format('U')] = $tomorrow;

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $this->subject->getDayStorage()
        );
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

        $event = array(
            'event_type' => 'duration',
            'event_begin' => $eventBegin->format('U'),
            'event_end' => $tomorrow->format('U'),
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 0,
            'exceptions' => array(
                0 => array(
                    'exception_type' => 'Remove',
                    'exception_date' => $eventBegin->format('U'),
                ),
            ),
        );

        $expectedDays = array();
        $expectedDays[$tomorrow->format('U')] = $tomorrow;

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $this->subject->getDayStorage()
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

        $event = array(
            'event_type' => 'duration',
            'event_begin' => $eventBegin->format('U'),
            'event_end' => $tomorrow->format('U'),
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 0,
            'exceptions' => array(
                0 => array(
                    'exception_type' => 'Time',
                ),
            ),
        );

        $expectedDays = array();
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$tomorrow->format('U')] = $tomorrow;

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $this->subject->getDayStorage()
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

        $event = array(
            'event_type' => 'duration',
            'event_begin' => $eventBegin->format('U'),
            'event_end' => $tomorrow->format('U'),
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 0,
            'exceptions' => array(
                0 => array(
                    'exception_type' => 'Info',
                ),
            ),
        );

        $expectedDays = array();
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$tomorrow->format('U')] = $tomorrow;

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $this->subject->getDayStorage()
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

        $event = array(
            'event_type' => 'duration',
            'event_begin' => $eventBegin->format('U'),
            'event_end' => $tomorrow->format('U'),
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 0,
            'exceptions' => array(
                0 => array(
                    'exception_type' => 'invalid value',
                ),
            ),
        );

        $expectedDays = array();
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$tomorrow->format('U')] = $tomorrow;

        $this->assertTrue($this->subject->initialize($event));
        $this->assertEquals(
            $expectedDays,
            $this->subject->getDayStorage()
        );
    }

    /**
     * @test
     */
    public function getDayStorageResultsInSortedArray()
    {
        $this->subject->setDayStorage(array(
            4 => 'TestValue',
            2 => 'TestValue',
            0 => 'TestValue',
            3 => 'TestValue',
            1 => 'TestValue',
        ));
        $expectedArray = array(
            0 => 'TestValue',
            1 => 'TestValue',
            2 => 'TestValue',
            3 => 'TestValue',
            4 => 'TestValue',
        );
        $this->assertSame(
            $expectedArray,
            $this->subject->getDayStorage()
        );
    }
}
