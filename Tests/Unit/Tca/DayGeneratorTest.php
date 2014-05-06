<?php
namespace JWeiland\Events2\Unit\Tca;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Stefan Froemken <sfroemken@jweiland.net>, jweiland.net
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

/**
 * Test case for class Tx_Events2_Controller_EventController.
 *
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 * @package TYPO3
 * @subpackage Events
 *
 * @author Stefan Froemken <sfroemken@jweiland.net>
 */
class DayGeneratorTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {
	/**
	 * @var \JWeiland\Events2\Tca\DayGenerator
	 */
	protected $dayGenerator;





	public function setUp() {
		$this->dayGenerator = $this->objectManager->get('JWeiland\\Events2\\Tca\\DayGenerator');
	}

	public function tearDown() {
		unset($this->dayGenerator);
	}





	/**
	 * @test
	 */
	public function testGetEventBegin() {
		$eventRecord['event_begin'] = NULL;
		$this->dayGenerator->setEventRecord($eventRecord);
		$eventBegin = $this->dayGenerator->getEventBegin();
		$this->assertEquals(NULL, $eventBegin);

		$eventRecord['event_begin'] = 0;
		$this->dayGenerator->setEventRecord($eventRecord);
		$eventBegin = $this->dayGenerator->getEventBegin();
		$this->assertEquals(NULL, $eventBegin);

		$eventRecord['event_begin'] = '';
		$this->dayGenerator->setEventRecord($eventRecord);
		$eventBegin = $this->dayGenerator->getEventBegin();
		$this->assertEquals(NULL, $eventBegin);

		// We are working with UTC time. So here in germany we have to add 1 hour
		$eventRecord['event_begin'] = 1358380800;
		$this->dayGenerator->setEventRecord($eventRecord);
		$eventBegin = $this->dayGenerator->getEventBegin();
		$this->assertEquals('17.01.2013 01:00:00', $eventBegin->format('d.m.Y H:i:s'));
	}

	/**
	 * @test
	 */
	public function testGetEventEnd() {
		$eventRecord['event_end'] = NULL;
		$this->dayGenerator->setEventRecord($eventRecord);
		$eventEnd = $this->dayGenerator->getEventEnd();
		$this->assertEquals(NULL, $eventEnd);

		$eventRecord['event_end'] = 0;
		$this->dayGenerator->setEventRecord($eventRecord);
		$eventEnd = $this->dayGenerator->getEventEnd();
		$this->assertEquals(NULL, $eventEnd);

		$eventRecord['event_end'] = '';
		$this->dayGenerator->setEventRecord($eventRecord);
		$eventEnd = $this->dayGenerator->getEventEnd();
		$this->assertEquals(NULL, $eventEnd);

		// We are working with UTC time. So here in germany we have to add 1 hour
		$eventRecord['event_end'] = 1358380800;
		$this->dayGenerator->setEventRecord($eventRecord);
		$eventEnd = $this->dayGenerator->getEventEnd();
		$this->assertEquals('17.01.2013 01:00:00', $eventEnd->format('d.m.Y H:i:s'));
	}

	/**
	 * @test
	 */
	public function testGetMaxDateForGeneratedDays() {
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('createDateTime'));

		$extConf = $this->getMock('JWeiland\\Events2\\Configuration\\ExtConf', array('getRecurringFuture'));
		$extConf
			->expects($this->once())
			->method('getRecurringFuture')
			->will($this->returnValue(6));

		$dayGenerator->injectExtConf($extConf);

		$dayGenerator
			->expects($this->any())
			->method('createDateTime')
			->with($this->equalTo('today'))
			->will(
				$this->returnValue(
					// set date to 17.01.2013
					new DateTime(date('c', 1358380800))
				)
			);

		$maxDate = $dayGenerator->getMaxDateForGeneratedDays();
		$this->assertEquals('17.07.2013 01:00:00', $maxDate->format('d.m.Y H:i:s'));

		$extConf = $this->getMock('JWeiland\\Events2\\Configuration\\ExtConf', array('getRecurringFuture'));
		$extConf
			->expects($this->once())
			->method('getRecurringFuture')
			->will($this->returnValue(14));

		$dayGenerator->injectExtConf($extConf);

