<?php

declare(strict_types=1);

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

    public function setUp(): void
    {
        $this->subject = new Exception();
    }

    public function tearDown(): void
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getExceptionTypeInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getExceptionType()
        );
    }

    /**
     * @test
     */
    public function setExceptionTypeSetsExceptionType(): void
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
    public function setExceptionDateSetsExceptionDate(): void
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
    public function getExceptionTimeInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getExceptionTime());
    }

    /**
     * @test
     */
    public function setExceptionTimeSetsExceptionTime(): void
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
    public function getExceptionDetailsInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getExceptionDetails()
        );
    }

    /**
     * @test
     */
    public function setExceptionDetailsSetsExceptionDetails(): void
    {
        $this->subject->setExceptionDetails('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getExceptionDetails()
        );
    }
}
