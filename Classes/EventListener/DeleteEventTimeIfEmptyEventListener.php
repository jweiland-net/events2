<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\EventListener;

use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Event\PostProcessControllerActionEvent;

/*
 * As extbase can not automatically remove a related property if it is empty, we have to remove
 * Time model on our own.
 */
class DeleteEventTimeIfEmptyEventListener extends AbstractControllerEventListener
{
    protected array $allowedControllerActions = [
        'Management' => [
            'create',
            'update'
        ]
    ];

    public function __invoke(PostProcessControllerActionEvent $controllerActionEvent): void
    {
        if (
            $this->isValidRequest($controllerActionEvent)
            && ($eventObject = $controllerActionEvent->getEvent())
            && $eventObject->getEventTime() instanceof Time
            && $eventObject->getEventTime()->getTimeBegin() === ''
        ) {
            $eventObject->setEventTime(null);
        }
    }
}
