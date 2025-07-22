<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Repository;

use JWeiland\Events2\Traits\Typo3RequestTrait;

/**
 * This repository is not connected to the extbase system. So saving does not work.
 * It creates readOnly access to the user values in $GLOBALS.
 */
class UserRepository
{
    use Typo3RequestTrait;

    /**
     * Get currently logged-in user.
     */
    public function getUser(): array
    {
        $frontendUser = $this->getFrontendUserAuthentication();

        if (is_array($frontendUser->user) && (int)$frontendUser->user['uid'] > 0) {
            // remove password for security reasons
            unset($frontendUser->user['password']);

            return $frontendUser->user;
        }

        return [];
    }

    /**
     * Get a special field from the user.
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
