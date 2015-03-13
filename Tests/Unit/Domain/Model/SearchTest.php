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
use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Search;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case.
 *
 * @subpackage Events
 * @author Stefan Froemken <projects@jweiland.net>
 */
class SearchTest extends UnitTestCase {

	/**
	 * @var \JWeiland\Events2\Domain\Model\Search
	 */
	protected $subject;

	/**
	 * set up
	 *
	 * @return void
	 */
	public function setUp() {
		$this->subject = new Search();
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
	public function getSearchInitiallyReturnsEmptyString() {
		$this->assertSame(
			'',
			$this->subject->getSearch()
		);
	}

	/**
	 * @test
	 */
	public function setSearchSetsSearch() {
		$this->subject->setSearch('foo bar');

		$this->assertSame(
			'foo bar',
			$this->subject->getSearch()
		);
	}

	/**
	 * @test
	 */
	public function setSearchWithIntegerResultsInString() {
		$this->subject->setSearch(123);
		$this->assertSame('123', $this->subject->getSearch());
	}

	/**
	 * @test
	 */
	public function setSearchWithBooleanResultsInString() {
		$this->subject->setSearch(TRUE);
		$this->assertSame('1', $this->subject->getSearch());
	}

	/**
	 * @test
	 */
	public function getMainCategoryInitiallyReturnsNull() {
		$this->assertNull($this->subject->getMainCategory());
	}

	/**
	 * @test
	 */
	public function setMainCategorySetsMainCategory() {
		$instance = new Category();
		$this->subject->setMainCategory($instance);

		$this->assertSame(
			$instance,
			$this->subject->getMainCategory()
		);
	}

	/**
	 * @test
	 */
	public function getSubCategoryInitiallyReturnsNull() {
		$this->assertNull($this->subject->getSubCategory());
	}

	/**
	 * @test
	 */
	public function setSubCategorySetsSubCategory() {
		$instance = new Category();
		$this->subject->setSubCategory($instance);

		$this->assertSame(
			$instance,
			$this->subject->getSubCategory()
		);
	}

	/**
	 * @test
	 */
	public function getEventBeginInitiallyReturnsNULL() {
		$this->assertNull($this->subject->getEventBegin());
	}

	/**
	 * @test
	 */
	public function setEventBeginSetsEventBegin() {
		$this->subject->setEventBegin('today');

		$this->assertEquals(
			new \DateTime('today'),
			$this->subject->getEventBegin()
		);
	}

	/**
	 * @test
	 */
	public function getEventEndInitiallyReturnsNull() {
		$this->assertNull($this->subject->getEventEnd());
	}

	/**
	 * @test
	 */
	public function setEventEndSetsEventEnd() {
		$this->subject->setEventEnd('today');

		$this->assertEquals(
			new \DateTime('today'),
			$this->subject->getEventEnd()
		);
	}

	/**
	 * @test
	 */
	public function getLocationInitiallyReturnsNull() {
		$this->assertNull($this->subject->getLocation());
	}

	/**
	 * @test
	 */
	public function setLocationSetsLocation() {
		$instance = new Location();
		$this->subject->setLocation($instance);

		$this->assertSame(
			$instance,
			$this->subject->getLocation()
		);
	}

	/**
	 * @test
	 */
	public function getFreeEntryInitiallyReturnsFalse() {
		$this->assertSame(
			FALSE,
			$this->subject->getFreeEntry()
		);
	}

	/**
	 * @test
	 */
	public function setFreeEntrySetsFreeEntry() {
		$this->subject->setFreeEntry(TRUE);
		$this->assertSame(
			TRUE,
			$this->subject->getFreeEntry()
		);
	}

	/**
	 * @test
	 */
	public function setFreeEntryWithStringReturnsTrue() {
		$this->subject->setFreeEntry('foo bar');
		$this->assertTrue($this->subject->getFreeEntry());
	}

	/**
	 * @test
	 */
	public function setFreeEntryWithZeroReturnsFalse() {
		$this->subject->setFreeEntry(0);
		$this->assertFalse($this->subject->getFreeEntry());
	}


}