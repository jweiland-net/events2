<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Configuration;

use TYPO3\CMS\Core\SingletonInterface;

/*
 * This class streamlines all settings from extension manager
 */
class ExtConf implements SingletonInterface
{
    /**
     * poi collection PID
     *
     * @var int
     */
    protected $poiCollectionPid = 0;

    /**
     * root uid for categories.
     *
     * @var int
     */
    protected $rootUid = 0;

    /**
     * recurring past.
     *
     * @var int
     */
    protected $recurringPast = 0;

    /**
     * recurring future.
     *
     * @var int
     */
    protected $recurringFuture = 0;

    /**
     * default country.
     *
     * @var string
     */
    protected $defaultCountry = '';

    /**
     * Organizer is required
     *
     * @var bool
     */
    protected $organizerIsRequired = false;

    /**
     * Location is required
     *
     * @var bool
     */
    protected $locationIsRequired = false;

    /**
     * email from address.
     *
     * @var string
     */
    protected $emailFromAddress = '';

    /**
     * email from name.
     *
     * @var string
     */
    protected $emailFromName = '';

    /**
     * email to address.
     *
     * @var string
     */
    protected $emailToAddress = '';

    /**
     * email to name.
     *
     * @var string
     */
    protected $emailToName = '';

    /**
     * constructor of this class
     * This method reads the global configuration and calls the setter methods.
     */
    public function __construct()
    {
        // On a fresh installation this value can be null.
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['events2'])) {
            // get global configuration
            $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['events2']);
            if (is_array($extConf) && count($extConf)) {
                // call setter method foreach configuration entry
                foreach ($extConf as $key => $value) {
                    $methodName = 'set' . ucfirst($key);
                    if (method_exists($this, $methodName)) {
                        $this->$methodName($value);
                    }
                }
            }
        }
    }

    /**
     * Returns the poiCollectionPid
     */
    public function getPoiCollectionPid(): int
    {
        return $this->poiCollectionPid;
    }

    /**
     * Sets the poiCollectionPid
     *
     * @param int $poiCollectionPid
     */
    public function setPoiCollectionPid($poiCollectionPid)
    {
        $this->poiCollectionPid = (int)$poiCollectionPid;
    }

    /**
     * getter for rootUid.
     */
    public function getRootUid(): int
    {
        if (empty($this->rootUid)) {
            return 0;
        } else {
            return $this->rootUid;
        }
    }

    /**
     * setter for rootUid.
     *
     * @param int $rootUid
     */
    public function setRootUid($rootUid)
    {
        $this->rootUid = (int)$rootUid;
    }

    /**
     * getter for recurringPast.
     */
    public function getRecurringPast(): int
    {
        if ($this->recurringPast >= 0) {
            return $this->recurringPast;
        } else {
            return 3;
        }
    }

    /**
     * setter for recurring past.
     *
     * @param int $recurringPast
     */
    public function setRecurringPast($recurringPast)
    {
        $this->recurringPast = (int)$recurringPast;
    }

    /**
     * getter for recurringFuture.
     */
    public function getRecurringFuture(): int
    {
        if (empty($this->recurringFuture)) {
            return 6;
        } else {
            return $this->recurringFuture;
        }
    }

    /**
     * setter for recurring future.
     *
     * @param int $recurringFuture
     */
    public function setRecurringFuture($recurringFuture)
    {
        $this->recurringFuture = (int)$recurringFuture;
    }

    /**
     * Returns the defaultCountry
     */
    public function getDefaultCountry(): string
    {
        return $this->defaultCountry;
    }

    /**
     * Sets the defaultCountry
     *
     * @param string $defaultCountry
     */
    public function setDefaultCountry($defaultCountry)
    {
        $this->defaultCountry = (string)$defaultCountry;
    }

    /**
     * Returns the organizerIsRequired
     */
    public function getOrganizerIsRequired(): bool
    {
        return $this->organizerIsRequired;
    }

    /**
     * Sets the organizerIsRequired
     *
     * @param bool $organizerIsRequired
     */
    public function setOrganizerIsRequired($organizerIsRequired)
    {
        $this->organizerIsRequired = (bool)$organizerIsRequired;
    }

    /**
     * Returns the locationIsRequired
     */
    public function getLocationIsRequired(): bool
    {
        return $this->locationIsRequired;
    }

    /**
     * Sets the locationIsRequired
     *
     * @param bool $locationIsRequired
     */
    public function setLocationIsRequired($locationIsRequired)
    {
        $this->locationIsRequired = (bool)$locationIsRequired;
    }

    /**
     * getter for email from address.
     *
     * @throws \Exception
     */
    public function getEmailFromAddress(): string
    {
        if (empty($this->emailFromAddress)) {
            $senderMail = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
            if (empty($senderMail)) {
                throw new \Exception('You have forgotten to set a sender email address in extension configuration or in install tool', 1484823422);
            } else {
                return $senderMail;
            }
        } else {
            return $this->emailFromAddress;
        }
    }

    /**
     * setter for email from address.
     *
     * @param string $emailFromAddress
     */
    public function setEmailFromAddress($emailFromAddress)
    {
        $this->emailFromAddress = (string)$emailFromAddress;
    }

    /**
     * getter for email from name.
     *
     * @throws \Exception
     */
    public function getEmailFromName(): string
    {
        if (empty($this->emailFromName)) {
            $senderName = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'];
            if (empty($senderName)) {
                throw new \Exception('You have forgotten to set a sender name in extension configuration or in install tool', 1484823661);
            } else {
                return $senderName;
            }
        } else {
            return $this->emailFromName;
        }
    }

    /**
     * setter for emailFromName.
     *
     * @param string $emailFromName
     */
    public function setEmailFromName($emailFromName)
    {
        $this->emailFromName = (string)$emailFromName;
    }

    /**
     * getter for email to address.
     */
    public function getEmailToAddress(): string
    {
        return $this->emailToAddress;
    }

    /**
     * setter for email to address.
     *
     * @param string $emailToAddress
     */
    public function setEmailToAddress($emailToAddress)
    {
        $this->emailToAddress = (string)$emailToAddress;
    }

    /**
     * getter for email to name.
     */
    public function getEmailToName(): string
    {
        return $this->emailToName;
    }

    /**
     * setter for emailToName.
     *
     * @param string $emailToName
     */
    public function setEmailToName($emailToName)
    {
        $this->emailToName = (string)$emailToName;
    }
}
