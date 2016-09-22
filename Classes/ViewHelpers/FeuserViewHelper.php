<?php

namespace JWeiland\Events2\ViewHelpers;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FeuserViewHelper extends AbstractViewHelper
{
    /**
     * implements a vievHelper to get values from current logged in fe_user.
     *
     * @param string $field Field to retrieve value from
     *
     * @return string
     */
    public function render($field = 'uid')
    {
        // do not return user password for security resons
        if ($field === 'password') {
            return '';
        }

        // return field of user array
        if (is_array($GLOBALS['TSFE']->fe_user->user) && (int)$GLOBALS['TSFE']->fe_user->user['uid'] > 0) {
            return $GLOBALS['TSFE']->fe_user->user[$field];
        } else {
            return '';
        }
    }
}
