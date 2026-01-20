<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Domain\Model;

use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Search;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class SearchTest extends UnitTestCase
{
    protected Search $subject;

    protected function setUp(): void
    {
        parent::setUp();

        date_default_timezone_set(Events2Constants::PHP_TIMEZONE);

        $this->subject = new Search();
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function getSearchInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getSearch(),
        );
    }

    #[Test]
    public function setSearchSetsSearch(): void
    {
        $this->subject->setSearch('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getSearch(),
        );
    }

    #[Test]
    public function getMainCategoryInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getMainCategory());
    }

    #[Test]
    public function setMainCategorySetsMainCategory(): void
    {
        $instance = new Category();
        $this->subject->setMainCategory($instance);

        self::assertSame(
            $instance,
            $this->subject->getMainCategory(),
        );
    }

    #[Test]
    public function getSubCategoryInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getSubCategory());
    }

    #[Test]
    public function setSubCategorySetsSubCategory(): void
    {
        $instance = new Category();
        $this->subject->setSubCategory($instance);

        self::assertSame(
            $instance,
            $this->subject->getSubCategory(),
        );
    }

    #[Test]
    public function getEventBeginInitiallyReturnsNULL(): void
    {
        self::assertNull($this->subject->getEventBegin());
    }

    #[Test]
    public function setEventBeginSetsEventBegin(): void
    {
        $this->subject->setEventBegin('today');

        self::assertEquals(
            new \DateTimeImmutable('today'),
            $this->subject->getEventBegin(),
        );
    }

    #[Test]
    public function getEventEndInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getEventEnd());
    }

    #[Test]
    public function setEventEndSetsEventEnd(): void
    {
        $this->subject->setEventEnd('today');

        self::assertEquals(
            new \DateTimeImmutable('today'),
            $this->subject->getEventEnd(),
        );
    }

    #[Test]
    public function getLocationInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getLocation());
    }

    #[Test]
    public function setLocationSetsLocation(): void
    {
        $instance = new Location();
        $this->subject->setLocation($instance);

        self::assertSame(
            $instance,
            $this->subject->getLocation(),
        );
    }

    #[Test]
    public function getAttendanceModeInitiallyReturnsZero(): void
    {
        self::assertSame(
            0,
            $this->subject->getAttendanceMode(),
        );
    }

    #[Test]
    public function setAttendanceModeSetsAttendanceMode(): void
    {
        $this->subject->setAttendanceMode(2);

        self::assertSame(
            2,
            $this->subject->getAttendanceMode(),
        );
    }

    #[Test]
    public function getFreeEntryInitiallyReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->getFreeEntry(),
        );
    }

    #[Test]
    public function setFreeEntrySetsFreeEntry(): void
    {
        $this->subject->setFreeEntry(true);
        self::assertTrue(
            $this->subject->getFreeEntry(),
        );
    }
}
