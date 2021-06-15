<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Configuration;

use JWeiland\Events2\Configuration\ExtConf;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;

/**
 * Test case.
 */
class ExtConfTest extends FunctionalTestCase
{
    /**
     * @var ExtConf
     */
    protected $subject;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2'
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new ExtConf();
        $this->subject->setRecurringPast('3');
        $this->subject->setRecurringFuture('6');
    }

    public function tearDown(): void
    {
        unset($this->subject);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getPoiCollectionPidInitiallyReturnsZero(): void
    {
        self::assertSame(
            0,
            $this->subject->getPoiCollectionPid()
        );
    }

    /**
     * @test
     */
    public function setPoiCollectionPidSetsPoiCollectionPid(): void
    {
        $this->subject->setPoiCollectionPid(123456);

        self::assertSame(
            123456,
            $this->subject->getPoiCollectionPid()
        );
    }

    /**
     * @test
     */
    public function setPoiCollectionPidWithStringResultsInInteger(): void
    {
        $this->subject->setPoiCollectionPid('123Test');

        self::assertSame(
            123,
            $this->subject->getPoiCollectionPid()
        );
    }

    /**
     * @test
     */
    public function setPoiCollectionPidWithBooleanResultsInInteger(): void
    {
        $this->subject->setPoiCollectionPid(true);

        self::assertSame(
            1,
            $this->subject->getPoiCollectionPid()
        );
    }

    /**
     * @test
     */
    public function getRootUidInitiallyReturnsZero(): void
    {
        self::assertSame(
            0,
            $this->subject->getRootUid()
        );
    }

    /**
     * @test
     */
    public function setRootUidSetsRootUid(): void
    {
        $this->subject->setRootUid(123456);

        self::assertSame(
            123456,
            $this->subject->getRootUid()
        );
    }

    /**
     * @test
     */
    public function setRootUidWithStringResultsInInteger(): void
    {
        $this->subject->setRootUid('123Test');

        self::assertSame(
            123,
            $this->subject->getRootUid()
        );
    }

    /**
     * @test
     */
    public function setRootUidWithBooleanResultsInInteger(): void
    {
        $this->subject->setRootUid(true);

        self::assertSame(
            1,
            $this->subject->getRootUid()
        );
    }

    /**
     * @test
     */
    public function getRecurringPastReturns3monthAsDefault(): void
    {
        self::assertSame(
            3,
            $this->subject->getRecurringPast()
        );
    }

    /**
     * @test
     */
    public function setRecurringPastWithIntegerWillReturnSameInGetter(): void
    {
        $this->subject->setRecurringPast(6);
        self::assertSame(
            6,
            $this->subject->getRecurringPast()
        );
    }

    /**
     * @test
     */
    public function setRecurringPastWithStringWillReturnIntegerInGetter(): void
    {
        $this->subject->setRecurringPast('6');
        self::assertSame(
            6,
            $this->subject->getRecurringPast()
        );
    }

    /**
     * @test
     */
    public function setRecurringPastWithInvalidValueWillReturnIntCastedValueInGetter(): void
    {
        $this->subject->setRecurringPast('invalidValue');
        self::assertSame(
            0,
            $this->subject->getRecurringPast()
        );
    }

    /**
     * @test
     */
    public function getRecurringFutureReturns6monthAsDefault(): void
    {
        self::assertSame(
            6,
            $this->subject->getRecurringFuture()
        );
    }

    /**
     * @test
     */
    public function setRecurringFutureWithIntegerWillReturnSameInGetter(): void
    {
        $this->subject->setRecurringFuture(12);
        self::assertSame(
            12,
            $this->subject->getRecurringFuture()
        );
    }

    /**
     * @test
     */
    public function setRecurringFutureWithStringWillReturnIntegerInGetter(): void
    {
        $this->subject->setRecurringFuture('12');
        self::assertSame(
            12,
            $this->subject->getRecurringFuture()
        );
    }

    /**
     * @test
     */
    public function setRecurringFutureWithInvalidValueWillReturnDefaultValueInGetter(): void
    {
        $this->subject->setRecurringFuture('invalidValue');
        self::assertSame(
            6,
            $this->subject->getRecurringFuture()
        );
    }

    /**
     * @test
     */
    public function getDefaultCountryInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getDefaultCountry()
        );
    }

    /**
     * @test
     */
    public function setDefaultCountrySetsDefaultCountry(): void
    {
        $this->subject->setDefaultCountry('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getDefaultCountry()
        );
    }

    /**
     * @test
     */
    public function getXmlImportValidatorPathInitiallyReturnsDefaultXsdPath(): void
    {
        self::assertSame(
            'EXT:events2/Resources/Public/XmlImportValidator.xsd',
            $this->subject->getXmlImportValidatorPath()
        );
    }

    /**
     * @test
     */
    public function setXmlImportValidatorPathSetsXmlImportValidatorPath(): void
    {
        $this->subject->setXmlImportValidatorPath('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getXmlImportValidatorPath()
        );
    }

    /**
     * @test
     */
    public function getOrganizerIsRequiredInitiallyReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->getOrganizerIsRequired()
        );
    }

    /**
     * @test
     */
    public function setOrganizerIsRequiredSetsOrganizerIsRequired(): void
    {
        $this->subject->setOrganizerIsRequired(true);
        self::assertTrue(
            $this->subject->getOrganizerIsRequired()
        );
    }

    /**
     * @test
     */
    public function setOrganizerIsRequiredWithStringReturnsTrue(): void
    {
        $this->subject->setOrganizerIsRequired('foo bar');
        self::assertTrue($this->subject->getOrganizerIsRequired());
    }

    /**
     * @test
     */
    public function setOrganizerIsRequiredWithZeroReturnsFalse(): void
    {
        $this->subject->setOrganizerIsRequired(0);
        self::assertFalse($this->subject->getOrganizerIsRequired());
    }

    /**
     * @test
     */
    public function getLocationIsRequiredInitiallyReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->getLocationIsRequired()
        );
    }

    /**
     * @test
     */
    public function setLocationIsRequiredSetsLocationIsRequired(): void
    {
        $this->subject->setLocationIsRequired(true);
        self::assertTrue(
            $this->subject->getLocationIsRequired()
        );
    }

    /**
     * @test
     */
    public function setLocationIsRequiredWithStringReturnsTrue(): void
    {
        $this->subject->setLocationIsRequired('foo bar');
        self::assertTrue($this->subject->getLocationIsRequired());
    }

    /**
     * @test
     */
    public function setLocationIsRequiredWithZeroReturnsFalse(): void
    {
        $this->subject->setLocationIsRequired(0);
        self::assertFalse($this->subject->getLocationIsRequired());
    }

    /**
     * @test
     */
    public function getEmailFromAddressInitiallyReturnsEmptyString(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1484823422);

        self::assertSame(
            '',
            $this->subject->getEmailFromAddress()
        );
    }

    /**
     * @test
     */
    public function setEmailFromAddressSetsEmailFromAddress(): void
    {
        $this->subject->setEmailFromAddress('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getEmailFromAddress()
        );
    }

    /**
     * @test
     */
    public function getEmailFromNameInitiallyReturnsEmptyString(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1484823661);

        self::assertSame(
            '',
            $this->subject->getEmailFromName()
        );
    }

    /**
     * @test
     */
    public function setEmailFromNameSetsEmailFromName(): void
    {
        $this->subject->setEmailFromName('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getEmailFromName()
        );
    }

    /**
     * @test
     */
    public function getEmailToAddressInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getEmailToAddress()
        );
    }

    /**
     * @test
     */
    public function setEmailToAddressSetsEmailToAddress(): void
    {
        $this->subject->setEmailToAddress('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getEmailToAddress()
        );
    }

    /**
     * @test
     */
    public function getEmailToNameInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->subject->getEmailToName()
        );
    }

    /**
     * @test
     */
    public function setEmailToNameSetsEmailToName(): void
    {
        $this->subject->setEmailToName('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getEmailToName()
        );
    }
}
