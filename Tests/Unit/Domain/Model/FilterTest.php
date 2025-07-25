<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Domain\Model;

use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class FilterTest extends UnitTestCase
{
    protected Filter $subject;

    protected function setUp(): void
    {
        parent::setUp();

        date_default_timezone_set(Events2Constants::PHP_TIMEZONE);

        $this->subject = new Filter();
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function getOrganizerInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getOrganizer());
    }

    #[Test]
    public function setOrganizerSetsOrganizer(): void
    {
        $organizer = 34;
        $this->subject->setOrganizer($organizer);

        self::assertSame(
            $organizer,
            $this->subject->getOrganizer(),
        );
    }

    #[Test]
    public function setOrganizerWithNullSetsOrganizer(): void
    {
        $this->subject->setOrganizer(null);

        self::assertNull(
            $this->subject->getOrganizer(),
        );
    }
}
