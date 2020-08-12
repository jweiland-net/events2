<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Domain\Model;

use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Tests\Unit\Domain\Traits\TestTypo3PropertiesTrait;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test case.
 */
class ExceptionTest extends UnitTestCase
{
    use TestTypo3PropertiesTrait;

    /**
     * @var \JWeiland\Events2\Domain\Model\Exception
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = new Exception();
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
    public function getExceptionTypeInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getExceptionType()
        );
    }

    /**
     * @test
     */
    public function setExceptionTypeSetsExceptionType()
    {
        $this->subject->setExceptionType('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getExceptionType()
        );
    }

    /**
     * @test
     */
    public function setExceptionTypeWithIntegerResultsInString()
    {
        $this->subject->setExceptionType(123);
        self::assertSame('123', $this->subject->getExceptionType());
    }

    /**
     * @test
     */
    public function setExceptionTypeWithBooleanResultsInString()
    {
        $this->subject->setExceptionType(true);
        self::assertSame('1', $this->subject->getExceptionType());
    }

    /**
     * @test
     */
    public function setExceptionDateSetsExceptionDate()
    {
        $date = new \DateTime();
        $this->subject->setExceptionDate($date);

        self::assertEquals(
            $date,
            $this->subject->getExceptionDate()
        );
    }

    /**
     * @test
     */
    public function getExceptionTimeInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getExceptionTime());
    }

    /**
     * @test
     */
    public function setExceptionTimeSetsExceptionTime()
    {
        $instance = new Time();
        $this->subject->setExceptionTime($instance);

        self::assertSame(
            $instance,
            $this->subject->getExceptionTime()
        );
    }

    /**
     * @test
     */
    public function getExceptionDetailsInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getExceptionDetails()
        );
    }

    /**
     * @test
     */
    public function setExceptionDetailsSetsExceptionDetails()
    {
        $this->subject->setExceptionDetails('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getExceptionDetails()
        );
    }

    /**
     * @test
     */
    public function setExceptionDetailsWithIntegerResultsInString()
    {
        $this->subject->setExceptionDetails(123);
        self::assertSame('123', $this->subject->getExceptionDetails());
    }

    /**
     * @test
     */
    public function setExceptionDetailsWithBooleanResultsInString()
    {
        $this->subject->setExceptionDetails(true);
        self::assertSame('1', $this->subject->getExceptionDetails());
    }
}
