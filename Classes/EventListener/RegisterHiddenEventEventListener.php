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
    protected HiddenObjectHelper $hiddenObjectHelper;

    protected EventRepository $eventRepository;

    protected array $allowedControllerActions = [
        'Management' => [
            'edit',
            'update'
        ]
    ];

    public function __construct(
        HiddenObjectHelper $hiddenObjectHelper,
        EventRepository $eventRepository
    ) {
        $this->hiddenObjectHelper = $hiddenObjectHelper;
        $this->eventRepository = $eventRepository;
    }

    public function __invoke(PreProcessControllerActionEvent $event): void
    {
        if ($this->isValidRequest($event)) {
            $this->hiddenObjectHelper->registerHiddenObjectInExtbaseSession(
                $this->eventRepository,
                $event->getRequest(),
                'event'
            );
        }
    }
}
