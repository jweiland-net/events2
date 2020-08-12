<?php

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
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test case.
 */
class SearchTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Domain\Model\Search
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = new Search();
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
    public function getSearchInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getSearch()
        );
    }

    /**
     * @test
     */
    public function setSearchSetsSearch()
    {
        $this->subject->setSearch('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getSearch()
        );
    }

    /**
     * @test
     */
    public function setSearchWithIntegerResultsInString()
    {
        $this->subject->setSearch(123);
        self::assertSame('123', $this->subject->getSearch());
    }

    /**
     * @test
     */
    public function setSearchWithBooleanResultsInString()
    {
        $this->subject->setSearch(true);
        self::assertSame('1', $this->subject->getSearch());
    }

    /**
     * @test
     */
    public function getMainCategoryInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getMainCategory());
    }

    /**
     * @test
     */
    public function setMainCategorySetsMainCategory()
    {
        $instance = new Category();
        $this->subject->setMainCategory($instance);

        self::assertSame(
            $instance,
            $this->subject->getMainCategory()
        );
    }

    /**
     * @test
     */
    public function getSubCategoryInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getSubCategory());
    }

    /**
     * @test
     */
    public function setSubCategorySetsSubCategory()
    {
        $instance = new Category();
        $this->subject->setSubCategory($instance);

        self::assertSame(
            $instance,
            $this->subject->getSubCategory()
        );
    }

    /**
     * @test
     */
    public function getEventBeginInitiallyReturnsNULL()
    {
        self::assertNull($this->subject->getEventBegin());
    }

    /**
     * @test
     */
    public function setEventBeginSetsEventBegin()
    {
        $this->subject->setEventBegin('today');

        self::assertEquals(
            new \DateTime('today'),
            $this->subject->getEventBegin()
        );
    }

    /**
     * @test
     */
    public function getEventEndInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getEventEnd());
    }

    /**
     * @test
     */
    public function setEventEndSetsEventEnd()
    {
        $this->subject->setEventEnd('today');

        self::assertEquals(
            new \DateTime('today'),
            $this->subject->getEventEnd()
        );
    }

    /**
     * @test
     */
    public function getLocationInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getLocation());
    }

    /**
     * @test
     */
    public function setLocationSetsLocation()
    {
        $instance = new Location();
        $this->subject->setLocation($instance);

        self::assertSame(
            $instance,
            $this->subject->getLocation()
        );
    }

    /**
     * @test
     */
    public function getFreeEntryInitiallyReturnsFalse()
    {
        self::assertSame(
            false,
            $this->subject->getFreeEntry()
        );
    }

    /**
     * @test
     */
    public function setFreeEntrySetsFreeEntry()
    {
        $this->subject->setFreeEntry(true);
        self::assertSame(
            true,
            $this->subject->getFreeEntry()
        );
    }

    /**
     * @test
     */
    public function setFreeEntryWithStringReturnsTrue()
    {
        $this->subject->setFreeEntry('foo bar');
        self::assertTrue($this->subject->getFreeEntry());
    }

    /**
     * @test
     */
    public function setFreeEntryWithZeroReturnsFalse()
    {
        $this->subject->setFreeEntry(0);
        self::assertFalse($this->subject->getFreeEntry());
    }
}
