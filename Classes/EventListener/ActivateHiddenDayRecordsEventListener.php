<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\EventListener;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Event\PostProcessFluidVariablesEvent;
use JWeiland\Events2\Traits\IsValidEventListenerRequestTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * After activating an event via the link of activation mail (frontend created events) just the event itself
 * will be activated while the day records are still hidden. We can't loop through $event->getDays() as at that point
 * the day records are still hidden and are not found by the internals of DayRepository.
 *
 * This EventListener will find these hidden day records and activates them.
 */
#[AsEventListener('events2/activateHiddenDayRecords')]
final readonly class ActivateHiddenDayRecordsEventListener
{
    use IsValidEventListenerRequestTrait;

    protected const ALLOWED_CONTROLLER_ACTIONS = [
        'Management' => [
            'activate',
        ],
    ];

    public function __construct(readonly private ConnectionPool $connectionPool) {}

    public function __invoke(PostProcessFluidVariablesEvent $controllerActionEvent): void
    {
        if (!$this->isValidRequest($controllerActionEvent)) {
            return;
        }

        $event = $controllerActionEvent->getFluidVariables()['event'] ?? null;
        if (!$event instanceof Event) {
            return;
        }

        $this->getConnectionForDay()->update(
            'tx_events2_domain_model_day',
            [
                'hidden' => 0,
            ],
            [
                'event' => $event->getUid(),
            ],
            [
                Connection::PARAM_INT,
            ],
        );
    }

    private function getConnectionForDay(): Connection
    {
        return $this->connectionPool->getConnectionForTable('tx_events2_domain_model_day');
    }
}
