<?php
namespace JWeiland\Events2\Tests\Unit\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Stefan Froemken <projects@jweiland.net>, jweiland.net
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
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Maps2\Domain\Model\PoiCollection;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case.
 *
 * @subpackage Events
 * @author Stefan Froemken <projects@jweiland.net>
 */
class LocationTest extends UnitTestCase {

	/**
	 * @var \JWeiland\Events2\Domain\Model\Location
	 */
	protected $subject;

	/**
	 * set up
	 *
	 * @return void
	 */
	public function setUp() {
		$this->subject = new Location();
	}

	/**
	 * tear down
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->subject);
	}

	/**
	 * @test
	 */
	public function getLocationInitiallyReturnsEmptyString() {
		$this->assertSame(
			'',
			$this->subject->getLocation()
		);
	}

	/**
	 * @test
	 */
	public function setLocationSetsLocation() {
		$this->subject->setLocation('foo bar');

		$this->assertSame(
			'foo bar',
			$this->subject->getLocation()
		);
	}

	/**
	 * @test
	 */
	public function setLocationWithIntegerResultsInString() {
		$this->subject->setLocation(123);
		$this->assertSame('123', $this->subject->getLocation());
	}

	/**
	 * @test
	 */
	public function setLocationWithBooleanResultsInString() {
		$this->subject->setLocation(TRUE);
		$this->assertSame('1', $this->subject->getLocation());
	}

	/**
	 * @test
	 */
	public function getStreetInitiallyReturnsEmptyString() {
		$this->assertSame(
			'',
			$this->subject->getStreet()
		);
	}

	/**
	 * @test
	 */
	public function setStreetSetsStreet() {
		$this->subject->setStreet('foo bar');

		$this->assertSame(
			'foo bar',
			$this->subject->getStreet()
		);
	}

	/**
	 * @test
	 */
	public function setStreetWithIntegerResultsInString() {
		$this->subject->setStreet(123);
		$this->assertSame('123', $this->subject->getStreet());
	}

	/**
	 * @test
	 */
	public function setStreetWithBooleanResultsInString() {
		$this->subject->setStreet(TRUE);
		$this->assertSame('1', $this->subject->getStreet());
	}

	/**
	 * @test
	 */
	public function getHouseNumberInitiallyReturnsEmptyString() {
		$this->assertSame(
			'',
			$this->subject->getHouseNumber()
		);
	}

	/**
	 * @test
	 */
	public function setHouseNumberSetsHouseNumber() {
		$this->subject->setHouseNumber('foo bar');

		$this->assertSame(
			'foo bar',
			$this->subject->getHouseNumber()
		);
	}

	/**
	 * @test
	 */
	public function setHouseNumberWithIntegerResultsInString() {
		$this->subject->setHouseNumber(123);
		$this->assertSame('123', $this->subject->getHouseNumber());
	}

	/**
	 * @test
	 */
	public function setHouseNumberWithBooleanResultsInString() {
		$this->subject->setHouseNumber(TRUE);
		$this->assertSame('1', $this->subject->getHouseNumber());
	}

	/**
	 * @test
	 */
	public function getZipInitiallyReturnsEmptyString() {
		$this->assertSame(
			'',
			$this->subject->getZip()
		);
	}

	/**
	 * @test
	 */
	public function setZipSetsZip() {
		$this->subject->setZip('foo bar');

		$this->assertSame(
			'foo bar',
			$this->subject->getZip()
		);
	}

	/**
	 * @test
	 */
	public function setZipWithIntegerResultsInString() {
		$this->subject->setZip(123);
		$this->assertSame('123', $this->subject->getZip());
	}

	/**
	 * @test
	 */
	public function setZipWithBooleanResultsInString() {
		$this->subject->setZip(TRUE);
		$this->assertSame('1', $this->subject->getZip());
	}

	/**
	 * @test
	 */
	public function getCityInitiallyReturnsEmptyString() {
		$this->assertSame(
			'',
			$this->subject->getCity()
		);
	}

	/**
	 * @test
	 */
	public function setCitySetsCity() {
		$this->subject->setCity('foo bar');

		$this->assertSame(
			'foo bar',
			$this->subject->getCity()
		);
	}

	/**
	 * @test
	 */
	public function setCityWithIntegerResultsInString() {
		$this->subject->setCity(123);
		$this->assertSame('123', $this->subject->getCity());
	}

	/**
	 * @test
	 */
	public function setCityWithBooleanResultsInString() {
		$this->subject->setCity(TRUE);
		$this->assertSame('1', $this->subject->getCity());
	}

	/**
	 * @test
	 */
	public function getTxMaps2UidInitiallyReturnsNull() {
		$this->assertNull($this->subject->getTxMaps2Uid());
	}

	/**
	 * @test
	 */
	public function setTxMaps2UidSetsTxMaps2Uid() {
		$instance = new PoiCollection();
		$this->subject->setTxMaps2Uid($instance);

		$this->assertSame(
			$instance,
			$this->subject->getTxMaps2Uid()
		);
	}

}