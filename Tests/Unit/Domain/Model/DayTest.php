<?php

namespace JWeiland\Events2\Tests\Unit\Domain\Model;

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
use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class DayTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Domain\Model\Day
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = new Day();
        $this->subject->setDay(new \DateTime());
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
    public function getDayInitiallyReturnsNull()
    {
        $day = new Day();
        $this->assertNull(
            $day->getDay()
        );
    }

    /**
     * @test
     */
    public function setDaySetsDay()
    {
        $date = new \DateTime();
        $this->subject->setDay($date);

        $this->assertSame(
            $date,
            $this->subject->getDay()
        );
    }

    /**
     * @test
     */
    public function getEventInitiallyReturnsNull()
    {
        $this->assertNull($this->subject->getEvent());
    }

    /**
     * @test
     */
    public function setEventSetsEvent()
    {
        $instance = new Event();
        $this->subject->setEvent($instance);

        $this->assertSame(
            $instance,
            $this->subject->getEvent()
        );
    }
}
