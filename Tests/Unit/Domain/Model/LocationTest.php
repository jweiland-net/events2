<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Domain\Model;

use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Tests\Unit\Domain\Traits\TestTypo3PropertiesTrait;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test case.
 */
class LocationTest extends UnitTestCase
{
    use TestTypo3PropertiesTrait;

    /**
     * @var \JWeiland\Events2\Domain\Model\Location
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = new Location();
    }

    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getLocationInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getLocation()
        );
    }

    /**
     * @test
     */
    public function setLocationSetsLocation()
    {
        $this->subject->setLocation('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getLocation()
        );
    }

    /**
     * @test
     */
    public function setLocationWithIntegerResultsInString()
    {
        $this->subject->setLocation(123);
        self::assertSame('123', $this->subject->getLocation());
    }

    /**
     * @test
     */
    public function setLocationWithBooleanResultsInString()
    {
        $this->subject->setLocation(true);
        self::assertSame('1', $this->subject->getLocation());
    }

    /**
     * @test
     */
    public function getStreetInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getStreet()
        );
    }

    /**
     * @test
     */
    public function setStreetSetsStreet()
    {
        $this->subject->setStreet('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getStreet()
        );
    }

    /**
     * @test
     */
    public function setStreetWithIntegerResultsInString()
    {
        $this->subject->setStreet(123);
        self::assertSame('123', $this->subject->getStreet());
    }

    /**
     * @test
     */
    public function setStreetWithBooleanResultsInString()
    {
        $this->subject->setStreet(true);
        self::assertSame('1', $this->subject->getStreet());
    }

    /**
     * @test
     */
    public function getHouseNumberInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getHouseNumber()
        );
    }

    /**
     * @test
     */
    public function setHouseNumberSetsHouseNumber()
    {
        $this->subject->setHouseNumber('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getHouseNumber()
        );
    }

    /**
     * @test
     */
    public function setHouseNumberWithIntegerResultsInString()
    {
        $this->subject->setHouseNumber(123);
        self::assertSame('123', $this->subject->getHouseNumber());
    }

    /**
     * @test
     */
    public function setHouseNumberWithBooleanResultsInString()
    {
        $this->subject->setHouseNumber(true);
        self::assertSame('1', $this->subject->getHouseNumber());
    }

    /**
     * @test
     */
    public function getZipInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getZip()
        );
    }

    /**
     * @test
     */
    public function setZipSetsZip()
    {
        $this->subject->setZip('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getZip()
        );
    }

    /**
     * @test
     */
    public function setZipWithIntegerResultsInString()
    {
        $this->subject->setZip(123);
        self::assertSame('123', $this->subject->getZip());
    }

    /**
     * @test
     */
    public function setZipWithBooleanResultsInString()
    {
        $this->subject->setZip(true);
        self::assertSame('1', $this->subject->getZip());
    }

    /**
     * @test
     */
    public function getCityInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getCity()
        );
    }

    /**
     * @test
     */
    public function setCitySetsCity()
    {
        $this->subject->setCity('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getCity()
        );
    }

    /**
     * @test
     */
    public function setCityWithIntegerResultsInString()
    {
        $this->subject->setCity(123);
        self::assertSame('123', $this->subject->getCity());
    }

    /**
     * @test
     */
    public function setCityWithBooleanResultsInString()
    {
        $this->subject->setCity(true);
        self::assertSame('1', $this->subject->getCity());
    }

    /**
     * @test
     */
    public function getLinkInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getLink());
    }

    /**
     * @test
     */
    public function setLinkSetsLink()
    {
        $instance = new Link();
        $this->subject->setLink($instance);

        self::assertSame(
            $instance,
            $this->subject->getLink()
        );
    }

    /**
     * @test
     */
    public function getTxMaps2UidInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getTxMaps2Uid());
    }
}
