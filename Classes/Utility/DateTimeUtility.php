<?php
namespace JWeiland\Events2\Utility;

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
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DateTimeUtility {

	/**
	 * Creates a DateTime from an unix timestamp or date/datetime value.
	 * If the input is empty, NULL is returned.
	 *
	 * @param integer|string $value Unix timestamp or date/datetime value
	 * @return \DateTime
	 */
	public function convert($value) {
		if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
			// 0 -> NULL !!!
			return NULL;
		} elseif (is_string($value) && !MathUtility::canBeInterpretedAsInteger($value)) {
			// SF: This is my own converter for modifying the date by special formatting values like "today" OR "tomorrow"
			$currentTimeZone = new \DateTimeZone(date_default_timezone_get());
			$date = new \DateTime($value, $currentTimeZone);
			return $this->standardizeDateTimeObject($date);
		} else {
			$date = new \DateTime(date('c', $value));
			return $this->standardizeDateTimeObject($date);
		}
	}

	/**
	 * we have our own implementation of Time
	 * That's why we change time to midnight in DateTime-Objects
	 * Further it's easier to compare DateTime-Objects
	 * Hint: This function can also be called with NULL
	 *
	 * @param \DateTime $date
	 * @return \DateTime
	 */
	public function standardizeDateTimeObject($date) {
		if ($date instanceof \DateTime) {
			$date->modify('midnight');
		}
		return $date;
	}

}