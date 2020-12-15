<?php

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

    public function setUp()
    {
        parent::setUp();

        $this->subject = new ExtConf();
        $this->subject->setRecurringPast('3');
        $this->subject->setRecurringFuture('6');
    }

    public function tearDown()
    {
        unset($this->subject);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getPoiCollectionPidInitiallyReturnsZero()
    {
        self::assertSame(
            0,
            $this->subject->getPoiCollectionPid()
        );
    }

    /**
     * @test
     */
    public function setPoiCollectionPidSetsPoiCollectionPid()
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
    public function setPoiCollectionPidWithStringResultsInInteger()
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
    public function setPoiCollectionPidWithBooleanResultsInInteger()
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
    public function getRootUidInitiallyReturnsZero()
    {
        self::assertSame(
            0,
            $this->subject->getRootUid()
        );
    }

    /**
     * @test
     */
    public function setRootUidSetsRootUid()
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
    public function setRootUidWithStringResultsInInteger()
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
    public function setRootUidWithBooleanResultsInInteger()
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
    public function getRecurringPastReturns3monthAsDefault()
    {
        self::assertSame(
            3,
            $this->subject->getRecurringPast()
        );
    }

    /**
     * @test
     */
    public function setRecurringPastWithIntegerWillReturnSameInGetter()
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
    public function setRecurringPastWithStringWillReturnIntegerInGetter()
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
    public function setRecurringPastWithInvalidValueWillReturnIntCastedValueInGetter()
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
    public function getRecurringFutureReturns6monthAsDefault()
    {
        self::assertSame(
            6,
            $this->subject->getRecurringFuture()
        );
    }

    /**
     * @test
     */
    public function setRecurringFutureWithIntegerWillReturnSameInGetter()
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
    public function setRecurringFutureWithStringWillReturnIntegerInGetter()
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
    public function setRecurringFutureWithInvalidValueWillReturnDefaultValueInGetter()
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
    public function getDefaultCountryInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getDefaultCountry()
        );
    }

    /**
     * @test
     */
    public function setDefaultCountrySetsDefaultCountry()
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
    public function setDefaultCountryWithIntegerResultsInString()
    {
        $this->subject->setDefaultCountry(123);
        self::assertSame('123', $this->subject->getDefaultCountry());
    }

    /**
     * @test
     */
    public function setDefaultCountryWithBooleanResultsInString()
    {
        $this->subject->setDefaultCountry(true);
        self::assertSame('1', $this->subject->getDefaultCountry());
    }

    /**
     * @test
     */
    public function getXmlImportValidatorPathInitiallyReturnsDefaultXsdPath()
    {
        self::assertSame(
            'EXT:events2/Resources/Public/XmlImportValidator.xsd',
            $this->subject->getXmlImportValidatorPath()
        );
    }

    /**
     * @test
     */
    public function setXmlImportValidatorPathSetsXmlImportValidatorPath()
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
    public function setXmlImportValidatorPathWithIntegerResultsInString()
    {
        $this->subject->setXmlImportValidatorPath(123);
        self::assertSame('123', $this->subject->getXmlImportValidatorPath());
    }

    /**
     * @test
     */
    public function setXmlImportValidatorPathWithBooleanResultsInString()
    {
        $this->subject->setXmlImportValidatorPath(true);
        self::assertSame('1', $this->subject->getXmlImportValidatorPath());
    }

    /**
     * @test
     */
    public function getOrganizerIsRequiredInitiallyReturnsFalse()
    {
        self::assertFalse(
            $this->subject->getOrganizerIsRequired()
        );
    }

    /**
     * @test
     */
    public function setOrganizerIsRequiredSetsOrganizerIsRequired()
    {
        $this->subject->setOrganizerIsRequired(true);
        self::assertTrue(
            $this->subject->getOrganizerIsRequired()
        );
    }

    /**
     * @test
     */
    public function setOrganizerIsRequiredWithStringReturnsTrue()
    {
        $this->subject->setOrganizerIsRequired('foo bar');
        self::assertTrue($this->subject->getOrganizerIsRequired());
    }

    /**
     * @test
     */
    public function setOrganizerIsRequiredWithZeroReturnsFalse()
    {
        $this->subject->setOrganizerIsRequired(0);
        self::assertFalse($this->subject->getOrganizerIsRequired());
    }

    /**
     * @test
     */
    public function getLocationIsRequiredInitiallyReturnsFalse()
    {
        self::assertFalse(
            $this->subject->getLocationIsRequired()
        );
    }

    /**
     * @test
     */
    public function setLocationIsRequiredSetsLocationIsRequired()
    {
        $this->subject->setLocationIsRequired(true);
        self::assertTrue(
            $this->subject->getLocationIsRequired()
        );
    }

    /**
     * @test
     */
    public function setLocationIsRequiredWithStringReturnsTrue()
    {
        $this->subject->setLocationIsRequired('foo bar');
        self::assertTrue($this->subject->getLocationIsRequired());
    }

    /**
     * @test
     */
    public function setLocationIsRequiredWithZeroReturnsFalse()
    {
        $this->subject->setLocationIsRequired(0);
        self::assertFalse($this->subject->getLocationIsRequired());
    }

    /**
     * @test
     */
    public function getEmailFromAddressInitiallyReturnsEmptyString()
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
    public function setEmailFromAddressSetsEmailFromAddress()
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
    public function setEmailFromAddressWithIntegerResultsInString()
    {
        $this->subject->setEmailFromAddress(123);
        self::assertSame('123', $this->subject->getEmailFromAddress());
    }

    /**
     * @test
     */
    public function setEmailFromAddressWithBooleanResultsInString()
    {
        $this->subject->setEmailFromAddress(true);
        self::assertSame('1', $this->subject->getEmailFromAddress());
    }

    /**
     * @test
     */
    public function getEmailFromNameInitiallyReturnsEmptyString()
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
    public function setEmailFromNameSetsEmailFromName()
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
    public function setEmailFromNameWithIntegerResultsInString()
    {
        $this->subject->setEmailFromName(123);
        self::assertSame('123', $this->subject->getEmailFromName());
    }

    /**
     * @test
     */
    public function setEmailFromNameWithBooleanResultsInString()
    {
        $this->subject->setEmailFromName(true);
        self::assertSame('1', $this->subject->getEmailFromName());
    }

    /**
     * @test
     */
    public function getEmailToAddressInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getEmailToAddress()
        );
    }

    /**
     * @test
     */
    public function setEmailToAddressSetsEmailToAddress()
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
    public function setEmailToAddressWithIntegerResultsInString()
    {
        $this->subject->setEmailToAddress(123);
        self::assertSame('123', $this->subject->getEmailToAddress());
    }

    /**
     * @test
     */
    public function setEmailToAddressWithBooleanResultsInString()
    {
        $this->subject->setEmailToAddress(true);
        self::assertSame('1', $this->subject->getEmailToAddress());
    }

    /**
     * @test
     */
    public function getEmailToNameInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getEmailToName()
        );
    }

    /**
     * @test
     */
    public function setEmailToNameSetsEmailToName()
    {
        $this->subject->setEmailToName('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getEmailToName()
        );
    }

    /**
     * @test
     */
    public function setEmailToNameWithIntegerResultsInString()
    {
        $this->subject->setEmailToName(123);
        self::assertSame('123', $this->subject->getEmailToName());
    }

    /**
     * @test
     */
    public function setEmailToNameWithBooleanResultsInString()
    {
        $this->subject->setEmailToName(true);
        self::assertSame('1', $this->subject->getEmailToName());
    }
}
