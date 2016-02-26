<?php

namespace JWeiland\Events2\Domain\Repository;

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

/**
 * This repository is not connected to the extbase system. So saving does not work
 * It is a read only access to the user data in $GLOBALS.
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class UserRepository
{
    /**
     * get currently logged in user.
     *
     * @return array The requested user data
     */
    public function getUser()
    {
        if (is_array($GLOBALS['TSFE']->fe_user->user) && (int) $GLOBALS['TSFE']->fe_user->user['uid'] > 0) {
            // remove password for security reasons
            unset($GLOBALS['TSFE']->fe_user->user['password']);

            return $GLOBALS['TSFE']->fe_user->user;
        } else {
            return array();
        }
    }

    /**
     * get a special field from user.
     *
     * @param string $field Return only the specified field instead of full user data
     *
     * @return string The requested user data
     */
    public function getFieldFromUser($field)
    {
        $user = $this->getUser();
        if (isset($user[$field])) {
            return (string) $user[$field];
        } else {
            return '';
        }
    }
}
