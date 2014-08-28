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
class Time extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * Weekday
	 *
	 * @var string
	 */
	protected $weekday = '';

	/**
	 * Time begin
	 *
	 * @var string
	 * @validate NotEmpty,RegularExpression(regularExpression=/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/)
	 */
	protected $timeBegin = '';

	/**
	 * Time entry
	 *
	 * @var string
	 * @validate RegularExpression(regularExpression=/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/)
	 */
	protected $timeEntry = '';

	/**
	 * Duration
	 *
	 * @var string
	 * @validate RegularExpression(regularExpression=/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/)
	 */
	protected $duration = '';

	/**
	 * Time end
	 *
	 * @var string
	 * @validate RegularExpression(regularExpression=/(2[0-4]{1}|[0-1]{1}\d{1}):[0-5]{1}\d{1}/)
	 */
	protected $timeEnd = '';





	/**
	 * Returns the weekday
	 *
	 * @return string $weekday
	 */
	public function getWeekday() {
		return $this->weekday;
	}

	/**
	 * Sets the weekday
	 *
	 * @param string $weekday
	 * @return void
	 */
	public function setWeekday($weekday) {
		$this->weekday = (string)$weekday;
	}

	/**
	 * Returns the timeBegin
	 *
	 * @return string $timeBegin
	 */
	public function getTimeBegin() {
		return $this->timeBegin;
	}

	/**
	 * Sets the timeBegin
	 *
	 * @param string $timeBegin
	 * @return void
	 */
	public function setTimeBegin($timeBegin) {
		$this->timeBegin = (string)$timeBegin;
	}

	/**
	 * Returns the timeEntry
	 *
	 * @return string $timeEntry
	 */
	public function getTimeEntry() {
		return $this->timeEntry;
	}

	/**
	 * Sets the timeEntry
	 *
	 * @param string $timeEntry
	 * @return void
	 */
	public function setTimeEntry($timeEntry) {
		$this->timeEntry = (string)$timeEntry;
	}

	/**
	 * Returns the duration
	 *
	 * @return string $duration
	 */
	public function getDuration() {
		return $this->duration;
	}

	/**
	 * Sets the duration
	 *
	 * @param string $duration
	 * @return void
	 */
	public function setDuration($duration) {
		$this->duration = (string)$duration;
	}

	/**
	 * Returns the timeEnd
	 *
	 * @return string $timeEnd
	 */
	public function getTimeEnd() {
		return $this->timeEnd;
	}

	/**
	 * Sets the timeEnd
	 *
	 * @param string $timeEnd
	 * @return void
	 */
	public function setTimeEnd($timeEnd) {
		$this->timeEnd = (string)$timeEnd;
	}

}