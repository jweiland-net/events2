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
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class SearchTest extends UnitTestCase
{
    protected Search $subject;

    protected function setUp(): void
    {
        $this->subject = new Search();
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function getSearchInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getSearch(),
        );
    }

    /**
     * @test
     */
    public function setSearchSetsSearch(): void
    {
        $this->subject->setSearch('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getSearch(),
        );
    }

    /**
     * @test
     */
    public function getMainCategoryInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getMainCategory());
    }

    /**
     * @test
     */
    public function setMainCategorySetsMainCategory(): void
    {
        $instance = new Category();
        $this->subject->setMainCategory($instance);

        self::assertSame(
            $instance,
            $this->subject->getMainCategory(),
        );
    }

    /**
     * @test
     */
    public function getSubCategoryInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getSubCategory());
    }

    /**
     * @test
     */
    public function setSubCategorySetsSubCategory(): void
    {
        $instance = new Category();
        $this->subject->setSubCategory($instance);

        self::assertSame(
            $instance,
            $this->subject->getSubCategory(),
        );
    }

    /**
     * @test
     */
    public function getEventBeginInitiallyReturnsNULL(): void
    {
        self::assertNull($this->subject->getEventBegin());
    }

    /**
     * @test
     */
    public function setEventBeginSetsEventBegin(): void
    {
        $this->subject->setEventBegin('today');

        self::assertEquals(
            new \DateTimeImmutable('today'),
            $this->subject->getEventBegin(),
        );
    }

    /**
     * @test
     */
    public function getEventEndInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getEventEnd());
    }

    /**
     * @test
     */
    public function setEventEndSetsEventEnd(): void
    {
        $this->subject->setEventEnd('today');

        self::assertEquals(
            new \DateTimeImmutable('today'),
            $this->subject->getEventEnd(),
        );
    }

    /**
     * @test
     */
    public function getLocationInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getLocation());
    }

    /**
     * @test
     */
    public function setLocationSetsLocation(): void
    {
        $instance = new Location();
        $this->subject->setLocation($instance);

        self::assertSame(
            $instance,
            $this->subject->getLocation(),
        );
    }

    /**
     * @test
     */
    public function getFreeEntryInitiallyReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->getFreeEntry(),
        );
    }

    /**
     * @test
     */
    public function setFreeEntrySetsFreeEntry(): void
    {
        $this->subject->setFreeEntry(true);
        self::assertTrue(
            $this->subject->getFreeEntry(),
        );
    }
}
