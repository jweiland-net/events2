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
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 */
class ExtConfTest extends FunctionalTestCase
{
    public ExtensionConfiguration|MockObject $extensionConfigurationMock;

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

        $this->extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
    }

    protected function tearDown(): void
    {
        unset(
            $this->extensionConfigurationMock,
        );

        parent::tearDown();
    }

    #[Test]
    public function getPoiCollectionPidInitiallyReturnsZero(): void
    {
        $config = [];
        $subject = new ExtConf(...$config);

        self::assertSame(
            0,
            $subject->getPoiCollectionPid(),
        );
    }

    #[Test]
    public function setPoiCollectionPidSetsPoiCollectionPid(): void
    {
        $config = [
            'poiCollectionPid' => 123456,
        ];
        $subject = new ExtConf(...$config);

        self::assertSame(
            123456,
            $subject->getPoiCollectionPid(),
        );
    }

    #[Test]
    public function setPoiCollectionPidWithStringResultsInInteger(): void
    {
        $this->extensionConfigurationMock
            ->expects(self::once())
            ->method('get')
            ->with('events2')
            ->willReturn([
                'poiCollectionPid' => '123Test',
            ]);

        $subject = ExtConf::create($this->extensionConfigurationMock);

        self::assertSame(
            123,
            $subject->getPoiCollectionPid(),
        );
    }

    #[Test]
    public function getRootUidInitiallyReturnsZero(): void
    {
        $config = [];
        $subject = new ExtConf(...$config);

        self::assertSame(
            0,
            $subject->getRootUid(),
        );
    }

    #[Test]
    public function setRootUidSetsRootUid(): void
    {
        $config = [
            'rootUid' => 123456,
        ];
        $subject = new ExtConf(...$config);

        self::assertSame(
            123456,
            $subject->getRootUid(),
        );
    }

    #[Test]
    public function setRootUidWithStringResultsInInteger(): void
    {
        $this->extensionConfigurationMock
            ->expects(self::once())
            ->method('get')
            ->with('events2')
            ->willReturn([
                'rootUid' => '123Test',
            ]);

        $subject = ExtConf::create($this->extensionConfigurationMock);

        self::assertSame(
            123,
            $subject->getRootUid(),
        );
    }

    #[Test]
    public function getRecurringPastReturns3monthAsDefault(): void
    {
        $config = [];
        $subject = new ExtConf(...$config);

        self::assertSame(
            3,
            $subject->getRecurringPast(),
        );
    }

    #[Test]
    public function setRecurringPastWithIntegerWillReturnSameInGetter(): void
    {
        $config = [
            'recurringPast' => 6,
        ];
        $subject = new ExtConf(...$config);

        self::assertSame(
            6,
            $subject->getRecurringPast(),
        );
    }

    #[Test]
    public function setRecurringPastWithStringWillReturnIntegerInGetter(): void
    {
        $this->extensionConfigurationMock
            ->expects(self::once())
            ->method('get')
            ->with('events2')
            ->willReturn([
                'recurringPast' => '6',
            ]);

        $subject = ExtConf::create($this->extensionConfigurationMock);

        self::assertSame(
            6,
            $subject->getRecurringPast(),
        );
    }

    #[Test]
    public function getRecurringFutureReturns6monthAsDefault(): void
    {
        $config = [];
        $subject = new ExtConf(...$config);

        self::assertSame(
            6,
            $subject->getRecurringFuture(),
        );
    }

    #[Test]
    public function setRecurringFutureWithIntegerWillReturnSameInGetter(): void
    {
        $config = [
            'recurringFuture' => 12,
        ];
        $subject = new ExtConf(...$config);

        self::assertSame(
            12,
            $subject->getRecurringFuture(),
        );
    }

    #[Test]
    public function setRecurringFutureWithStringWillReturnIntegerInGetter(): void
    {
        $this->extensionConfigurationMock
            ->expects(self::once())
            ->method('get')
            ->with('events2')
            ->willReturn([
                'recurringFuture' => '12',
            ]);

        $subject = ExtConf::create($this->extensionConfigurationMock);

        self::assertSame(
            12,
            $subject->getRecurringFuture(),
        );
    }

    #[Test]
    public function getDefaultCountryInitiallyReturnsZero(): void
    {
        $config = [];
        $subject = new ExtConf(...$config);

        self::assertSame(
            0,
            $subject->getDefaultCountry(),
        );
    }

    #[Test]
    public function setDefaultCountrySetsDefaultCountryAsInteger(): void
    {
        $this->extensionConfigurationMock
            ->expects(self::once())
            ->method('get')
            ->with('events2')
            ->willReturn([
                'defaultCountry' => '45',
            ]);

        $subject = ExtConf::create($this->extensionConfigurationMock);

        self::assertSame(
            45,
            $subject->getDefaultCountry(),
        );
    }

    #[Test]
    public function setDefaultCountryWithEmptyStringSetsDefaultCountryToZero(): void
    {
        $this->extensionConfigurationMock
            ->expects(self::once())
            ->method('get')
            ->with('events2')
            ->willReturn([
                'defaultCountry' => '',
            ]);

        $subject = ExtConf::create($this->extensionConfigurationMock);

        self::assertSame(
            0,
            $subject->getDefaultCountry(),
        );
    }

    #[Test]
    public function getXmlImportValidatorPathInitiallyReturnsDefaultXsdPath(): void
    {
        $config = [];
        $subject = new ExtConf(...$config);

        self::assertSame(
            'EXT:events2/Resources/Public/XmlImportValidator.xsd',
            $subject->getXmlImportValidatorPath(),
        );
    }

    #[Test]
    public function setXmlImportValidatorPathSetsXmlImportValidatorPath(): void
    {
        $this->extensionConfigurationMock
            ->expects(self::once())
            ->method('get')
            ->with('events2')
            ->willReturn([
                'xmlImportValidatorPath' => 'foo bar',
            ]);

        $subject = ExtConf::create($this->extensionConfigurationMock);

        self::assertSame(
            'foo bar',
            $subject->getXmlImportValidatorPath(),
        );
    }

    #[Test]
    public function getOrganizerIsRequiredInitiallyReturnsFalse(): void
    {
        $config = [];
        $subject = new ExtConf(...$config);

        self::assertFalse(
            $subject->getOrganizerIsRequired(),
        );
    }

    #[Test]
    public function setOrganizerIsRequiredSetsOrganizerIsRequired(): void
    {
        $config = [
            'organizerIsRequired' => true,
        ];
        $subject = new ExtConf(...$config);

        self::assertTrue(
            $subject->getOrganizerIsRequired(),
        );
    }

    #[Test]
    public function setOrganizerIsRequiredWithZeroReturnsFalse(): void
    {
        $this->extensionConfigurationMock
            ->expects(self::once())
            ->method('get')
            ->with('events2')
            ->willReturn([
                'organizerIsRequired' => 0,
            ]);

        $subject = ExtConf::create($this->extensionConfigurationMock);

        self::assertFalse($subject->getOrganizerIsRequired());
    }

    #[Test]
    public function getLocationIsRequiredInitiallyReturnsFalse(): void
    {
        $config = [];
        $subject = new ExtConf(...$config);

        self::assertFalse(
            $subject->getLocationIsRequired(),
        );
    }

    #[Test]
    public function setLocationIsRequiredSetsLocationIsRequired(): void
    {
        $config = [
            'locationIsRequired' => true,
        ];
        $subject = new ExtConf(...$config);

        self::assertTrue(
            $subject->getLocationIsRequired(),
        );
    }

    #[Test]
    public function setLocationIsRequiredWithZeroReturnsFalse(): void
    {
        $this->extensionConfigurationMock
            ->expects(self::once())
            ->method('get')
            ->with('events2')
            ->willReturn([
                'locationIsRequired' => 0,
            ]);

        $subject = ExtConf::create($this->extensionConfigurationMock);

        self::assertFalse($subject->getLocationIsRequired());
    }

    #[Test]
    public function getEmailFromAddressInitiallyReturnsEmptyString(): void
    {
        $config = [];
        $subject = new ExtConf(...$config);

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1484823422);

        self::assertSame(
            '',
            $subject->getEmailFromAddress(),
        );
    }

    #[Test]
    public function setEmailFromAddressSetsEmailFromAddress(): void
    {
        $config = [
            'emailFromAddress' => 'foo bar',
        ];
        $subject = new ExtConf(...$config);

        self::assertSame(
            'foo bar',
            $subject->getEmailFromAddress(),
        );
    }

    #[Test]
    public function getEmailFromNameInitiallyReturnsEmptyString(): void
    {
        $config = [];
        $subject = new ExtConf(...$config);

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1484823661);

        self::assertSame(
            '',
            $subject->getEmailFromName(),
        );
    }

    #[Test]
    public function setEmailFromNameSetsEmailFromName(): void
    {
        $config = [
            'emailFromName' => 'foo bar',
        ];
        $subject = new ExtConf(...$config);

        self::assertSame(
            'foo bar',
            $subject->getEmailFromName(),
        );
    }

    #[Test]
    public function getEmailToAddressInitiallyReturnsEmptyString(): void
    {
        $config = [];
        $subject = new ExtConf(...$config);

        self::assertSame(
            '',
            $subject->getEmailToAddress(),
        );
    }

    #[Test]
    public function setEmailToAddressSetsEmailToAddress(): void
    {
        $config = [
            'emailToAddress' => 'foo bar',
        ];
        $subject = new ExtConf(...$config);

        self::assertSame(
            'foo bar',
            $subject->getEmailToAddress(),
        );
    }

    #[Test]
    public function getEmailToNameInitiallyReturnsEmptyString(): void
    {
        $config = [];
        $subject = new ExtConf(...$config);

        self::assertSame(
            '',
            $subject->getEmailToName(),
        );
    }

    #[Test]
    public function setEmailToNameSetsEmailToName(): void
    {
        $config = [
            'emailToName' => 'foo bar',
        ];
        $subject = new ExtConf(...$config);

        self::assertSame(
            'foo bar',
            $subject->getEmailToName(),
        );
    }
}
