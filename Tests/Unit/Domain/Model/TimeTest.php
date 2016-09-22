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
use JWeiland\Events2\Domain\Model\Time;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class TimeTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Domain\Model\Time
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = new Time();
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
    public function getWeekdayInitiallyReturnsEmptyString()
    {
        $this->assertSame(
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

        $this->assertSame(
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
        $this->assertSame('123', $this->subject->getWeekday());
    }

    /**
     * @test
     */
    public function setWeekdayWithBooleanResultsInString()
    {
        $this->subject->setWeekday(true);
        $this->assertSame('1', $this->subject->getWeekday());
    }

    /**
     * @test
     */
    public function getTimeBeginInitiallyReturnsEmptyString()
    {
        $this->assertSame(
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

        $this->assertSame(
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
        $this->assertSame('123', $this->subject->getTimeBegin());
    }

    /**
     * @test
     */
    public function setTimeBeginWithBooleanResultsInString()
    {
        $this->subject->setTimeBegin(true);
        $this->assertSame('1', $this->subject->getTimeBegin());
    }

    /**
     * @test
     */
    public function getTimeEntryInitiallyReturnsEmptyString()
    {
        $this->assertSame(
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

        $this->assertSame(
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
        $this->assertSame('123', $this->subject->getTimeEntry());
    }

    /**
     * @test
     */
    public function setTimeEntryWithBooleanResultsInString()
    {
        $this->subject->setTimeEntry(true);
        $this->assertSame('1', $this->subject->getTimeEntry());
    }

    /**
     * @test
     */
    public function getDurationInitiallyReturnsEmptyString()
    {
        $this->assertSame(
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

        $this->assertSame(
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
        $this->assertSame('123', $this->subject->getDuration());
    }

    /**
     * @test
     */
    public function setDurationWithBooleanResultsInString()
    {
        $this->subject->setDuration(true);
        $this->assertSame('1', $this->subject->getDuration());
    }

    /**
     * @test
     */
    public function getTimeEndInitiallyReturnsEmptyString()
    {
        $this->assertSame(
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

        $this->assertSame(
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
        $this->assertSame('123', $this->subject->getTimeEnd());
    }

    /**
     * @test
     */
    public function setTimeEndWithBooleanResultsInString()
    {
        $this->subject->setTimeEnd(true);
        $this->assertSame('1', $this->subject->getTimeEnd());
    }
}
