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
use JWeiland\Events2\Traits\IsValidEventListenerRequestTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;

#[AsEventListener('events2/registerHiddenEvent')]
final readonly class RegisterHiddenEventEventListener
{
    use IsValidEventListenerRequestTrait;

    protected const ALLOWED_CONTROLLER_ACTIONS = [
        'Management' => [
            'edit',
            'update',
        ],
    ];

    public function __construct(
        private HiddenObjectHelper $hiddenObjectHelper,
        private EventRepository $eventRepository,
    ) {}

    public function __invoke(PreProcessControllerActionEvent $controllerActionEvent): void
    {
        if ($this->isValidRequest($controllerActionEvent)) {
            $this->hiddenObjectHelper->registerHiddenObjectInExtbaseSession(
                $this->eventRepository,
                $controllerActionEvent->getRequest(),
                'event',
            );
        }
    }
}
