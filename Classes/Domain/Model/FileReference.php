<?php
namespace JWeiland\Events2\Domain\Model;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014 Stefan Frömken <froemken@gmail.com>
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
 * FileReference
 */
class FileReference extends \TYPO3\CMS\Extbase\Domain\Model\FileReference {

	/**
	 * title
	 *
	 * @var string
	 */
	protected $title = NULL;

	/**
	 * cruserId
	 *
	 * @var int
	 */
	protected $cruserId = 0;

	/**
	 * uidLocal
	 *
	 * @var int
	 */
	protected $uidLocal = 0;

	/**
	 * tablenames
	 *
	 * @var string
	 */
	protected $tablenames = '';

	/**
	 * Returns the title
	 *
	 * @return string $title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Sets the title
	 *
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Returns the cruserId
	 *
	 * @return int $cruserId
	 */
	public function getCruserId() {
		return $this->cruserId;
	}

	/**
	 * Sets the cruserId
	 *
	 * @param int $cruserId
	 * @return void
	 */
	public function setCruserId($cruserId) {
		$this->cruserId = $cruserId;
	}

	/**
	 * Returns the uidLocal
	 *
	 * @return int $uidLocal
	 */
	public function getUidLocal() {
		return $this->uidLocal;
	}

	/**
	 * Sets the uidLocal
	 *
	 * @param int $uidLocal
	 * @return void
	 */
	public function setUidLocal($uidLocal) {
		$this->uidLocal = $uidLocal;
	}

	/**
	 * Returns the tablenames
	 *
	 * @return string $tablenames
	 */
	public function getTablenames() {
		return $this->tablenames;
	}

	/**
	 * Sets the tablenames
	 *
	 * @param string $tablenames
	 * @return void
	 */
	public function setTablenames($tablenames) {
		$this->tablenames = $tablenames;
	}

}