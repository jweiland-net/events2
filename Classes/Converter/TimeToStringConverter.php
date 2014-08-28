<?php
namespace JWeiland\Events2\Converter;

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
class TimeToStringConverter {

	/**
	 * a method to convert a timestamp to a readable time format like: 21:35
	 * maximum value is 23:59
	 *
	 * @param integer $timestamp Timestamp to convert
	 * @return string
	 */
	public function convert($timestamp) {
		$time = '';
		if (is_integer($timestamp)) {
			if ($timestamp >= (60 * 60 * 24)) {
				// return highest allowed value: 23:59 if timestamp is too high
				$time = '23:59';
			} elseif ($timestamp <= 0) {
				// return minimum allowed value: 00:00 if timestamp is too low
				$time = '00:00';
			} else {
				$hours = $this->getHours($timestamp);
				$minutes = $this->getRemainingMinutes($timestamp, $hours);
				$time = str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT);
			}
		}
		return $time;
	}

	/**
	 * this method rounds down (floor) the contained hours in $time
	 * Hint: Can also return 0. Be careful with this result (division by zero)
	 *
	 * @param integer $time
	 * @return float
	 */
	protected function getHours($time) {
		return floor((int) $time / 3600);
	}

	/**
	 * Extracts the minutes from $time
	 * Example:
	 * 33.300 Seconds / 3.600 = 9,25 hours
	 * 9 * 3.600 = 32.400
	 * 33.300 - 32.400 = 900 seconds remaining
	 * 900 / 60 = 15 minutes
	 *
	 * @param integer $time seconds since midnight
	 * @param float $hours
	 * @return integer remaining minutes
	 */
	protected function getRemainingMinutes($time, $hours) {
		$seconds = $hours === (float)0 ? $time : $time % ($hours * 3600);
		if ($seconds) {
			$minutes = floor($seconds / 60);
		} else $minutes = 0;
		return $minutes;
	}
}