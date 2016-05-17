<?php

namespace JWeiland\Events2\Configuration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Stefan Froemken <projects@jweiland.net>, jweiland.net
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ExtConf implements \TYPO3\CMS\Core\SingletonInterface
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

    /**
     * Returns the poiCollectionPid
     *
     * @return int $poiCollectionPid
     */
    public function getPoiCollectionPid()
    {
        return $this->poiCollectionPid;
    }

    /**
     * Sets the poiCollectionPid
     *
     * @param int $poiCollectionPid
     * @return void
     */
    public function setPoiCollectionPid($poiCollectionPid)
    {
        $this->poiCollectionPid = (int)$poiCollectionPid;
    }

    /**
     * getter for rootUid.
     *
     * @return int
     */
    public function getRootUid()
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
     *
     * @return int
     */
    public function getRecurringPast()
    {
        if (empty($this->recurringPast)) {
            return 3;
        } else {
            return $this->recurringPast;
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
     *
     * @return int
     */
    public function getRecurringFuture()
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
     * getter for email from address.
     *
     * @throws \Exception
     *
     * @return string
     */
    public function getEmailFromAddress()
    {
        if (empty($this->emailFromAddress)) {
            $senderMail = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
            if (empty($senderMail)) {
                throw new \Exception('You have forgotten to set a sender email address in extension configuration or in install tool');
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
     *
     * @return string
     */
    public function getEmailFromName()
    {
        if (empty($this->emailFromName)) {
            $senderName = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'];
            if (empty($senderName)) {
                throw new \Exception('You have forgotten to set a sender name in extension configuration or in install tool');
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
     *
     * @return string
     */
    public function getEmailToAddress()
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
     *
     * @return string
     */
    public function getEmailToName()
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
