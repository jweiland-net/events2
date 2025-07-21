<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Traits;

use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait InsertEventTrait
{
    protected function insertEvent(
        string $title,
        \DateTimeImmutable $eventBegin,
        string $timeBegin = '',
        string $organizer = '',
    ): void {
        $eventRecord = [
            'pid' => Events2Constants::PAGE_STORAGE,
            'event_type' => 'single',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_time' => $timeBegin !== '' ? 1 : 0,
            'title' => $title,
            'organizers' => $organizer !== '' ? 1 : 0,
        ];

        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_event');
        $connection->insert(
            'tx_events2_domain_model_event',
            $eventRecord,
        );
        $eventUid = $connection->lastInsertId();

        if ($timeBegin !== '') {
            $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_time');
            $connection->insert(
                'tx_events2_domain_model_time',
                [
                    'pid' => Events2Constants::PAGE_STORAGE,
                    'type' => 'event_time',
                    'time_begin' => $timeBegin,
                    'event' => $eventUid,
                    'exception' => 0,
                ],
            );
        }

        if ($organizer !== '') {
            $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_organizer');
            $connection->insert(
                'tx_events2_domain_model_organizer',
                [
                    'pid' => Events2Constants::PAGE_STORAGE,
                    'organizer' => $organizer,
                ],
            );
            $organizerUid = $connection->lastInsertId();

            $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_event_organizer_mm');
            $connection->insert(
                'tx_events2_event_organizer_mm',
                [
                    'uid_local' => $eventUid,
                    'uid_foreign' => $organizerUid,
                ],
            );
        }
    }

    protected function createDayRelations(): void
    {
        $dayRelationService = GeneralUtility::makeInstance(DayRelationService::class);

        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_event');
        $queryResult = $connection->select(
            ['*'],
            'tx_events2_domain_model_event',
            [
                'pid' => Events2Constants::PAGE_STORAGE,
            ],
        );

        while ($eventRecord = $queryResult->fetchAssociative()) {
            $dayRelationService->createDayRelations((int)$eventRecord['uid']);
        }
    }
}
