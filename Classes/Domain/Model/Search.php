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
class Search extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * Searchword
	 *
	 * @var string
	 */
	protected $search = '';

	/**
	 * mainCategory
	 *
	 * @var \JWeiland\Events2\Domain\Model\Category
	 */
	protected $mainCategory;

	/**
	 * subCategory
	 *
	 * @var \JWeiland\Events2\Domain\Model\Category
	 */
	protected $subCategory;

	/**
	 * eventBegin
	 *
	 * @var string
	 */
	protected $eventBegin;

	/**
	 * eventEnd
	 *
	 * @var string
	 */
	protected $eventEnd;

	/**
	 * location
	 *
	 * @var \JWeiland\Events2\Domain\Model\Location
	 */
	protected $location;

	/**
	 * freeEntry
	 *
	 * @var boolean
	 */
	protected $freeEntry = FALSE;





	/**
	 * Returns the search
	 *
	 * @return string $search
	 */
	public function getSearch() {
		return $this->search;
	}

	/**
	 * Sets the search
	 *
	 * @param string $search
	 * @return void
	 */
	public function setSearch($search) {
		$this->search = htmlspecialchars((string)$search);
	}

	/**
	 * Returns the mainCategory
	 *
	 * @return \JWeiland\Events2\Domain\Model\Category $mainCategory
	 */
	public function getMainCategory() {
		return $this->mainCategory;
	}

	/**
	 * Sets the mainCategory
	 *
	 * @param \JWeiland\Events2\Domain\Model\Category $mainCategory
	 * @return void
	 */
	public function setMainCategory(\JWeiland\Events2\Domain\Model\Category $mainCategory = NULL) {
		$this->mainCategory = $mainCategory;
	}

	/**
	 * Returns the subCategory
	 *
	 * @return \JWeiland\Events2\Domain\Model\Category $subCategory
	 */
	public function getSubCategory() {
		return $this->subCategory;
	}

	/**
	 * Sets the subCategory
	 *
	 * @param \JWeiland\Events2\Domain\Model\Category $subCategory
	 * @return void
	 */
	public function setSubCategory(\JWeiland\Events2\Domain\Model\Category $subCategory = NULL) {
		$this->subCategory = $subCategory;
	}

	/**
	 * Returns the eventBegin
	 *
	 * @return \DateTime $eventBegin
	 */
	public function getEventBegin() {
		if (empty($this->eventBegin)) return NULL;
		try {
			$date = new \DateTime($this->eventBegin);
			$date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
		} catch (\Exception $exception) {
			$date = NULL;
		}
		return $date;
	}

	/**
	 * Sets the eventBegin
	 *
	 * @param string $eventBegin
	 * @return void
	 */
	public function setEventBegin($eventBegin) {
		$this->eventBegin = $eventBegin;
	}

	/**
	 * Returns the eventEnd
	 *
	 * @return \DateTime $eventEnd
	 */
	public function getEventEnd() {
		if (empty($this->eventEnd)) return NULL;
		try {
			$date = new \DateTime($this->eventEnd);
			$date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
		} catch (\Exception $exception) {
			$date = NULL;
		}
		return $date;
	}

	/**
	 * Sets the eventEnd
	 *
	 * @param string $eventEnd
	 * @return void
	 */
	public function setEventEnd($eventEnd) {
		$this->eventEnd = $eventEnd;
	}

	/**
	 * Returns the location
	 *
	 * @return \JWeiland\Events2\Domain\Model\Location $location
	 */
	public function getLocation() {
		return $this->location;
	}

	/**
	 * Sets the location
	 *
	 * @param \JWeiland\Events2\Domain\Model\Location $location
	 * @return void
	 */
	public function setLocation(\JWeiland\Events2\Domain\Model\Location $location = NULL) {
		$this->location = $location;
	}

	/**
	 * Returns the freeEntry
	 *
	 * @return boolean $freeEntry
	 */
	public function getFreeEntry() {
		return $this->freeEntry;
	}

	/**
	 * Sets the freeEntry
	 *
	 * @param boolean $freeEntry
	 * @return void
	 */
	public function setFreeEntry($freeEntry) {
		$this->freeEntry = (bool) $freeEntry;
	}

}