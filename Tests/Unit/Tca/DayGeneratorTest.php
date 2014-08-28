<?php
namespace JWeiland\Events2\Tests\Unit\Tca;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Stefan Froemken <projects@jweiland.net>, jweiland.net
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Tca\DayGenerator;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case for class \JWeiland\Events2\Tca\DayGenerator.
 *
 * @package TYPO3
 * @subpackage Events
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class DayGeneratorTest extends UnitTestCase {
	/**
	 * @var \JWeiland\Events2\Tca\DayGenerator
	 */
	protected $subject;

	public function setUp() {
		$this->subject = new DayGenerator();
	}

	public function tearDown() {
		unset($this->dayGenerator, $this->objectManager);
	}

	/**
	 * @test
	 */
	public function initializeWithRecurringEventCallsAddRecurringEvents() {
		$event = array(
			'recurring_event' => 1,
			'event_end' => 0
		);
		/** @var \JWeiland\Events2\Tca\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('addRecurringEvents', 'addException', 'getEventBegin'));
		$dayGenerator->expects($this->once())->method('addRecurringEvents');
		$dayGenerator->expects($this->never())->method('addException');
		$dayGenerator->expects($this->never())->method('getEventBegin');
		$dayGenerator->initialize($event);
	}

	/**
	 * @test
	 */
	public function initializeWithEventWithEventEndLoopsAddDayToStorage() {
		$event = array(
			'recurring_event' => 0,
			'event_end' => 1
		);
		$eventBegin = new \DateTime();
		$eventEnd = new \DateTime();
		$eventEnd->modify('+4 days');

		/** @var \JWeiland\Events2\Tca\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('addRecurringEvents', 'addException', 'getEventBegin', 'getEventEnd', 'addDayToStorage'));
		$dayGenerator->expects($this->never())->method('addRecurringEvents');
		$dayGenerator->expects($this->never())->method('addException');
		$dayGenerator->expects($this->once())->method('getEventBegin')->will($this->returnValue($eventBegin));
		$dayGenerator->expects($this->once())->method('getEventEnd')->will($this->returnValue($eventEnd));
		$dayGenerator->expects($this->exactly(5))->method('addDayToStorage');
		$dayGenerator->initialize($event);
	}

	/**
	 * @test
	 */
	public function initializeWithEventBeginCallsAddDayToStorage() {
		$event = array(
			'recurring_event' => 0,
			'event_end' => 0
		);
		$eventBegin = new \DateTime();

		/** @var \JWeiland\Events2\Tca\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('addRecurringEvents', 'addException', 'getEventBegin', 'addDayToStorage'));
		$dayGenerator->expects($this->never())->method('addRecurringEvents');
		$dayGenerator->expects($this->never())->method('addException');
		$dayGenerator->expects($this->once())->method('getEventBegin')->will($this->returnValue($eventBegin));
		$dayGenerator->expects($this->once())->method('addDayToStorage')->with($eventBegin);
		$dayGenerator->initialize($event);
	}

	/**
	 * @test
	 */
	public function initializeWithExceptionsInEventCallsAddException() {
		$event = array(
			'recurring_event' => 0,
			'event_end' => 0,
			'exceptions' => array(
				0 => 'TestValue'
			)
		);
		$eventBegin = new \DateTime();

		/** @var \JWeiland\Events2\Tca\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('addRecurringEvents', 'addException', 'getEventBegin', 'addDayToStorage', 'addExceptions'));
		$dayGenerator->expects($this->never())->method('addRecurringEvents');
		$dayGenerator->expects($this->never())->method('addException');
		$dayGenerator->expects($this->once())->method('getEventBegin')->will($this->returnValue($eventBegin));
		$dayGenerator->expects($this->once())->method('addDayToStorage')->with($eventBegin);
		$dayGenerator->expects($this->once())->method('addExceptions');
		$dayGenerator->initialize($event);
	}

	/**
	 * @test
	 */
	public function getEventRecordInitiallyReturnsEmptyArray() {
		$this->assertSame(
			array(),
			$this->subject->getEventRecord()
		);
	}

	/**
	 * @test
	 */
	public function setEventRecordSetsEventRecord() {
		$array = array(
			0 => 'TestValue'
		);
		$this->subject->setEventRecord($array);

		$this->assertSame(
			$array,
			$this->subject->getEventRecord()
		);
	}

	/**
	 * @test
	 */
	public function setEventRecordWithIntegerResultsInArray() {
		$this->subject->setEventRecord(123);
		$this->assertSame(array(123), $this->subject->getEventRecord());
	}

	/**
	 * @test
	 */
	public function setEventRecordWithBooleanResultsInArray() {
		$this->subject->setEventRecord(TRUE);
		$this->assertSame(array(TRUE), $this->subject->getEventRecord());
	}

	/**
	 * @test
	 */
	public function addDayToStorageAddsDayToStorage() {
		$day = new \DateTime();
		$expectedDayStorage = array(
			$day->format('U') => $day
		);
		$this->subject->addDayToStorage($day);
		$this->assertEquals(
			$expectedDayStorage,
			$this->subject->getDayStorage()
		);
	}

	/**
	 * @test
	 */
	public function addDayToStorageResultsInSameDayStorage() {
		$day = new \DateTime();
		$expectedDayStorage = array(
			$day->format('U') => $day
		);
		// test grouping of days
		for ($i = 0; $i < 5; $i++) {
			$this->subject->addDayToStorage($day);
		}
		$this->assertEquals(
			$expectedDayStorage,
			$this->subject->getDayStorage()
		);
	}

	/**
	 * @test
	 */
	public function removeDayFromStorageResultsInSmallerDayStorage() {
		$day = new \DateTime();
		$this->subject->setDayStorage(array(
			$day->format('U') => $day
		));
		$this->subject->removeDayFromStorage($day);
		$this->assertSame(
			array(),
			$this->subject->getDayStorage()
		);
	}

	/**
	 * @test
	 */
	public function getDayStorageResultsInSortedArray() {
		$this->subject->setDayStorage(array(
			4 => 'TestValue',
			2 => 'TestValue',
			0 => 'TestValue',
			3 => 'TestValue',
			1 => 'TestValue'
		));
		$expectedArray = array(
			0 => 'TestValue',
			1 => 'TestValue',
			2 => 'TestValue',
			3 => 'TestValue',
			4 => 'TestValue'
		);
		$this->assertSame(
			$expectedArray,
			$this->subject->getDayStorage()
		);
	}

	/**
	 * @return array
	 */
	public function dataProviderForGetEventBeginAndEnd() {
		$dateTimeUtility = new DateTimeUtility();
		$daysAsInteger = array();
		$daysAsInteger['0'] = array(0, $dateTimeUtility->convert(0));
		$daysAsInteger['-1236745'] = array(-1236745, $dateTimeUtility->convert(-1236745));
		if (strlen(decbin(~0)) === 64) {
			// on 64bit systems we can test higher values
			$daysAsInteger['127834564325'] = array(127834564325, $dateTimeUtility->convert(127834564325));
		} else {
			$daysAsInteger['2140000000'] = array(2140000000, $dateTimeUtility->convert(2140000000));
		}
		return $daysAsInteger;
	}


	/**
	 * @test
	 *
	 * @dataProvider dataProviderForGetEventBeginAndEnd
	 */
	public function getEventBeginConvertsIntegerToDateTime($eventAsInteger, $expectedDay) {
		$this->subject->injectDateTimeUtility(new DateTimeUtility());
		$this->subject->setEventRecord(array(
			'event_begin' => $eventAsInteger
		));
		$this->assertEquals(
			$expectedDay,
			$this->subject->getEventBegin()
		);
	}

	/**
	 * @test
	 *
	 * @dataProvider dataProviderForGetEventBeginAndEnd
	 */
	public function getEventEndConvertsIntegerToDateTime($eventAsInteger, $expectedDay) {
		$this->subject->injectDateTimeUtility(new DateTimeUtility());
		$this->subject->setEventRecord(array(
			'event_end' => $eventAsInteger
		));
		$this->assertEquals(
			$expectedDay,
			$this->subject->getEventEnd()
		);
	}

	/**
	 * @test
	 */
	public function getEventEndWithEmptyStringResultsInNull() {
		$this->subject->injectDateTimeUtility(new DateTimeUtility());
		$this->subject->setEventRecord(array(
			'event_end' => ''
		));
		$this->assertEquals(
			NULL,
			$this->subject->getEventEnd()
		);
	}

	/**
	 * @test
	 */
	public function getMaxDateForGeneratedDaysReturnsDateOfExtConf() {
		$maxEventEnd = new \DateTime();
		$maxEventEnd->modify('+4 month midnight');
		$eventEnd = new \DateTime();
		$eventEnd->modify('+6 months midnight');

		/** @var \JWeiland\Events2\Configuration\ExtConf|\PHPUnit_Framework_MockObject_MockObject $extConf */
		$extConf = $this->getMock('JWeiland\\Events2\\Configuration\\ExtConf', array('dummy'), array(), '', FALSE);
		$extConf->setRecurringFuture(4);

		/** @var \JWeiland\Events2\Tca\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('getEventEnd'));
		$dayGenerator->injectExtConf($extConf);
		$dayGenerator->injectDateTimeUtility(new DateTimeUtility());
		$dayGenerator->expects($this->once())->method('getEventEnd')->will($this->returnValue($eventEnd));

		$this->assertEquals(
			$maxEventEnd,
			$dayGenerator->getMaxDateForGeneratedDays()
		);
	}

	/**
	 * @test
	 */
	public function getMaxDateForGeneratedDaysReturnsEventEnd() {
		$maxEventEnd = new \DateTime();
		$maxEventEnd->modify('+6 month midnight');
		$eventEnd = new \DateTime();
		$eventEnd->modify('+4 months midnight');

		/** @var \JWeiland\Events2\Configuration\ExtConf|\PHPUnit_Framework_MockObject_MockObject $extConf */
		$extConf = $this->getMock('JWeiland\\Events2\\Configuration\\ExtConf', array('dummy'), array(), '', FALSE);
		$extConf->setRecurringFuture(6);

		/** @var \JWeiland\Events2\Tca\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('getEventEnd'));
		$dayGenerator->injectExtConf($extConf);
		$dayGenerator->injectDateTimeUtility(new DateTimeUtility());
		$dayGenerator->expects($this->once())->method('getEventEnd')->will($this->returnValue($eventEnd));

		$this->assertEquals(
			$eventEnd,
			$dayGenerator->getMaxDateForGeneratedDays()
		);
	}

	/**
	 * @test
	 */
	public function getXthWithNoItemsResultsInEmptyArray() {
		/** @var \JWeiland\Events2\Tca\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('getItemsFromTca'));
		$dayGenerator->expects($this->once())->method('getItemsFromTca')->with($this->equalTo('xth'))->will($this->returnValue(array()));
		$this->assertSame(
			array(),
			$dayGenerator->getXth()
		);
	}

	/**
	 * @test
	 */
	public function getXthWithEmptyItemValuesResultsInFilledArray() {
		$expectedValue = $returnValue = array();
		$xth = array('first', 'second', 'third', 'fourth', 'fifth');
		for ($i = 0; $i < 5; $i++) {
			$returnValue[] = array('lang_' . $xth[$i], $xth[$i]);
			$expectedValue[$xth[$i]] = 1;
		}

		/** @var \JWeiland\Events2\Tca\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('getItemsFromTca'));
		$dayGenerator->expects($this->once())->method('getItemsFromTca')->with($this->equalTo('xth'))->will($this->returnValue($returnValue));
		$this->assertSame(
			$expectedValue,
			$dayGenerator->getXth()
		);
	}

	/**
	 * @test
	 */
	public function getXthWithRandomItemValuesResultsInRandomArray() {
		$expectedValue = $returnValue = array();
		$xth = array('first', 'second', 'third', 'fourth', 'fifth');
		$xthValues = array(1, 2, 4, 0, 16);
		for ($i = 0; $i < 5; $i++) {
			$returnValue[] = array('lang_' . $xth[$i], $xth[$i]);
			$expectedValue[$xth[$i]] = $xthValues[$i];
		}

		/** @var \JWeiland\Events2\Tca\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('getItemsFromTca'));
		$dayGenerator->expects($this->once())->method('getItemsFromTca')->with($this->equalTo('xth'))->will($this->returnValue($returnValue));
		$dayGenerator->setEventRecord(array('xth' => 23));
		$this->assertSame(
			$expectedValue,
			$dayGenerator->getXth()
		);
	}

	/**
	 * @test
	 */
	public function getWeekdayWithNoItemsResultsInEmptyArray() {
		/** @var \JWeiland\Events2\Tca\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('getItemsFromTca'));
		$dayGenerator->expects($this->once())->method('getItemsFromTca')->with($this->equalTo('weekday'))->will($this->returnValue(array()));
		$this->assertSame(
			array(),
			$dayGenerator->getWeekday()
		);
	}

	/**
	 * @test
	 */
	public function getWeekdayWithRandomItemValuesResultsInRandomArray() {
		$expectedValue = $returnValue = array();
		$weekday = array('monday', 'thuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
		$weekdayValues = array(1, 2, 4, 0, 16, 0, 64);
		for ($i = 0; $i < 5; $i++) {
			$returnValue[] = array('lang_' . $weekday[$i], $weekday[$i]);
			$expectedValue[$weekday[$i]] = $weekdayValues[$i];
		}

		/** @var \JWeiland\Events2\Tca\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('getItemsFromTca'));
		$dayGenerator->expects($this->once())->method('getItemsFromTca')->with($this->equalTo('weekday'))->will($this->returnValue($returnValue));
		$dayGenerator->setEventRecord(array('weekday' => 87));
		$this->assertSame(
			$expectedValue,
			$dayGenerator->getWeekday()
		);
	}

	/**
	 * @test
	 */
	public function addRecurringEventsWithEachWeeksCallsAddRecurringWeeks() {
		/** @var \JWeiland\Events2\Tca\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('addRecurringWeeks', 'getEventBegin'));
		$dayGenerator->setEventRecord(array('each_weeks' => 1));
		$dayGenerator->expects($this->once())->method('addRecurringWeeks');
		$dayGenerator->expects($this->never())->method('getEventBegin');
		$dayGenerator->addRecurringEvents();
	}

	/**
	 * @test
	 */
	public function addRecurringEventsWithoutEachWeeksCallsAddDaysForMonth() {
		$eventBegin = new \DateTime();
		$eventBegin->modify('midnight');
		$maxDate = new \DateTime();
		$maxDate->modify('+3 months midnight');
		/** @var \JWeiland\Events2\Tca\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('addRecurringWeeks', 'getEventBegin', 'getMaxDateForGeneratedDays', 'addDaysForMonth'));
		$dayGenerator->setEventRecord(array('each_weeks' => 0));
		$dayGenerator->expects($this->never())->method('addRecurringWeeks');
		$dayGenerator->expects($this->once())->method('getEventBegin')->will($this->returnValue($eventBegin));
		$dayGenerator->expects($this->once())->method('getMaxDateForGeneratedDays')->will($this->returnValue($maxDate));
		$dayGenerator->expects($this->exactly(4))->method('addDaysForMonth');

		$dayGenerator->addRecurringEvents();
	}

	/**
	 * @test
	 */
	public function addRecurringWeeksAddsDayForEachWeek() {
		$eventBegin = new \DateTime();
		$eventBegin->modify('midnight');
		$maxDate = new \DateTime();
		$maxDate->modify('+5 weeks midnight');
		/** @var \JWeiland\Events2\Tca\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('getEventBegin', 'addDayToStorage', 'getMaxDateForGeneratedDays'));
		$dayGenerator->setEventRecord(array('each_weeks' => 1));
		$dayGenerator->expects($this->once())->method('getEventBegin')->will($this->returnValue($eventBegin));
		$dayGenerator->expects($this->at(1))->method('addDayToStorage')->with($this->equalTo($eventBegin));
		$dayGenerator->expects($this->once())->method('getMaxDateForGeneratedDays')->will($this->returnValue($maxDate));
		for ($i = 0; $i < 5; $i++) {
			$dayGenerator->expects($this->at($i + 3))->method('addDayToStorage')->with($this->equalTo($eventBegin));
		}

		$dayGenerator->addRecurringWeeks();
	}

	/**
	 * @test
	 */
	public function addDaysForMonth() {

	}

	/**
	 * @test
	 */
	public function addExceptionsOfTypeAddCallsAddDayToStorage() {
		$eventRecord = array(
			'exceptions' => array(
				0 => array(
					'exception_type' => 'Add',
					'exception_date' => 1273645812
				)
			)
		);
		/** @var \JWeiland\Events2\Tca\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('addDayToStorage'));
		$dayGenerator->setEventRecord($eventRecord);
		$dayGenerator->injectDateTimeUtility(new DateTimeUtility());
		$dayGenerator->expects($this->once())->method('addDayToStorage');
		$dayGenerator->addExceptions();
	}

	/**
	 * @test
	 */
	public function addExceptionsOfTypeRemoveCallsAddDayToStorage() {
		$eventRecord = array(
			'exceptions' => array(
				0 => array(
					'exception_type' => 'Remove',
					'exception_date' => 1273645812
				)
			)
		);
		/** @var \JWeiland\Events2\Tca\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('removeDayFromStorage'));
		$dayGenerator->setEventRecord($eventRecord);
		$dayGenerator->injectDateTimeUtility(new DateTimeUtility());
		$dayGenerator->expects($this->once())->method('removeDayFromStorage');
		$dayGenerator->addExceptions();
	}

	/**
	 * @test
	 */
	public function addExceptionsOfTypeTimeCallsNothing() {
		$eventRecord = array(
			'exceptions' => array(
				0 => array(
					'exception_type' => 'Time',
					'exception_date' => 1273645812
				)
			)
		);
		/** @var \JWeiland\Events2\Tca\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('addDayToStorage', 'removeDayFromStorage'));
		$dayGenerator->setEventRecord($eventRecord);
		$dayGenerator->expects($this->never())->method('addDayToStorage');
		$dayGenerator->expects($this->never())->method('removeDayFromStorage');
		$dayGenerator->addExceptions();
	}

	/**
	 * @test
	 */
	public function addExceptionsOfTypeInfoCallsNothing() {
		$eventRecord = array(
			'exceptions' => array(
				0 => array(
					'exception_type' => 'Info',
					'exception_date' => 1273645812
				)
			)
		);
		/** @var \JWeiland\Events2\Tca\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('addDayToStorage', 'removeDayFromStorage'));
		$dayGenerator->setEventRecord($eventRecord);
		$dayGenerator->expects($this->never())->method('addDayToStorage');
		$dayGenerator->expects($this->never())->method('removeDayFromStorage');
		$dayGenerator->addExceptions();
	}

	/**
	 * @test
	 *
	 * @expectedException \Exception
	 */
	public function addExceptionsOfUnknownTypeResultsInException() {
		$eventRecord = array(
			'exceptions' => array(
				0 => array(
					'exception_type' => 'Foo Bar',
					'exception_date' => 1273645812
				)
			)
		);
		/** @var \JWeiland\Events2\Tca\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('addDayToStorage', 'removeDayFromStorage'));
		$dayGenerator->setEventRecord($eventRecord);
		$dayGenerator->expects($this->never())->method('addDayToStorage');
		$dayGenerator->expects($this->never())->method('removeDayFromStorage');
		$dayGenerator->addExceptions();
	}

}