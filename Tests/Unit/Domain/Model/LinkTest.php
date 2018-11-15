<?php

namespace JWeiland\Events2\Tests\Unit\Domain\Model;

/*
 * This file is part of the events2 project.
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
use JWeiland\Events2\Domain\Model\Link;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test case.
 */
class LinkTest extends UnitTestCase
{
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
        $this->assertSame(
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

        $this->assertSame(
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
        $this->assertSame('123', $this->subject->getLink());
    }

    /**
     * @test
     */
    public function setLinkWithBooleanResultsInString()
    {
        $this->subject->setLink(true);
        $this->assertSame('1', $this->subject->getLink());
    }

    /**
     * @test
     */
    public function getTitleInitiallyReturnsVideo()
    {
        $this->assertSame(
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

        $this->assertSame(
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
        $this->assertSame('123', $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function setTitleWithBooleanResultsInString()
    {
        $this->subject->setTitle(true);
        $this->assertSame('1', $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function getDeletedInitiallyReturnsFalse()
    {
        $this->assertSame(
            false,
            $this->subject->getDeleted()
        );
    }

    /**
     * @test
     */
    public function setDeletedSetsDeleted()
    {
        $this->subject->setDeleted(true);
        $this->assertSame(
            true,
            $this->subject->getDeleted()
        );
    }

    /**
     * @test
     */
    public function setDeletedWithStringReturnsTrue()
    {
        $this->subject->setDeleted('foo bar');
        $this->assertTrue($this->subject->getDeleted());
    }

    /**
     * @test
     */
    public function setDeletedWithZeroReturnsFalse()
    {
        $this->subject->setDeleted(0);
        $this->assertFalse($this->subject->getDeleted());
    }
}
