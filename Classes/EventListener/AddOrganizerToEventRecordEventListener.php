<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\EventListener;

use JWeiland\Events2\Domain\Repository\UserRepository;
use JWeiland\Events2\Event\PreProcessControllerActionEvent;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;

/**
 * Add organizer to event record manually.
 *
 * As it is possible for a hacker to change the user/organizer in source code of event form (FE)
 * I prefer adding the organizer manually here in PHP
 */
class AddOrganizerToEventRecordEventListener extends AbstractControllerEventListener
{
    protected UserRepository $userRepository;

    protected array $allowedControllerActions = [
        'Management' => [
            'create'
        ]
    ];

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function __invoke(PreProcessControllerActionEvent $event): void
    {
        if (
            $this->isValidRequest($event)
            && $event->getRequest()->hasArgument('event')
            && ($eventRecord = $event->getRequest()->getArgument('event'))
            && is_array($eventRecord)
            && !array_key_exists('organizers', $eventRecord)
            && ($organizerOfCurrentUser = $this->userRepository->getFieldFromUser('tx_events2_organizer'))
            && MathUtility::canBeInterpretedAsInteger($organizerOfCurrentUser)
        ) {
            $this->addOrganizerToEventRecord($eventRecord, (int)$organizerOfCurrentUser, $event);

            // Per default it is not allowed to add new arguments manually. So we have to register them.
            $pmc = $event->getArguments()
                ->getArgument('event')
                ->getPropertyMappingConfiguration();

            // Allow mapping of organizers
            $pmc->allowProperties('organizers');

            // Allow creation of new organizers
            $pmc->forProperty('organizers.*')
                ->setTypeConverterOption(
                    PersistentObjectConverter::class,
                    PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
                    true
                );
        }
    }

    protected function addOrganizerToEventRecord(
        array &$eventRecord,
        int $organizerUid,
        PreProcessControllerActionEvent $event
    ): void {
        $eventRecord['organizers'] = [];
        $eventRecord['organizers'][0] = $organizerUid;

        $event->getRequest()->setArgument('event', $eventRecord);
    }
}
