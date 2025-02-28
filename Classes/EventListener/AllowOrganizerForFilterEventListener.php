<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\EventListener;

use JWeiland\Events2\Event\PreProcessControllerActionEvent;
use JWeiland\Events2\Traits\IsValidEventListenerRequestTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;

/**
 * Allow filter for organizer
 *
 * Organizer is always allowed for POST requests, but for GET requests we have to explicit allow organizer
 * for PropertyMapper. As Extbase Pagination was removed and does not support POST requests anymore
 * we need to add organizer manually.
 */
#[AsEventListener('events2/allowOrganizerForFilter')]
final readonly class AllowOrganizerForFilterEventListener
{
    use IsValidEventListenerRequestTrait;

    protected const ALLOWED_CONTROLLER_ACTIONS = [
        'Day' => [
            'list',
            'listLatest',
            'listToday',
            'listThisWeek',
            'listRange',
            'listSearchResults',
        ],
    ];

    public function __invoke(PreProcessControllerActionEvent $event): void
    {
        if (
            $this->isValidRequest($event)
            && $event->getRequest()->hasArgument('filter')
        ) {
            $pmc = $event->getArguments()
                ->getArgument('filter')
                ->getPropertyMappingConfiguration();

            $pmc->setTypeConverterOption(
                PersistentObjectConverter::class,
                PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
                true,
            );

            // Allow mapping of organizers
            $pmc->allowProperties('organizer');
        }
    }
}
