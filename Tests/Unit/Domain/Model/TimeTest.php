<?php

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

    /**
     * @var \JWeiland\Events2\Domain\Model\Time
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = new Time();
    }

    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getWeekdayInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getWeekday()
        );
    }

    /**
     * @test
     */
    public function setWeekdaySetsWeekday()
    {
        $this->subject->setWeekday('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getWeekday()
        );
    }

    /**
     * @test
     */
    public function setWeekdayWithIntegerResultsInString()
    {
        $this->subject->setWeekday(123);
        self::assertSame('123', $this->subject->getWeekday());
    }

    /**
     * @test
     */
    public function setWeekdayWithBooleanResultsInString()
    {
        $this->subject->setWeekday(true);
        self::assertSame('1', $this->subject->getWeekday());
    }

    /**
     * @test
     */
    public function getTimeBeginInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getTimeBegin()
        );
    }

    /**
     * @test
     */
    public function setTimeBeginSetsTimeBegin()
    {
        $this->subject->setTimeBegin('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getTimeBegin()
        );
    }

    /**
     * @test
     */
    public function setTimeBeginWithIntegerResultsInString()
    {
        $this->subject->setTimeBegin(123);
        self::assertSame('123', $this->subject->getTimeBegin());
    }

    /**
     * @test
     */
    public function setTimeBeginWithBooleanResultsInString()
    {
        $this->subject->setTimeBegin(true);
        self::assertSame('1', $this->subject->getTimeBegin());
    }

    /**
     * @test
     */
    public function getTimeEntryInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getTimeEntry()
        );
    }

    /**
     * @test
     */
    public function setTimeEntrySetsTimeEntry()
    {
        $this->subject->setTimeEntry('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getTimeEntry()
        );
    }

    /**
     * @test
     */
    public function setTimeEntryWithIntegerResultsInString()
    {
        $this->subject->setTimeEntry(123);
        self::assertSame('123', $this->subject->getTimeEntry());
    }

    /**
     * @test
     */
    public function setTimeEntryWithBooleanResultsInString()
    {
        $this->subject->setTimeEntry(true);
        self::assertSame('1', $this->subject->getTimeEntry());
    }

    /**
     * @test
     */
    public function getDurationInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getDuration()
        );
    }

    /**
     * @test
     */
    public function setDurationSetsDuration()
    {
        $this->subject->setDuration('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getDuration()
        );
    }

    /**
     * @test
     */
    public function setDurationWithIntegerResultsInString()
    {
        $this->subject->setDuration(123);
        self::assertSame('123', $this->subject->getDuration());
    }

    /**
     * @test
     */
    public function setDurationWithBooleanResultsInString()
    {
        $this->subject->setDuration(true);
        self::assertSame('1', $this->subject->getDuration());
    }

    /**
     * @test
     */
    public function getTimeEndInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getTimeEnd()
        );
    }

    /**
     * @test
     */
    public function setTimeEndSetsTimeEnd()
    {
        $this->subject->setTimeEnd('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getTimeEnd()
        );
    }

    /**
     * @test
     */
    public function setTimeEndWithIntegerResultsInString()
    {
        $this->subject->setTimeEnd(123);
        self::assertSame('123', $this->subject->getTimeEnd());
    }

    /**
     * @test
     */
    public function setTimeEndWithBooleanResultsInString()
    {
        $this->subject->setTimeEnd(true);
        self::assertSame('1', $this->subject->getTimeEnd());
    }

    /**
     * @test
     */
    public function getTimeEntryAsDateTimeInitiallyReturnsNull()
    {
        self::assertNull(
            $this->subject->getTimeEntryAsDateTime()
        );
    }

    /**
     * @test
     */
    public function setTimeEntryAsDateTimeSetsTimeEntryAsDateTime()
    {
        $date = new \DateTime();
        $this->subject->setTimeEntryAsDateTime($date);

        self::assertSame(
            $date,
            $this->subject->getTimeEntryAsDateTime()
        );
    }

    /**
     * @test
     */
    public function getTimeBeginAsDateTimeInitiallyReturnsNull()
    {
        self::assertNull(
            $this->subject->getTimeBeginAsDateTime()
        );
    }

    /**
     * @test
     */
    public function setTimeBeginAsDateTimeSetsTimeBeginAsDateTime()
    {
        $date = new \DateTime();
        $this->subject->setTimeBeginAsDateTime($date);

        self::assertSame(
            $date,
            $this->subject->getTimeBeginAsDateTime()
        );
    }

    /**
     * @test
     */
    public function getTimeEndAsDateTimeInitiallyReturnsNull()
    {
        self::assertNull(
            $this->subject->getTimeEndAsDateTime()
        );
    }

    /**
     * @test
     */
    public function setTimeEndAsDateTimeSetsTimeEndAsDateTime()
    {
        $date = new \DateTime();
        $this->subject->setTimeEndAsDateTime($date);

        self::assertSame(
            $date,
            $this->subject->getTimeEndAsDateTime()
        );
    }
}
