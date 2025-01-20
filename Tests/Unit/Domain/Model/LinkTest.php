<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Domain\Model;

use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Tests\Unit\Domain\Traits\TestTypo3PropertiesTrait;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class LinkTest extends UnitTestCase
{
    use TestTypo3PropertiesTrait;

    protected Link $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new Link();
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
    public function getLinkInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getLink(),
        );
    }

    /**
     * @test
     */
    public function setLinkSetsLink(): void
    {
        $this->subject->setLink('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getLink(),
        );
    }

    /**
     * @test
     */
    public function getTitleInitiallyReturnsVideo(): void
    {
        self::assertSame(
            'Video',
            $this->subject->getTitle(),
        );
    }

    /**
     * @test
     */
    public function setTitleSetsTitle(): void
    {
        $this->subject->setTitle('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getTitle(),
        );
    }

    /**
     * @test
     */
    public function getDeletedInitiallyReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->getDeleted(),
        );
    }

    /**
     * @test
     */
    public function setDeletedSetsDeleted(): void
    {
        $this->subject->setDeleted(true);
        self::assertTrue(
            $this->subject->getDeleted(),
        );
    }
}
