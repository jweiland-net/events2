<?php
namespace JWeiland\Events2\Tests\Unit\Domain\Model;

/*
 * This file is part of the events2 project.
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

use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Model\Organizer;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test case.
 */
class FilterTest extends UnitTestCase
{
    /**
     * @var Filter
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = new Filter();
    }

    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getOrganizerInitiallyReturnsNull()
    {
        $this->assertNull($this->subject->getOrganizer());
    }

    /**
     * @test
     */
    public function setOrganizerSetsOrganizer()
    {
        $organizer = 34;
        $this->subject->setOrganizer($organizer);

        $this->assertSame(
            $organizer,
            $this->subject->getOrganizer()
        );
    }

    /**
     * @test
     */
    public function setOrganizerWithNullSetsOrganizer()
    {
        $this->subject->setOrganizer(null);

        $this->assertNull(
            $this->subject->getOrganizer()
        );
    }
}
