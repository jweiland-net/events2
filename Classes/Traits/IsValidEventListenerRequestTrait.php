<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Traits;

use JWeiland\Events2\Event\ControllerActionEventInterface;

/**
 * Trait to validate, if incoming request is valid for specific EventListener
 */
trait IsValidEventListenerRequestTrait
{
    protected function isValidRequest(ControllerActionEventInterface $event): bool
    {
        return
            array_key_exists($event->getControllerName(), self::ALLOWED_CONTROLLER_ACTIONS)
            && in_array(
                $event->getActionName(),
                self::ALLOWED_CONTROLLER_ACTIONS[$event->getControllerName()],
                true,
            );
    }
}
