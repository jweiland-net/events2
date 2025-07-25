<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Domain\Model;

use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Unit\Domain\Traits\TestTypo3PropertiesTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 */
class LocationTest extends FunctionalTestCase
{
    use TestTypo3PropertiesTrait;

    protected Location $subject;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'phpTimeZone' => Events2Constants::PHP_TIMEZONE,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new Location();
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function getLocationInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getLocation(),
        );
    }

    #[Test]
    public function setLocationSetsLocation(): void
    {
        $this->subject->setLocation('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getLocation(),
        );
    }

    #[Test]
    public function getStreetInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getStreet(),
        );
    }

    #[Test]
    public function setStreetSetsStreet(): void
    {
        $this->subject->setStreet('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getStreet(),
        );
    }

    #[Test]
    public function getHouseNumberInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getHouseNumber(),
        );
    }

    #[Test]
    public function setHouseNumberSetsHouseNumber(): void
    {
        $this->subject->setHouseNumber('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getHouseNumber(),
        );
    }

    #[Test]
    public function getZipInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getZip(),
        );
    }

    #[Test]
    public function setZipSetsZip(): void
    {
        $this->subject->setZip('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getZip(),
        );
    }

    #[Test]
    public function getCityInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getCity(),
        );
    }

    #[Test]
    public function setCitySetsCity(): void
    {
        $this->subject->setCity('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getCity(),
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
