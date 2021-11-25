<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Repository;

/*
 * This repository is not connected to the extbase system. So saving does not work.
 * It creates readOnly access to the user values in $GLOBALS.
 */
class UserRepository
{
    /**
     * Get currently logged-in user.
     */
    public function getUser(): array
    {
        if (is_array($GLOBALS['TSFE']->fe_user->user) && (int)$GLOBALS['TSFE']->fe_user->user['uid'] > 0) {
            // remove password for security reasons
            unset($GLOBALS['TSFE']->fe_user->user['password']);

            return $GLOBALS['TSFE']->fe_user->user;
        }

        return [];
    }

    /**
     * Get a special field from user.
     *
     * @param string $field Return only the specified field instead of full user data
     * @return string The requested user data
     */
    public function getFieldFromUser(string $field): string
    {
        $user = $this->getUser();
        if (isset($user[$field])) {
            return (string)$user[$field];
        }

        return '';
    }
}
