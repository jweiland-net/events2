<?php
namespace JWeiland\Events2\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Stefan Froemken <sfroemken@jweiland.net>, jweiland.net
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
class EventLocationMm extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * Event
	 *
	 * @var \JWeiland\Events2\Domain\Model\Event
	 */
	protected $event;

	/**
	 * Location
	 *
	 * @var \JWeiland\Events2\Domain\Model\Location
	 * @validate NotEmpty
	 */
	protected $location;





	/**
	 * Returns the event
	 *
	 * @return \JWeiland\Events2\Domain\Model\Event $event
	 */
	public function getEvent() {
		return $this->event;
	}

	/**
	 * Sets the event
	 *
	 * @param \JWeiland\Events2\Domain\Model\Event $event
	 * @return void
	 */
	public function setEvent(\JWeiland\Events2\Domain\Model\Event $event) {
		$this->event = $event;
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

}