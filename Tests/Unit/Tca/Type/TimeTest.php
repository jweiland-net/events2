<?php
namespace JWeiland\Events2\Tests\Unit\Tca\Type;

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
use TYPO3\CMS\Core\Tests\UnitTestCase;
use JWeiland\Events2\Tca\Type\Time;

/**
 * Test case.
 *
 * @subpackage events2
 * @author Stefan Froemken <projects@jweiland.net>
 */
class TimeTest extends UnitTestCase {

	/**
	 * @var \JWeiland\Events2\Tca\Type\Time
	 */
	protected $subject;





	public function setUp() {
		$this->subject = new Time();
	}

	public function tearDown() {
		unset($this->subject);
	}


	/**
	 * dataprovider for times with times which will not be modified
	 *
	 * @return array
	 */
	public function unmodifiedTimesDataProvider() {
		$times = array();
		$times['empty values'] = array('');
		$times['erliest two digit time'] = array('00:00');
		$times['latest two digit time'] = array('23:59');
		$times['random two digit time'] = array('21:34');

		return $times;
	}

	/**
	 * @test
	 *
	 * @param $unmodifiedTime
	 * @dataProvider unmodifiedTimesDataProvider
	 */
	public function evaluateWithTimesWhichWillNotBeModified($unmodifiedTime) {
		$this->assertSame(
			$unmodifiedTime,
			$this->subject->evaluateFieldValue($unmodifiedTime)
		);
	}

	/**
	 * @return array
	 */
	public function unpaddedTimesDataProvider() {
		$times = array();
		$times['zero values'] = array('0:0', '00:00');
		$times['one digit hour'] = array('5:32', '05:32');
		$times['one digit minute'] = array('12:8', '12:08');
		$times['one digit for both'] = array('4:9', '04:09');

		return $times;
	}

	/**
	 * @test
	 *
	 * @param $unpaddedTimes
	 * @param $paddedTimes
	 * @dataProvider unpaddedTimesDataProvider
	 */
	public function evaluateWithTimesWhichWillAddPaddings($unpaddedTimes, $paddedTimes) {
		$this->assertSame(
			$paddedTimes,
			$this->subject->evaluateFieldValue($unpaddedTimes)
		);
	}

	/**
	 * @return array
	 */
	public function tooHighTimesDataProvider() {
		$times = array();
		$times['edge case midnight'] = array('24:00', '24:00');
		$times['edge case one minute after midnight'] = array('24:01', '23:01');
		$times['edge case with 60 minutes'] = array('15:60', '15:59');
		$times['too high hour'] = array('67:43', '23:43');
		$times['too high minute'] = array('19:543', '19:59');
		$times['everything is too high'] = array('63:754', '23:59');

		return $times;
	}

	/**
	 * @test
	 *
	 * @param string $tooHighTime
	 * @param string $normalizedTime
	 * @dataProvider tooHighTimesDataProvider
	 */
	public function evaluateWithTooHighTimeValues($tooHighTime, $normalizedTime) {
		$this->assertSame(
			$normalizedTime,
			$this->subject->evaluateFieldValue($tooHighTime)
		);
	}

	/**
	 * @return array
	 */
	public function invalidTimesDataProvider() {
		$times = array();
		$times['invalid value: Stefan'] = array('Stefan', '');
		$times['invalid value: 123Test'] = array('123Test', '');

		return $times;
	}

	/**
	 * @test
	 *
	 * @param string $invalidTime
	 * @param string $expectedTime
	 * @dataProvider invalidTimesDataProvider
	 */
	public function evaluateWithInvalidValues($invalidTime, $expectedTime) {
		$this->assertSame(
			$expectedTime,
			$this->subject->evaluateFieldValue($invalidTime)
		);
	}

	/**
	 * @return array
	 */
	public function dateProviderForVariousIntegerValues() {
		$timestamps = array();
		$timestamps['edge case with zero'] = array(0, '00:00');
		$timestamps['edge case with one second'] = array(1, '00:00');
		$timestamps['edge case with one minute'] = array(60, '00:01');
		$timestamps['edge case with midnight'] = array(60 * 60 * 24, '23:59');
		$timestamps['edge case with midnight - 1 second'] = array((60 * 60 * 24) - 1, '23:59');
		$timestamps['timestamp with padded zero in front'] = array(60 * 60 * 7 + 60 * 3, '07:03');
		$timestamps['timestamp in the morning'] = array(60 * 60 * 8 + 60 * 27, '08:27');
		$timestamps['timestamp at launch'] = array(60 * 60 * 12 + 60 * 12, '12:12');
		$timestamps['timestamp in the afternoon'] = array(60 * 60 * 19 + 60 * 13, '19:13');

		return $timestamps;
	}

	/**
	 * @test
	 *
	 * @param string $intValue
	 * @param string $expectedTime
	 * @dataProvider dateProviderForVariousIntegerValues
	 */
	public function evaluateWithInteger($intValue, $expectedTime) {
		$this->assertSame(
			$expectedTime,
			$this->subject->evaluateFieldValue($intValue)
		);
	}

}