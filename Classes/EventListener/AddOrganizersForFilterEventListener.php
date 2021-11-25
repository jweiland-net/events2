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
    protected OrganizerRepository $organizerRepository;

    protected array $allowedControllerActions = [
        'Day' => [
            'list',
            'listLatest',
            'listToday',
            'listThisWeek',
            'listRange',
        ]
    ];

    public function __construct(OrganizerRepository $organizerRepository)
    {
        $this->organizerRepository = $organizerRepository;
    }

    public function __invoke(PostProcessFluidVariablesEvent $event): void
    {
        if (!$this->isValidRequest($event)) {
            return;
        }

        if (!array_key_exists('showFilterForOrganizerInFrontend', $event->getSettings())) {
            return;
        }

        if (!$event->getSettings()['showFilterForOrganizerInFrontend'] === '1') {
            return;
        }

        $event->addFluidVariable(
            'organizers',
            $this->organizerRepository->getOrganizersForFilter()
        );
    }
}
