<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Domain\Model;

use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Tests\Unit\Domain\Traits\TestTypo3PropertiesTrait;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test case.
 */
class LinkTest extends UnitTestCase
{
    use TestTypo3PropertiesTrait;

    /**
     * @var \JWeiland\Events2\Domain\Model\Link
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = new Link();
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
    public function getLinkInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getLink()
        );
    }

    /**
     * @test
     */
    public function setLinkSetsLink()
    {
        $this->subject->setLink('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getLink()
        );
    }

    /**
     * @test
     */
    public function setLinkWithIntegerResultsInString()
    {
        $this->subject->setLink(123);
        self::assertSame('123', $this->subject->getLink());
    }

    /**
     * @test
     */
    public function setLinkWithBooleanResultsInString()
    {
        $this->subject->setLink(true);
        self::assertSame('1', $this->subject->getLink());
    }

    /**
     * @test
     */
    public function getTitleInitiallyReturnsVideo()
    {
        self::assertSame(
            'Video',
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $this->subject->setTitle('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     */
    public function setTitleWithIntegerResultsInString()
    {
        $this->subject->setTitle(123);
        self::assertSame('123', $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function setTitleWithBooleanResultsInString()
    {
        $this->subject->setTitle(true);
        self::assertSame('1', $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function getDeletedInitiallyReturnsFalse()
    {
        self::assertFalse(
            $this->subject->getDeleted()
        );
    }

    /**
     * @test
     */
    public function setDeletedSetsDeleted()
    {
        $this->subject->setDeleted(true);
        self::assertTrue(
            $this->subject->getDeleted()
        );
    }

    /**
     * @test
     */
    public function setDeletedWithStringReturnsTrue()
    {
        $this->subject->setDeleted('foo bar');
        self::assertTrue($this->subject->getDeleted());
    }

    /**
     * @test
     */
    public function setDeletedWithZeroReturnsFalse()
    {
        $this->subject->setDeleted(0);
        self::assertFalse($this->subject->getDeleted());
    }
}
