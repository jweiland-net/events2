<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Configuration;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Country\CountryProvider;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * This class streamlines all settings from the extension manager
 */
#[Autoconfigure(constructor: 'create')]
readonly class ExtConf implements SingletonInterface
{
    private const EXT_KEY = 'events2';

    private const DEFAULT_SETTINGS = [
        // general
        'poiCollectionPid' => 0,
        'rootUid' => 0,
        'recurringPast' => 3,
        'recurringFuture' => 6,
        'defaultCountry' => '',
        'xmlImportValidatorPath' => 'EXT:events2/Resources/Public/XmlImportValidator.xsd',
        'organizerIsRequired' => false,
        'locationIsRequired' => false,
        'categoryIsRequired' => false,

        // email
        'emailFromAddress' => '',
        'emailFromName' => '',
        'emailToAddress' => '',
        'emailToName' => '',
    ];

    public function __construct(
        // general
        private int $poiCollectionPid = self::DEFAULT_SETTINGS['poiCollectionPid'],
        private int $rootUid = self::DEFAULT_SETTINGS['rootUid'],
        private int $recurringPast = self::DEFAULT_SETTINGS['recurringPast'],
        private int $recurringFuture = self::DEFAULT_SETTINGS['recurringFuture'],
        private string $defaultCountry = self::DEFAULT_SETTINGS['defaultCountry'],
        private string $xmlImportValidatorPath = self::DEFAULT_SETTINGS['xmlImportValidatorPath'],
        private bool $organizerIsRequired = self::DEFAULT_SETTINGS['organizerIsRequired'],
        private bool $locationIsRequired = self::DEFAULT_SETTINGS['locationIsRequired'],
        private bool $categoryIsRequired = self::DEFAULT_SETTINGS['categoryIsRequired'],

        // email
        private string $emailFromAddress = self::DEFAULT_SETTINGS['emailFromAddress'],
        private string $emailFromName = self::DEFAULT_SETTINGS['emailFromName'],
        private string $emailToAddress = self::DEFAULT_SETTINGS['emailToAddress'],
        private string $emailToName = self::DEFAULT_SETTINGS['emailToName'],
    ) {}

    /**
     * Factory used by DI. The CountryProvider is injected here, as it is only available in DI context.
     * In failsafe mode (install tool) ExtConf is instantiated with its default values.
     */
    public static function create(
        ExtensionConfiguration $extensionConfiguration,
        CountryProvider $countryProvider,
    ): self {
        $extensionSettings = self::DEFAULT_SETTINGS;

        // Overwrite default extension settings with values from EXT_CONF
        try {
            $extensionSettings = array_merge(
                $extensionSettings,
                $extensionConfiguration->get(self::EXT_KEY),
            );
        } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException) {
        }

        return new self(
            // general
            poiCollectionPid: (int)$extensionSettings['poiCollectionPid'],
            rootUid: (int)$extensionSettings['rootUid'],
            recurringPast: (int)$extensionSettings['recurringPast'],
            recurringFuture: (int)$extensionSettings['recurringFuture'],
            defaultCountry: self::sanitizeCountryCode((string)$extensionSettings['defaultCountry'], $countryProvider),
            xmlImportValidatorPath: (string)$extensionSettings['xmlImportValidatorPath'],
            organizerIsRequired: (bool)$extensionSettings['organizerIsRequired'],
            locationIsRequired: (bool)$extensionSettings['locationIsRequired'],
            categoryIsRequired: (bool)$extensionSettings['categoryIsRequired'],

            // email
            emailFromAddress: (string)$extensionSettings['emailFromAddress'],
            emailFromName: (string)$extensionSettings['emailFromName'],
            emailToAddress: (string)$extensionSettings['emailToAddress'],
            emailToName: (string)$extensionSettings['emailToName'],
        );
    }

    public function getPoiCollectionPid(): int
    {
        return $this->poiCollectionPid;
    }

    public function getRootUid(): int
    {
        return $this->rootUid;
    }

    public function getRecurringPast(): int
    {
        return $this->recurringPast;
    }

    public function getRecurringFuture(): int
    {
        return $this->recurringFuture;
    }

    /**
     * ISO 3166-1 alpha-2 code of the default country or empty string
     */
    public function getDefaultCountry(): string
    {
        return $this->defaultCountry;
    }

    public function getXmlImportValidatorPath(): string
    {
        return $this->xmlImportValidatorPath;
    }

    public function getOrganizerIsRequired(): bool
    {
        return $this->organizerIsRequired;
    }

    public function getLocationIsRequired(): bool
    {
        return $this->locationIsRequired;
    }

    public function getCategoryIsRequired(): bool
    {
        return $this->categoryIsRequired;
    }

    /**
     * @throws \Exception
     */
    public function getEmailFromAddress(): string
    {
        if ($this->emailFromAddress === '') {
            $senderMail = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
            if ($senderMail === '') {
                throw new \Exception('You have forgotten to set a sender email address in extension configuration or in install tool', 1484823422);
            }

            return $senderMail;
        }

        return $this->emailFromAddress;
    }

    /**
     * @throws \Exception
     */
    public function getEmailFromName(): string
    {
        if ($this->emailFromName === '') {
            $senderName = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'];
            if ($senderName === '') {
                throw new \Exception('You have forgotten to set a sender name in extension configuration or in install tool', 1484823661);
            }

            return $senderName;
        }

        return $this->emailFromName;
    }

    public function getEmailToAddress(): string
    {
        return $this->emailToAddress;
    }

    public function getEmailToName(): string
    {
        return $this->emailToName;
    }

    /**
     * Returns the ISO 3166-1 alpha-2 code, if it is a country known by TYPO3 core.
     * Former versions of events2 stored the UID of a static_countries record.
     * Such values and all other invalid values result in an empty string.
     */
    private static function sanitizeCountryCode(string $countryCode, CountryProvider $countryProvider): string
    {
        $countryCode = trim($countryCode);
        if (strlen($countryCode) !== 2) {
            return '';
        }

        return $countryProvider->getByAlpha2IsoCode(strtoupper($countryCode))?->getAlpha2IsoCode() ?? '';
    }
}
