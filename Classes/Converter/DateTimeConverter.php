<?php
namespace JWeiland\Events2\Converter;

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
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DateTimeConverter {

	/**
	 * Creates a DateTime from an unix timestamp or date/datetime value.
	 * If the input is empty, NULL is returned.
	 *
	 * @param integer|string $value Unix timestamp or date/datetime value
	 * @param NULL|string $storageFormat Storage format for native date/datetime fields
	 * @return \DateTime
	 */
	static public function convert($value, $storageFormat = NULL) {
		if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
			// 0 -> NULL !!!
			return NULL;
		} elseif ($storageFormat === 'date' || $storageFormat === 'datetime') {
			// native date/datetime values are stored in UTC
			// SF: These are the values from DB with format: 0000-00-00
			$utcTimeZone = new \DateTimeZone('UTC');
			$utcDateTime = new \DateTime($value, $utcTimeZone);
			$currentTimeZone = new \DateTimeZone(date_default_timezone_get());
			return $utcDateTime->setTimezone($currentTimeZone);
		} elseif (is_string($value) && !MathUtility::canBeInterpretedAsInteger($value)) {
			// SF: This is my own converter for modifying the date by special formatting values like "today" OR "tomorrow"
			$currentTimeZone = new \DateTimeZone(date_default_timezone_get());
			return new \DateTime($value, $currentTimeZone);
		} else {
			return new \DateTime(date('c', $value));
		}
	}

}