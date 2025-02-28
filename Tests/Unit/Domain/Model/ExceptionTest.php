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
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class ExceptionTest extends UnitTestCase
{
    use TestTypo3PropertiesTrait;

    protected Exception $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new Exception();
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function getExceptionTypeInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getExceptionType(),
        );
    }

    #[Test]
    public function setExceptionTypeSetsExceptionType(): void
    {
        $this->subject->setExceptionType('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getExceptionType(),
        );
    }

    #[Test]
    public function setExceptionDateSetsExceptionDate(): void
    {
        $date = new \DateTimeImmutable();
        $this->subject->setExceptionDate($date);

        self::assertEquals(
            $date,
            $this->subject->getExceptionDate(),
        );
    }

    #[Test]
    public function getExceptionTimeInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getExceptionTime());
    }

    #[Test]
    public function setExceptionTimeSetsExceptionTime(): void
    {
        $instance = new Time();
        $this->subject->setExceptionTime($instance);

        self::assertSame(
            $instance,
            $this->subject->getExceptionTime(),
        );
    }

    #[Test]
    public function getExceptionDetailsInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getExceptionDetails(),
        );
    }

    #[Test]
    public function setExceptionDetailsSetsExceptionDetails(): void
    {
        $this->subject->setExceptionDetails('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getExceptionDetails(),
        );
    }
}
