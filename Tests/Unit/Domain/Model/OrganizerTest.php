<?php
namespace JWeiland\Events2\Tests\Unit\Domain\Model;

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
use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Domain\Model\Organizer;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case.
 *
 * @subpackage Events
 * @author Stefan Froemken <projects@jweiland.net>
 */
class OrganizerTest extends UnitTestCase {

	/**
	 * @var \JWeiland\Events2\Domain\Model\Organizer
	 */
	protected $subject;

	/**
	 * set up
	 *
	 * @return void
	 */
	public function setUp() {
		$this->subject = new Organizer();
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
	public function getOrganizerInitiallyReturnsEmptyString() {
		$this->assertSame(
			'',
			$this->subject->getOrganizer()
		);
	}

	/**
	 * @test
	 */
	public function setOrganizerSetsOrganizer() {
		$this->subject->setOrganizer('foo bar');

		$this->assertSame(
			'foo bar',
			$this->subject->getOrganizer()
		);
	}

	/**
	 * @test
	 */
	public function setOrganizerWithIntegerResultsInString() {
		$this->subject->setOrganizer(123);
		$this->assertSame('123', $this->subject->getOrganizer());
	}

	/**
	 * @test
	 */
	public function setOrganizerWithBooleanResultsInString() {
		$this->subject->setOrganizer(TRUE);
		$this->assertSame('1', $this->subject->getOrganizer());
	}

	/**
	 * @test
	 */
	public function getLinkInitiallyReturnsNull() {
		$this->assertNull($this->subject->getLink());
	}

	/**
	 * @test
	 */
	public function setLinkSetsLink() {
		$instance = new Link();
		$this->subject->setLink($instance);

		$this->assertSame(
			$instance,
			$this->subject->getLink()
		);
	}

}