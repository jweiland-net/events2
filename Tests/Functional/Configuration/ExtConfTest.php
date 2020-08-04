<?php

namespace JWeiland\Events2\Tests\Functional\Configuration;

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
        $this->assertSame(
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

        $this->assertSame(
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

        $this->assertSame(
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

        $this->assertSame(
            1,
            $this->subject->getPoiCollectionPid()
        );
    }

    /**
     * @test
     */
    public function getRootUidInitiallyReturnsZero()
    {
        $this->assertSame(
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

        $this->assertSame(
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

        $this->assertSame(
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

        $this->assertSame(
            1,
            $this->subject->getRootUid()
        );
    }

    /**
     * @test
     */
    public function getRecurringPastReturns3monthAsDefault()
    {
        $this->assertSame(
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
        $this->assertSame(
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
        $this->assertSame(
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
        $this->assertSame(
            0,
            $this->subject->getRecurringPast()
        );
    }

    /**
     * @test
     */
    public function getRecurringFutureReturns6monthAsDefault()
    {
        $this->assertSame(
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
        $this->assertSame(
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
        $this->assertSame(
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
        $this->assertSame(
            6,
            $this->subject->getRecurringFuture()
        );
    }

    /**
     * @test
     */
    public function getDefaultCountryInitiallyReturnsEmptyString()
    {
        $this->assertSame(
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

        $this->assertSame(
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
        $this->assertSame('123', $this->subject->getDefaultCountry());
    }

    /**
     * @test
     */
    public function setDefaultCountryWithBooleanResultsInString()
    {
        $this->subject->setDefaultCountry(true);
        $this->assertSame('1', $this->subject->getDefaultCountry());
    }

    /**
     * @test
     *
     * @expectedException \Exception
     * @expectedExceptionCode 1484823422
     */
    public function getEmailFromAddressInitiallyReturnsEmptyString()
    {
        $this->assertSame(
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

        $this->assertSame(
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
        $this->assertSame('123', $this->subject->getEmailFromAddress());
    }

    /**
     * @test
     */
    public function setEmailFromAddressWithBooleanResultsInString()
    {
        $this->subject->setEmailFromAddress(true);
        $this->assertSame('1', $this->subject->getEmailFromAddress());
    }

    /**
     * @test
     *
     * @expectedException \Exception
     * @expectedExceptionCode 1484823661
     */
    public function getEmailFromNameInitiallyReturnsEmptyString()
    {
        $this->assertSame(
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

        $this->assertSame(
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
        $this->assertSame('123', $this->subject->getEmailFromName());
    }

    /**
     * @test
     */
    public function setEmailFromNameWithBooleanResultsInString()
    {
        $this->subject->setEmailFromName(true);
        $this->assertSame('1', $this->subject->getEmailFromName());
    }

    /**
     * @test
     */
    public function getEmailToAddressInitiallyReturnsEmptyString()
    {
        $this->assertSame(
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

        $this->assertSame(
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
        $this->assertSame('123', $this->subject->getEmailToAddress());
    }

    /**
     * @test
     */
    public function setEmailToAddressWithBooleanResultsInString()
    {
        $this->subject->setEmailToAddress(true);
        $this->assertSame('1', $this->subject->getEmailToAddress());
    }

    /**
     * @test
     */
    public function getEmailToNameInitiallyReturnsEmptyString()
    {
        $this->assertSame(
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

        $this->assertSame(
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
        $this->assertSame('123', $this->subject->getEmailToName());
    }

    /**
     * @test
     */
    public function setEmailToNameWithBooleanResultsInString()
    {
        $this->subject->setEmailToName(true);
        $this->assertSame('1', $this->subject->getEmailToName());
    }
}
