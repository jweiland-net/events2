<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\EventListener;

use JWeiland\Events2\Domain\Repository\OrganizerRepository;
use JWeiland\Events2\Event\PostProcessFluidVariablesEvent;

/**
 * Add organizer for selector in list views.
 * Selector will only be shown if there are more than 1 organizers assigned
 */
class AddOrganizersForFilterEventListener extends AbstractControllerEventListener
{
    protected array $allowedControllerActions = [
        'Day' => [
            'list',
        ],
    ];

    public function __construct(protected readonly OrganizerRepository $organizerRepository) {}

    public function __invoke(PostProcessFluidVariablesEvent $controllerActionEvent): void
    {
        if (!$this->isValidRequest($controllerActionEvent)) {
            return;
        }

        if (!array_key_exists('showFilterForOrganizerInFrontend', $controllerActionEvent->getSettings())) {
            return;
        }

        if ((int)$controllerActionEvent->getSettings()['showFilterForOrganizerInFrontend'] !== 1) {
            return;
        }

        $controllerActionEvent->addFluidVariable(
            'organizers',
            $this->organizerRepository->getOrganizersForFilter()
        );
    }
}
