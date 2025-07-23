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
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class TimeTest extends UnitTestCase
{
    use TestTypo3PropertiesTrait;

    protected Time $subject;

    protected function setUp(): void
    {
        parent::setUp();

        date_default_timezone_set('Europe/Berlin');

        $this->subject = new Time();
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function getWeekdayInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getWeekday(),
        );
    }

    #[Test]
    public function setWeekdaySetsWeekday(): void
    {
        $this->subject->setWeekday('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getWeekday(),
        );
    }

    #[Test]
    public function getTimeBeginInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getTimeBegin(),
        );
    }

    #[Test]
    public function setTimeBeginSetsTimeBegin(): void
    {
        $this->subject->setTimeBegin('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getTimeBegin(),
        );
    }

    #[Test]
    public function getTimeEntryInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getTimeEntry(),
        );
    }

    #[Test]
    public function setTimeEntrySetsTimeEntry(): void
    {
        $this->subject->setTimeEntry('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getTimeEntry(),
        );
    }

    #[Test]
    public function getDurationInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getDuration(),
        );
    }

    #[Test]
    public function setDurationSetsDuration(): void
    {
        $this->subject->setDuration('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getDuration(),
        );
    }

    #[Test]
    public function getTimeEndInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getTimeEnd(),
        );
    }

    #[Test]
    public function setTimeEndSetsTimeEnd(): void
    {
        $this->subject->setTimeEnd('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getTimeEnd(),
        );
    }

    #[Test]
    public function getTimeEntryAsDateTimeInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getTimeEntryAsDateTime(),
        );
    }

    #[Test]
    public function setTimeEntryAsDateTimeSetsTimeEntryAsDateTime(): void
    {
        $date = new \DateTimeImmutable();
        $this->subject->setTimeEntryAsDateTime($date);

        self::assertSame(
            $date,
            $this->subject->getTimeEntryAsDateTime(),
        );
    }

    #[Test]
    public function getTimeBeginAsDateTimeInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getTimeBeginAsDateTime(),
        );
    }

    #[Test]
    public function setTimeBeginAsDateTimeSetsTimeBeginAsDateTime(): void
    {
        $date = new \DateTimeImmutable();
        $this->subject->setTimeBeginAsDateTime($date);

        self::assertSame(
            $date,
            $this->subject->getTimeBeginAsDateTime(),
        );
    }

    #[Test]
    public function getTimeEndAsDateTimeInitiallyReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getTimeEndAsDateTime(),
        );
    }

    #[Test]
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
