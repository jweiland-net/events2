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
use JWeiland\Events2\Domain\Model\Organizer;
use JWeiland\Events2\Tests\Unit\Domain\Traits\TestTypo3PropertiesTrait;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test case.
 */
class OrganizerTest extends UnitTestCase
{
    use TestTypo3PropertiesTrait;

    /**
     * @var \JWeiland\Events2\Domain\Model\Organizer
     */
    protected $subject;

    public function setUp(): void
    {
        $this->subject = new Organizer();
    }

    public function tearDown(): void
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getOrganizerInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getOrganizer()
        );
    }

    /**
     * @test
     */
    public function setOrganizerSetsOrganizer(): void
    {
        $this->subject->setOrganizer('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getOrganizer()
        );
    }

    /**
     * @test
     */
    public function getLinkInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getLink());
    }

    /**
     * @test
     */
    public function setLinkSetsLink(): void
    {
        $instance = new Link();
        $this->subject->setLink($instance);

        self::assertSame(
            $instance,
            $this->subject->getLink()
        );
    }
}
