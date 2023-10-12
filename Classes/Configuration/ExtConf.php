<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Configuration;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;

/*
 * This class streamlines all settings from extension manager
 */
class ExtConf implements SingletonInterface
{
    protected int $poiCollectionPid = 0;

    protected int $rootUid = 0;

    protected int $recurringPast = 0;

    protected int $recurringFuture = 0;

    protected int $defaultCountry = 0;

    protected string $xmlImportValidatorPath = '';

    protected bool $organizerIsRequired = false;

    protected bool $locationIsRequired = false;

    protected bool $categoryIsRequired = false;

    protected string $pathSegmentType = 'empty';

    protected string $emailFromAddress = '';

    protected string $emailFromName = '';

    protected string $emailToAddress = '';

    protected string $emailToName = '';

    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        $extConf = $extensionConfiguration->get('events2');
        if (!is_array($extConf)) {
            return;
        }

        // call setter method foreach configuration entry
        foreach ($extConf as $key => $value) {
            $methodName = 'set' . ucfirst($key);
            if (method_exists($this, $methodName)) {
                $this->$methodName($value);
            }
        }
    }

    public function getPoiCollectionPid(): int
    {
        return $this->poiCollectionPid;
    }

    public function setPoiCollectionPid($poiCollectionPid): void
    {
        $this->poiCollectionPid = (int)$poiCollectionPid;
    }

    public function getRootUid(): int
    {
        if (empty($this->rootUid)) {
            return 0;
        }

        return $this->rootUid;
    }

    public function setRootUid($rootUid): void
    {
        $this->rootUid = (int)$rootUid;
    }

    public function getRecurringPast(): int
    {
        if ($this->recurringPast >= 0) {
            return $this->recurringPast;
        }

        return 3;
    }

    public function setRecurringPast($recurringPast): void
    {
        $this->recurringPast = (int)$recurringPast;
    }

    public function getRecurringFuture(): int
    {
        if (empty($this->recurringFuture)) {
            return 6;
        }

        return $this->recurringFuture;
    }

    public function setRecurringFuture($recurringFuture): void
    {
        $this->recurringFuture = (int)$recurringFuture;
    }

    public function getDefaultCountry(): int
    {
        return $this->defaultCountry;
    }

    public function setDefaultCountry(string $defaultCountry): void
    {
        $this->defaultCountry = (int)$defaultCountry;
    }

    public function getXmlImportValidatorPath(): string
    {
        if (empty($this->xmlImportValidatorPath)) {
            $this->xmlImportValidatorPath = 'EXT:events2/Resources/Public/XmlImportValidator.xsd';
        }

        return $this->xmlImportValidatorPath;
    }

    public function setXmlImportValidatorPath(string $xmlImportValidatorPath): void
    {
        $this->xmlImportValidatorPath = $xmlImportValidatorPath;
    }

    public function getOrganizerIsRequired(): bool
    {
        return $this->organizerIsRequired;
    }

    public function setOrganizerIsRequired($organizerIsRequired): void
    {
        $this->organizerIsRequired = (bool)$organizerIsRequired;
    }

    public function getLocationIsRequired(): bool
    {
        return $this->locationIsRequired;
    }

    public function setLocationIsRequired($locationIsRequired): void
    {
        $this->locationIsRequired = (bool)$locationIsRequired;
    }

    public function getCategoryIsRequired(): bool
    {
        return $this->categoryIsRequired;
    }

    public function setCategoryIsRequired($categoryIsRequired): void
    {
        $this->categoryIsRequired = (bool)$categoryIsRequired;
    }

    public function getPathSegmentType(): string
    {
        if (empty($this->pathSegmentType)) {
            $this->pathSegmentType = 'empty';
        }

        return $this->pathSegmentType;
    }

    public function setPathSegmentType(string $pathSegmentType): void
    {
        $this->pathSegmentType = $pathSegmentType;
    }

    /**
     * @throws \Exception
     */
    public function getEmailFromAddress(): string
    {
        if (empty($this->emailFromAddress)) {
            $senderMail = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
            if (empty($senderMail)) {
                throw new \Exception('You have forgotten to set a sender email address in extension configuration or in install tool', 1484823422);
            }

            return $senderMail;
        }

        return $this->emailFromAddress;
    }

    public function setEmailFromAddress(string $emailFromAddress): void
    {
        $this->emailFromAddress = $emailFromAddress;
    }

    /**
     * @throws \Exception
     */
    public function getEmailFromName(): string
    {
        if (empty($this->emailFromName)) {
            $senderName = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'];
            if (empty($senderName)) {
                throw new \Exception('You have forgotten to set a sender name in extension configuration or in install tool', 1484823661);
            }

            return $senderName;
        }

        return $this->emailFromName;
    }

    public function setEmailFromName(string $emailFromName): void
    {
        $this->emailFromName = $emailFromName;
    }

    public function getEmailToAddress(): string
    {
        return $this->emailToAddress;
    }

    public function setEmailToAddress(string $emailToAddress): void
    {
        $this->emailToAddress = $emailToAddress;
    }
    public function getEmailToName(): string
    {
        return $this->emailToName;
    }

    public function setEmailToName(string $emailToName): void
    {
        $this->emailToName = $emailToName;
    }
}
