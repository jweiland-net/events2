<?php

namespace JWeiland\Events2\Domain\Model;

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
use JWeiland\Maps2\Domain\Model\PoiCollection;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * This class contains all getter and setters for a Location.
 */
class Location extends AbstractEntity
{
    /**
     * Location.
     *
     * @var string
     */
    protected $location = '';

    /**
     * Street.
     *
     * @var string
     */
    protected $street = '';

    /**
     * houseNumber.
     *
     * @var string
     */
    protected $houseNumber = '';

    /**
     * zip.
     *
     * @var string
     */
    protected $zip = '';

    /**
     * city.
     *
     * @var string
     */
    protected $city = '';

    /**
     * txMaps2Uid.
     *
     * @var \JWeiland\Maps2\Domain\Model\PoiCollection
     */
    protected $txMaps2Uid;

    /**
     * Setter for location.
     *
     * @param string $location
     */
    public function setLocation($location)
    {
        $this->location = (string)$location;
    }

    /**
     * Getter for location.
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Setter for street.
     *
     * @param string $street
     */
    public function setStreet($street)
    {
        $this->street = (string)$street;
    }

    /**
     * Getter for street.
     *
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Setter for houseNumber.
     *
     * @param string $houseNumber
     */
    public function setHouseNumber($houseNumber)
    {
        $this->houseNumber = (string)$houseNumber;
    }

    /**
     * Getter for houseNumber.
     *
     * @return string
     */
    public function getHouseNumber()
    {
        return $this->houseNumber;
    }

    /**
     * Setter for zip.
     *
     * @param string $zip
     */
    public function setZip($zip)
    {
        $this->zip = (string)$zip;
    }

    /**
     * Getter for zip.
     *
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Setter for City.
     *
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = (string)$city;
    }

    /**
     * Getter for City.
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Setter for txMaps2Uid.
     *
     * @param PoiCollection $txMaps2Uid
     */
    public function setTxMaps2Uid(PoiCollection $txMaps2Uid)
    {
        $this->txMaps2Uid = $txMaps2Uid;
    }

    /**
     * Getter for TxMaps2Uid.
     *
     * @return \JWeiland\Maps2\Domain\Model\PoiCollection
     */
    public function getTxMaps2Uid()
    {
        return $this->txMaps2Uid;
    }
}
