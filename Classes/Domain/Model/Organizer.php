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
class Organizer extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * Organizer
	 *
	 * @var string
	 */
	protected $organizer = '';

	/**
	 * Link
	 *
	 * @var \JWeiland\Events2\Domain\Model\Link
	 */
	protected $link = NULL;





	/**
	 * Returns the organizer
	 *
	 * @return string $organizer
	 */
	public function getOrganizer() {
		return $this->organizer;
	}

	/**
	 * Sets the organizer
	 *
	 * @param string $organizer
	 * @return void
	 */
	public function setOrganizer($organizer) {
		$this->organizer = (string)$organizer;
	}

	/**
	 * Returns the link
	 *
	 * @return \JWeiland\Events2\Domain\Model\Link $link
	 */
	public function getLink() {
		return $this->link;
	}

	/**
	 * Sets the link
	 *
	 * @param \JWeiland\Events2\Domain\Model\Link $link
	 * @return void
	 */
	public function setLink(\JWeiland\Events2\Domain\Model\Link $link) {
		$this->link = $link;
	}

}