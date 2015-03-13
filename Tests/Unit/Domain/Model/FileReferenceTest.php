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
use JWeiland\Events2\Domain\Model\FileReference;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case.
 *
 * @subpackage Events
 * @author Stefan Froemken <projects@jweiland.net>
 */
class FileReferenceTest extends UnitTestCase {

	/**
	 * @var \JWeiland\Events2\Domain\Model\FileReference
	 */
	protected $subject;

	/**
	 * set up
	 *
	 * @return void
	 */
	public function setUp() {
		$this->subject = new FileReference();
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
	public function getTitleInitiallyReturnsEmptyString() {
		$this->assertSame(
			'',
			$this->subject->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setTitleSetsTitle() {
		$this->subject->setTitle('foo bar');

		$this->assertSame(
			'foo bar',
			$this->subject->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setTitleWithIntegerResultsInString() {
		$this->subject->setTitle(123);
		$this->assertSame('123', $this->subject->getTitle());
	}

	/**
	 * @test
	 */
	public function setTitleWithBooleanResultsInString() {
		$this->subject->setTitle(TRUE);
		$this->assertSame('1', $this->subject->getTitle());
	}

	/**
	 * @test
	 */
	public function getCruserIdInitiallyReturnsZero() {
		$this->assertSame(
			0,
			$this->subject->getCruserId()
		);
	}

	/**
	 * @test
	 */
	public function setCruserIdSetsCruserId() {
		$this->subject->setCruserId(123456);

		$this->assertSame(
			123456,
			$this->subject->getCruserId()
		);
	}

	/**
	 * @test
	 */
	public function setCruserIdWithStringResultsInInteger() {
		$this->subject->setCruserId('123Test');

		$this->assertSame(
			123,
			$this->subject->getCruserId()
		);
	}

	/**
	 * @test
	 */
	public function setCruserIdWithBooleanResultsInInteger() {
		$this->subject->setCruserId(TRUE);

		$this->assertSame(
			1,
			$this->subject->getCruserId()
		);
	}

	/**
	 * @test
	 */
	public function getUidLocalInitiallyReturnsZero() {
		$this->assertSame(
			0,
			$this->subject->getUidLocal()
		);
	}

	/**
	 * @test
	 */
	public function setUidLocalSetsUidLocal() {
		$this->subject->setUidLocal(123456);

		$this->assertSame(
			123456,
			$this->subject->getUidLocal()
		);
	}

	/**
	 * @test
	 */
	public function getTablenamesInitiallyReturnsEmptyString() {
		$this->assertSame(
			'',
			$this->subject->getTablenames()
		);
	}

	/**
	 * @test
	 */
	public function setTablenamesSetsTablenames() {
		$this->subject->setTablenames('foo bar');

		$this->assertSame(
			'foo bar',
			$this->subject->getTablenames()
		);
	}

	/**
	 * @test
	 */
	public function setTablenamesWithIntegerResultsInString() {
		$this->subject->setTablenames(123);
		$this->assertSame('123', $this->subject->getTablenames());
	}

	/**
	 * @test
	 */
	public function setTablenamesWithBooleanResultsInString() {
		$this->subject->setTablenames(TRUE);
		$this->assertSame('1', $this->subject->getTablenames());
	}

}