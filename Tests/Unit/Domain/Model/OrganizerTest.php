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
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class OrganizerTest extends UnitTestCase
{
    use TestTypo3PropertiesTrait;

    protected Organizer $subject;

    protected function setUp(): void
    {
        parent::setUp();

        date_default_timezone_set('Europe/Berlin');

        $this->subject = new Organizer();
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function getOrganizerInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getOrganizer(),
        );
    }

    #[Test]
    public function setOrganizerSetsOrganizer(): void
    {
        $this->subject->setOrganizer('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getOrganizer(),
        );
    }

    #[Test]
    public function getLinkInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getLink());
    }

    #[Test]
    public function setLinkSetsLink(): void
    {
        $instance = new Link();
        $this->subject->setLink($instance);

        self::assertSame(
            $instance,
            $this->subject->getLink(),
        );
    }
}