		$maxDate = $dayGenerator->getMaxDateForGeneratedDays();
		$this->assertEquals('17.03.2014 01:00:00', $maxDate->format('d.m.Y H:i:s'));
	}

	/**
	 * @test
	 */
	public function testGetXth() {
		$items = array();
		$items[0] = array('Erster', 'first');
		$items[1] = array('Zweiter', 'second');
		$items[2] = array('Dritter', 'third');
		$items[3] = array('Vierter', 'fourth');
		$items[4] = array('Fuenfter', 'fifth');

		$GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['xth']['config']['items'] = $items;

		$eventRecord['xth'] = 5;
		$this->dayGenerator->setEventRecord($eventRecord);

		$expected = array();
		$expected['first'] = 1;
		$expected['second'] = 0;
		$expected['third'] = 4;
		$expected['fourth'] = 0;
		$expected['fifth'] = 0;

		$xth = $this->dayGenerator->getXth();
		$this->assertEquals($expected, $xth);

		$eventRecord['xth'] = 0;
		$this->dayGenerator->setEventRecord($eventRecord);

		$expected = array();
		$expected['first'] = 0;
		$expected['second'] = 0;
		$expected['third'] = 0;
		$expected['fourth'] = 0;
		$expected['fifth'] = 0;

		$xth = $this->dayGenerator->getXth();
		$this->assertEquals($expected, $xth);

		$eventRecord['xth'] = 31;
		$this->dayGenerator->setEventRecord($eventRecord);

		$expected = array();
		$expected['first'] = 1;
		$expected['second'] = 2;
		$expected['third'] = 4;
		$expected['fourth'] = 8;
		$expected['fifth'] = 16;

		$xth = $this->dayGenerator->getXth();
		$this->assertEquals($expected, $xth);
	}

	/**
	 * @test
	 */
	public function testGetWeekday() {
		$items = array();
		$items[0] = array('Montag', 'monday');
		$items[1] = array('Dienstag', 'tuesday');
		$items[2] = array('Mittwoch', 'wednesday');
		$items[3] = array('Donnerstag', 'thursday');
		$items[4] = array('Freitag', 'friday');
		$items[5] = array('Samstag', 'saturday');
		$items[6] = array('Sonntag', 'sunday');

		$GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['weekday']['config']['items'] = $items;

		$eventRecord['weekday'] = 5;
		$this->dayGenerator->setEventRecord($eventRecord);

		$expected = array();
		$expected['monday'] = 1;
		$expected['tuesday'] = 0;
		$expected['wednesday'] = 4;
		$expected['thursday'] = 0;
		$expected['friday'] = 0;
		$expected['saturday'] = 0;
		$expected['sunday'] = 0;

		$xth = $this->dayGenerator->getWeekday();
		$this->assertEquals($expected, $xth);

		$eventRecord['weekday'] = 0;
		$this->dayGenerator->setEventRecord($eventRecord);

		$expected = array();
		$expected['monday'] = 0;
		$expected['tuesday'] = 0;
		$expected['wednesday'] = 0;
		$expected['thursday'] = 0;
		$expected['friday'] = 0;
		$expected['saturday'] = 0;
		$expected['sunday'] = 0;

		$xth = $this->dayGenerator->getWeekday();
		$this->assertEquals($expected, $xth);

		$eventRecord['weekday'] = 127;
		$this->dayGenerator->setEventRecord($eventRecord);

		$expected = array();
		$expected['monday'] = 1;
		$expected['tuesday'] = 2;
		$expected['wednesday'] = 4;
		$expected['thursday'] = 8;
		$expected['friday'] = 16;
		$expected['saturday'] = 32;
		$expected['sunday'] = 64;

		$xth = $this->dayGenerator->getWeekday();
		$this->assertEquals($expected, $xth);
	}

	/**
	 * @test
	 */
	public function testAddDayToStorage() {
		$day = new DateTime('17.01.2013 01:00:00');
		$this->dayGenerator->addDayToStorage($day);
		$days = $this->dayGenerator->getDayStorage();
		$this->assertEquals(TRUE, array_key_exists(1358380800, $days));
	}

	/**
	 * @test
	 */
	public function testRemoveDayFromStorage() {
		$day = new DateTime('17.01.2013 01:00:00');
		$this->dayGenerator->removeDayFromStorage($day);
		$days = $this->dayGenerator->getDayStorage();
		$this->assertEquals(FALSE, array_key_exists(1358380800, $days));
	}

	/**
	 * @test
	 */
	public function testAddRecurringEvents() {
		$this->dayGenerator;
	}

	/**
	 * @test
	 */
	public function testAddRecurringWeeks() {
		/* Simple Test each 2 weeks */
		// We are working with UTC time. So here in germany we have to add 1 hour (17.01.2013)
		$eventRecord = array();
		$eventRecord['event_begin'] = 1358380800;
		$eventRecord['each_weeks'] = 2;
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('getMaxDateForGeneratedDays'));
		$dayGenerator
			->expects($this->any())
			->method('getMaxDateForGeneratedDays')
			->will(
				$this->returnValue(
					// set date to 17.02.2013
					new DateTime(date('c', 1361059200))
				)
			);
		$dayGenerator->setEventRecord($eventRecord);
		$dayGenerator->addRecurringWeeks();
		$expected = array();
		$expected[1358380800] = new DateTime(date('c', 1358380800));
		$expected[1359590400] = new DateTime(date('c', 1359590400));
		$expected[1360800000] = new DateTime(date('c', 1360800000));
		$this->assertEquals($expected, $dayGenerator->getDayStorage());

		/* Test over years and 28.02.2014 */
		// We are working with UTC time. So here in germany we have to add 1 hour (05.12.2013)
		$eventRecord = array();
		$eventRecord['event_begin'] = 1386201600;
		$eventRecord['each_weeks'] = 3;
		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('getMaxDateForGeneratedDays'));
		$dayGenerator
			->expects($this->any())
			->method('getMaxDateForGeneratedDays')
			->will(
				$this->returnValue(
				// set date to 08.03.2013
					new DateTime(date('c', 1395964800))
				)
			);
		$dayGenerator->setEventRecord($eventRecord);
		$dayGenerator->addRecurringWeeks();
		$expected = array();
		$expected[1386201600] = new DateTime(date('c', 1386201600));
		$expected[1388016000] = new DateTime(date('c', 1388016000));
		$expected[1389830400] = new DateTime(date('c', 1389830400));
		$expected[1391644800] = new DateTime(date('c', 1391644800));
		$expected[1393459200] = new DateTime(date('c', 1393459200));
		$expected[1395273600] = new DateTime(date('c', 1395273600));
		$this->assertEquals($expected, $dayGenerator->getDayStorage());
	}

	/**
	 * @test
	 */
	public function testAddDaysForMonth() {
		/* only days bigger than event_start should be added */
		// We are working with UTC time. So here in germany we have to add 1 hour (17.01.2013)
		$eventRecord = array();
		$eventRecord['event_begin'] = 1358380800;

		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('getXth', 'getWeekday'));

		$xth = array();
		$xth['first'] = 1;
		$xth['second'] = 0;
		$xth['third'] = 4;
		$xth['fourth'] = 0;
		$xth['fifth'] = 0;

		$dayGenerator
			->expects($this->once())
			->method('getXth')
			->will($this->returnValue($xth));

		$weekday = array();
		$weekday['monday'] = 1;
		$weekday['tuesday'] = 0;
		$weekday['wednesday'] = 4;
		$weekday['thursday'] = 0;
		$weekday['friday'] = 0;
		$weekday['saturday'] = 0;
		$weekday['sunday'] = 0;

		$dayGenerator
			->expects($this->once())
			->method('getWeekday')
			->will($this->returnValue($weekday));

		$dayGenerator->setEventRecord($eventRecord);
		$dayGenerator->addDaysForMonth('January', 2013);

		// only days greater than or equal to 17.01 will be added. Thats why we have only one day in storage
		$this->assertEquals(1, count($dayGenerator->getDayStorage()));
		$this->assertEquals(TRUE, array_key_exists(1358722800, $dayGenerator->getDayStorage()));

		/* all days of month should be added */
		// We are working with UTC time. So here in germany we have to add 1 hour (17.01.2013)
		$eventRecord = array();
		$eventRecord['event_begin'] = 1358380800;

		$dayGenerator = $this->getMock('JWeiland\\Events2\\Tca\\DayGenerator', array('getXth', 'getWeekday'));

		$xth = array();
		$xth['first'] = 1;
		$xth['second'] = 2;
		$xth['third'] = 4;
		$xth['fourth'] = 8;
		$xth['fifth'] = 16;

		$dayGenerator
			->expects($this->once())
			->method('getXth')
			->will($this->returnValue($xth));

		$weekday = array();
		$weekday['monday'] = 1;
		$weekday['tuesday'] = 2;
		$weekday['wednesday'] = 4;
		$weekday['thursday'] = 8;
		$weekday['friday'] = 16;
		$weekday['saturday'] = 32;
		$weekday['sunday'] = 64;

		$dayGenerator
			->expects($this->once())
			->method('getWeekday')
			->will($this->returnValue($weekday));

		$dayGenerator->setEventRecord($eventRecord);
		$dayGenerator->addDaysForMonth('April', 2013);
		// only days greater than or equal to 17.01 will be added. Thats why we have only one day in storage
		$this->assertEquals(30, count($dayGenerator->getDayStorage()));
		$firstDay = new DateTime('today'); // initialize time to 00:00:00
		$firstDay->modify('first day of april 2013');
		for ($i = 0; $i < 30; $i++) {
			$this->assertEquals(TRUE, array_key_exists($firstDay->format('U'), $dayGenerator->getDayStorage()));
			$firstDay->modify('tomorrow');
		}
	}

}