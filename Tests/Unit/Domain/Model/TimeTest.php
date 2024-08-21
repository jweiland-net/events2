<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Domain\Model;

use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Tests\Unit\Domain\Traits\TestTypo3PropertiesTrait;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test case.
 */
class TimeTest extends UnitTestCase
{
    use TestTypo3PropertiesTrait;

    protected Time $subject;

    protected function setUp(): void
    {
        $this->subject = new Time();
    }

    protected function tearDown(): void
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getWeekdayInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getWeekday(),
        );
    }

    /**
     * @test
     */
    public function setWeekdaySetsWeekday(): void
    {
        $this->subject->setWeekday('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getWeekday(),
        );
    }

    /**
     * @test
     */
    public function getTimeBeginInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getTimeBegin(),
        );
    }

    /**
     * @test
     */
    public function setTimeBeginSetsTimeBegin(): void
    {
        $this->subject->setTimeBegin('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getTimeBegin(),
        );
    }

    /**
     * @test
     */
    public function getTimeEntryInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getTimeEntry(),
        );
    }

    /**
     * @test
     */
    public function setTimeEntrySetsTimeEntry(): void
    {
        $this->subject->setTimeEntry('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getTimeEntry(),
        );
    }

    /**
     * @test
     */
    public function getDurationInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getDuration(),
        );
    }

    /**
     * @test
     */
    public function setDurationSetsDuration(): void
    {
        $this->subject->setDuration('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getDuration(),
        );
    }

    /**
     * @test
     */
    public function getTimeEndInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getTimeEnd(),
        );
    }

    /**
     * @test
     */
    public function setTimeEndSetsTimeEnd(): void
    {
        $this->subject->setTimeEnd('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getTimeEnd(),
        );
    }

    /**
     * @test
     */
    public function getTimeEntryAsDateTimeInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getTimeEntryAsDateTime(),
        );
    }

    /**
     * @test
     */
    public function setTimeEntryAsDateTimeSetsTimeEntryAsDateTime(): void
    {
        $date = new \DateTimeImmutable();
        $this->subject->setTimeEntryAsDateTime($date);

        self::assertSame(
            $date,
            $this->subject->getTimeEntryAsDateTime(),
        );
    }

    /**
     * @test
     */
    public function getTimeBeginAsDateTimeInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getTimeBeginAsDateTime(),
        );
    }

    /**
     * @test
     */
    public function setTimeBeginAsDateTimeSetsTimeBeginAsDateTime(): void
    {
        $date = new \DateTimeImmutable();
        $this->subject->setTimeBeginAsDateTime($date);

        self::assertSame(
            $date,
            $this->subject->getTimeBeginAsDateTime(),
        );
    }

    /**
     * @test
     */
    public function getTimeEndAsDateTimeInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getTimeEndAsDateTime(),
        );
    }

    /**
     * @test
     */
    public function setTimeEndAsDateTimeSetsTimeEndAsDateTime(): void
    {
        $date = new \DateTimeImmutable();
        $this->subject->setTimeEndAsDateTime($date);

        self::assertSame(
            $date,
            $this->subject->getTimeEndAsDateTime(),
        );
    }
}
