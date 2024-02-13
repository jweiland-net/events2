<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\EventListener;

use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Event\PreProcessControllerActionEvent;
use JWeiland\Events2\Helper\HiddenObjectHelper;

class RegisterHiddenEventEventListener extends AbstractControllerEventListener
{
    protected array $allowedControllerActions = [
        'Management' => [
            'edit',
            'update',
        ],
    ];

    public function __construct(
        protected readonly HiddenObjectHelper $hiddenObjectHelper,
        protected readonly EventRepository $eventRepository
    ) {}

    public function __invoke(PreProcessControllerActionEvent $controllerActionEvent): void
    {
        if ($this->isValidRequest($controllerActionEvent)) {
            $this->hiddenObjectHelper->registerHiddenObjectInExtbaseSession(
                $this->eventRepository,
                $controllerActionEvent->getRequest(),
                'event'
            );
        }
    }
}
