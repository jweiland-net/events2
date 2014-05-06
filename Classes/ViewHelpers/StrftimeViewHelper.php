<?php
namespace JWeiland\Events2\ViewHelpers;

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
class StrftimeViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * implements a ViewHelper to convert seconds since 0:00 to a readable format
	 *
	 * @param string $format How to format the date
	 * @param string $locale set_locale
	 * @return string
	 */
	public function render($format = '%d.%m.%Y', $locale = 'de_DE.UTF-8') {
		$date = $this->renderChildren();
		setlocale(LC_TIME, $locale);
		if ($date instanceof \DateTime) {
			return strftime($format, $date->format('U'));
		} else return '';
	}

}