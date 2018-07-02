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
use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Domain\Model\Organizer;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class OrganizerTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Domain\Model\Organizer
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = new Organizer();
    }

    /**
     * tear down.
     */
    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getOrganizerInitiallyReturnsEmptyString()
    {
        $this->assertSame(
            '',
            $this->subject->getOrganizer()
        );
    }

    /**
     * @test
     */
    public function setOrganizerSetsOrganizer()
    {
        $this->subject->setOrganizer('foo bar');

        $this->assertSame(
            'foo bar',
            $this->subject->getOrganizer()
        );
    }

    /**
     * @test
     */
    public function setOrganizerWithIntegerResultsInString()
    {
        $this->subject->setOrganizer(123);
        $this->assertSame('123', $this->subject->getOrganizer());
    }

    /**
     * @test
     */
    public function setOrganizerWithBooleanResultsInString()
    {
        $this->subject->setOrganizer(true);
        $this->assertSame('1', $this->subject->getOrganizer());
    }

    /**
     * @test
     */
    public function getLinkInitiallyReturnsNull()
    {
        $this->assertNull($this->subject->getLink());
    }

    /**
     * @test
     */
    public function setLinkSetsLink()
    {
        $instance = new Link();
        $this->subject->setLink($instance);

        $this->assertSame(
            $instance,
            $this->subject->getLink()
        );
    }
}
