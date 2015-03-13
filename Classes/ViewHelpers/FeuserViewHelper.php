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
class FeuserViewHelper extends AbstractViewHelper {

	/**
	 * implements a vievHelper to get values from current logged in fe_user
	 *
	 * @param string $field Field to retrieve value from
	 * @return string
	 */
	public function render($field = 'uid') {
		// do not return user password for security resons
		if ($field === 'password') return '';

		// return field of user array
		if (is_array($GLOBALS['TSFE']->fe_user->user) && (integer) $GLOBALS['TSFE']->fe_user->user['uid'] > 0) {
			return $GLOBALS['TSFE']->fe_user->user[$field];
		} else {
			return '';
		}
	}

}