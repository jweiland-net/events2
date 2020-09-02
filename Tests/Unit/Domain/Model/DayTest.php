<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Domain\Model;

use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test case.
 */
class DayTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Domain\Model\Day
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = new Day();
        $this->subject->setDay(new \DateTime());
    }

    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getCrdateInitiallyReturnsNull()
    {
        self::assertNull(
            $this->subject->getCrdate()
        );
    }

    /**
     * @test
     */
    public function setCrdateSetsCrdate()
    {
        $date = new \DateTime();
        $this->subject->setCrdate($date);

        self::assertSame(
            $date,
            $this->subject->getCrdate()
        );
    }

    /**
     * @test
     */
    public function setCrdateWithNullSetsCrdate()
    {
        $this->subject->setCrdate(null);

        self::assertNull(
            $this->subject->getCrdate()
        );
    }

    /**
     * @test
     */
    public function getTstampInitiallyReturnsNull()
    {
        self::assertNull(
            $this->subject->getTstamp()
        );
    }

    /**
     * @test
     */
    public function setTstampSetsTstamp()
    {
        $date = new \DateTime();
        $this->subject->setTstamp($date);

        self::assertSame(
            $date,
            $this->subject->getTstamp()
        );
    }

    /**
     * @test
     */
    public function setTstampWithNullSetsTstamp()
    {
        $this->subject->setTstamp(null);

        self::assertNull(
            $this->subject->getTstamp()
        );
    }

    /**
     * @test
     */
    public function getHiddenInitiallyReturnsFalse()
    {
        self::assertFalse(
            $this->subject->getHidden()
        );
    }

    /**
     * @test
     */
    public function setHiddenSetsHidden()
    {
        $this->subject->setHidden(true);
        self::assertTrue(
            $this->subject->getHidden()
        );
    }

    /**
     * @test
     */
    public function setHiddenWithStringReturnsTrue()
    {
        $this->subject->setHidden('foo bar');
        self::assertTrue($this->subject->getHidden());
    }

    /**
     * @test
     */
    public function setHiddenWithZeroReturnsFalse()
    {
        $this->subject->setHidden(0);
        self::assertFalse($this->subject->getHidden());
    }

    /**
     * @test
     */
    public function getCruserIdInitiallyReturnsZero()
    {
        self::assertSame(
            0,
            $this->subject->getCruserId()
        );
    }

    /**
     * @test
     */
    public function setCruserIdSetsCruserId()
    {
        $this->subject->setCruserId(123456);

        self::assertSame(
            123456,
            $this->subject->getCruserId()
        );
    }

    /**
     * @test
     */
    public function setCruserIdWithStringResultsInInteger()
    {
        $this->subject->setCruserId('123Test');

        self::assertSame(
            123,
            $this->subject->getCruserId()
        );
    }

    /**
     * @test
     */
    public function setCruserIdWithBooleanResultsInInteger()
    {
        $this->subject->setCruserId(true);

        self::assertSame(
            1,
            $this->subject->getCruserId()
        );
    }

    /**
     * @test
     */
    public function setDaySetsDay()
    {
        $date = new \DateTime();
        $this->subject->setDay($date);

        self::assertEquals(
            $date,
            $this->subject->getDay()
        );
    }

    /**
     * @test
     */
    public function getEventInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getEvent());
    }

    /**
     * @test
     */
    public function setEventSetsEvent()
    {
        $instance = new Event();
        $this->subject->setEvent($instance);

        self::assertSame(
            $instance,
            $this->subject->getEvent()
        );
    }

    /**
     * @test
     */
    public function getDayTimeAsTimestampWillReturnDayTimeAsInt()
    {
        $date = new \DateTime('today midnight');

        $this->subject->setDayTime($date);

        self::assertSame(
            (int)$date->format('U'),
            $this->subject->getDayTimeAsTimestamp()
        );
    }

    /**
     * @test
     */
    public function getSortDayTimeAsTimestampWillReturnSortDayTimeAsInt()
    {
        $date = new \DateTime('today midnight');

        $this->subject->setSortDayTime($date);

        self::assertSame(
            (int)$date->format('U'),
            $this->subject->getSortDayTimeAsTimestamp()
        );
    }

    /**
     * @test
     */
    public function getSameDayTimeAsTimestampWillReturnSameDayTimeAsInt()
    {
        $date = new \DateTime('today midnight');

        $this->subject->setSameDayTime($date);

        self::assertSame(
            (int)$date->format('U'),
            $this->subject->getSameDayTimeAsTimestamp()
        );
    }
}
