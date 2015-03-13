<?php
namespace JWeiland\Events2\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Stefan Froemken <projects@jweiland.net>, jweiland.net
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
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AddDateTimeDiffViewHelper extends AbstractViewHelper {

	/**
	 * implements a vievHelper which calculates the difference between FROM and TO and add the DIFF to the given date
	 *
	 * @param \DateTime $day The Day to add the difference to
	 * @param \DateTime $from The date FROM
	 * @param mixed $to The date TO
	 * @return string
	 */
	public function render(\DateTime $day, \DateTime $from, $to) {
		// then and else parts will be parsed before if condition was called. This is in my kind of view a bug: http://forge.typo3.org/issues/49292
		// But eventEnd is not a required event property, but it is a required property here
		// So, if this viewHelper was called within an if-part, that is not true, it could be that $to is null.
		// Thats why we have to check this here before further processing
		if ($to instanceof \DateTime) {
			$clonedDay = clone $day;
			$diff = $from->diff($to);
			return $clonedDay->add($diff);
		} else {
			return NULL;
		}
	}

}