<?php

namespace JWeiland\Events2\Domain\Repository;
    
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
