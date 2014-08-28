<?php
namespace JWeiland\Events2\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Stefan Froemken <projects@jweiland.net>, jweiland.net
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
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Location extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * Location
	 *
	 * @var string
	 */
	protected $location = '';

	/**
	 * Street
	 *
	 * @var string
	 */
	protected $street = '';

	/**
	 * houseNumber
	 *
	 * @var string
	 */
	protected $houseNumber = '';

	/**
	 * zip
	 *
	 * @var string
	 */
	protected $zip = '';

	/**
	 * city
	 *
	 * @var string
	 */
	protected $city = '';

	/**
	 * txMaps2Uid
	 *
	 * @var \JWeiland\Maps2\Domain\Model\PoiCollection
	 */
	protected $txMaps2Uid = NULL;





	/**
	 * Setter for location
	 *
	 * @param string $location
	 */
	public function setLocation($location) {
		$this->location = (string)$location;
	}

	/**
	 * Getter for location
	 *
	 * @return string
	 */
	public function getLocation() {
		return $this->location;
	}

	/**
	 * Setter for street
	 *
	 * @param string $street
	 */
	public function setStreet($street) {
		$this->street = (string)$street;
	}

	/**
	 * Getter for street
	 *
	 * @return string
	 */
	public function getStreet() {
		return $this->street;
	}

	/**
	 * Setter for houseNumber
	 *
	 * @param string $houseNumber
	 */
	public function setHouseNumber($houseNumber) {
		$this->houseNumber = (string)$houseNumber;
	}

	/**
	 * Getter for houseNumber
	 *
	 * @return string
	 */
	public function getHouseNumber() {
		return $this->houseNumber;
	}

	/**
	 * Setter for zip
	 *
	 * @param string $zip
	 */
	public function setZip($zip) {
		$this->zip = (string)$zip;
	}

	/**
	 * Getter for zip
	 *
	 * @return string
	 */
	public function getZip() {
		return $this->zip;
	}

	/**
	 * Setter for City
	 *
	 * @param string $city
	 */
	public function setCity($city) {
		$this->city = (string)$city;
	}

	/**
	 * Getter for City
	 *
	 * @return string
	 */
	public function getCity() {
		return $this->city;
	}

	/**
	 * Setter for txMaps2Uid
	 *
	 * @param \JWeiland\Maps2\Domain\Model\PoiCollection $txMaps2Uid
	 */
	public function setTxMaps2Uid(\JWeiland\Maps2\Domain\Model\PoiCollection $txMaps2Uid) {
		$this->txMaps2Uid = $txMaps2Uid;
	}

	/**
	 * Getter for TxMaps2Uid
	 *
	 * @return \JWeiland\Maps2\Domain\Model\PoiCollection
	 */
	public function getTxMaps2Uid() {
		return $this->txMaps2Uid;
	}

}