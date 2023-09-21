<?php

declare(strict_types=1);

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
    protected Day $subject;

    protected function setUp(): void
    {
        $this->subject = new Day();
        $this->subject->setDay(new \DateTimeImmutable());
    }

    protected function tearDown(): void
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getCrdateInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getCrdate()
        );
    }

    /**
     * @test
     */
    public function setCrdateSetsCrdate(): void
    {
        $date = new \DateTimeImmutable();
        $this->subject->setCrdate($date);

        self::assertSame(
            $date,
            $this->subject->getCrdate()
        );
    }

    /**
     * @test
     */
    public function setCrdateWithNullSetsCrdate(): void
    {
        $this->subject->setCrdate(null);

        self::assertNull(
            $this->subject->getCrdate()
        );
    }

    /**
     * @test
     */
    public function getTstampInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getTstamp()
        );
    }

    /**
     * @test
     */
    public function setTstampSetsTstamp(): void
    {
        $date = new \DateTimeImmutable();
        $this->subject->setTstamp($date);

        self::assertSame(
            $date,
            $this->subject->getTstamp()
        );
    }

    /**
     * @test
     */
    public function setTstampWithNullSetsTstamp(): void
    {
        $this->subject->setTstamp(null);

        self::assertNull(
            $this->subject->getTstamp()
        );
    }

    /**
     * @test
     */
    public function getHiddenInitiallyReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->getHidden()
        );
    }

    /**
     * @test
     */
    public function setHiddenSetsHidden(): void
    {
        $this->subject->setHidden(true);
        self::assertTrue(
            $this->subject->getHidden()
        );
    }

    /**
     * @test
     */
    public function setDaySetsDay(): void
    {
        $date = new \DateTimeImmutable();
        $this->subject->setDay($date);

        self::assertEquals(
            $date,
            $this->subject->getDay()
        );
    }

    /**
     * @test
     */
    public function setEventSetsEvent(): void
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
    public function getDayTimeAsTimestampWillReturnDayTimeAsInt(): void
    {
        $date = new \DateTimeImmutable('today midnight');

        $this->subject->setDayTime($date);

        self::assertSame(
            (int)$date->format('U'),
            $this->subject->getDayTimeAsTimestamp()
        );
    }

    /**
     * @test
     */
    public function getSortDayTimeAsTimestampWillReturnSortDayTimeAsInt(): void
    {
        $date = new \DateTimeImmutable('today midnight');

        $this->subject->setSortDayTime($date);

        self::assertSame(
            (int)$date->format('U'),
            $this->subject->getSortDayTimeAsTimestamp()
        );
    }

    /**
     * @test
     */
    public function getSameDayTimeAsTimestampWillReturnSameDayTimeAsInt(): void
    {
        $date = new \DateTimeImmutable('today midnight');

        $this->subject->setSameDayTime($date);

        self::assertSame(
            (int)$date->format('U'),
            $this->subject->getSameDayTimeAsTimestamp()
        );
    }
}
