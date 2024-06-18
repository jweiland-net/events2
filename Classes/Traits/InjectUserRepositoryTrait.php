<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Traits;

use JWeiland\Events2\Domain\Repository\UserRepository;

/**
 * Trait to inject UserRepository. Mostly used in controllers.
 */
trait InjectUserRepositoryTrait
{
    protected UserRepository $userRepository;

    public function injectUserRepository(UserRepository $userRepository): void
    {
        $this->userRepository = $userRepository;
    }
}
