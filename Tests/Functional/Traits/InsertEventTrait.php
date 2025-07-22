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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait InsertEventTrait
{
    protected function insertEvent(
        string $title,
        \DateTimeImmutable $eventBegin,
        string $timeBegin = '',
        string $ticketLink = '',
        string $videoLink = '',
        array $additionalFields = [],
        string $organizer = '',
        string $organizerLink = '',
        string $location = '',
        array $categories = [],
    ): void {
        $locationUid = 0;
        if ($location !== '') {
            $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_location');
            $connection->insert(
                'tx_events2_domain_model_location',
                [
                    'pid' => Events2Constants::PAGE_STORAGE,
                    'location' => $location,
                    'street' => 'Echterdinger Straße',
                    'house_number' => '57',
                    'zip' => '70794',
                    'city' => 'Filderstadt',
                ],
            );
            $locationUid = (int)$connection->lastInsertId();
        }

        $ticketLinkUid = 0;
        if ($ticketLink) {
            $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_link');
            $connection->insert(
                'tx_events2_domain_model_link',
                [
                    'pid' => Events2Constants::PAGE_STORAGE,
                    'title' => 'Ticket Link',
                    'link' => $ticketLink,
                ],
            );
            $ticketLinkUid = (int)$connection->lastInsertId();
        }

        $videoLinkUid = 0;
        if ($videoLink) {
            $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_link');
            $connection->insert(
                'tx_events2_domain_model_link',
                [
                    'pid' => Events2Constants::PAGE_STORAGE,
                    'title' => 'Video Link',
                    'link' => $videoLink,
                ],
            );
            $videoLinkUid = (int)$connection->lastInsertId();
        }

        $eventRecord = [
            'pid' => Events2Constants::PAGE_STORAGE,
            'event_type' => 'single',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_time' => $timeBegin !== '' ? 1 : 0,
            'title' => $title,
            'organizers' => $organizer !== '' ? 1 : 0,
            'location' => $locationUid,
            'ticket_link' => $ticketLinkUid,
            'video_link' => $videoLinkUid,
        ];

        ArrayUtility::mergeRecursiveWithOverrule($eventRecord, $additionalFields);

        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_event');
        $connection->insert(
            'tx_events2_domain_model_event',
            $eventRecord,
        );
        $eventUid = (int)$connection->lastInsertId();

        if ($timeBegin !== '') {
            $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_time');
            $connection->insert(
                'tx_events2_domain_model_time',
                [
                    'pid' => Events2Constants::PAGE_STORAGE,
                    'type' => 'event_time',
                    'time_begin' => $timeBegin,
                    'time_entry' => '19:00',
                    'duration' => '02:00',
                    'time_end' => '22:00',
                    'event' => $eventUid,
                    'exception' => 0,
                ],
            );
        }

        if ($organizer !== '') {
            $organizerLinkUid = 0;
            if ($organizerLink) {
                $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_link');
                $connection->insert(
                    'tx_events2_domain_model_link',
                    [
                        'pid' => Events2Constants::PAGE_STORAGE,
                        'title' => 'Organizer Link',
                        'link' => $organizerLink,
                    ],
                );
                $organizerLinkUid = (int)$connection->lastInsertId();
            }

            $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_organizer');
            $connection->insert(
                'tx_events2_domain_model_organizer',
                [
                    'pid' => Events2Constants::PAGE_STORAGE,
                    'organizer' => $organizer,
                    'link' => $organizerLinkUid,
                ],
            );
            $organizerUid = (int)$connection->lastInsertId();

            $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_event_organizer_mm');
            $connection->insert(
                'tx_events2_event_organizer_mm',
                [
                    'uid_local' => $eventUid,
                    'uid_foreign' => $organizerUid,
                ],
            );
        }

        if ($categories !== []) {
            foreach ($categories as $category) {
                $connection = $this->getConnectionPool()->getConnectionForTable('sys_category_record_mm');
                $connection->insert(
                    'sys_category_record_mm',
                    [
                        'uid_local' => $category,
                        'uid_foreign' => $eventUid,
                        'tablenames' => 'tx_events2_domain_model_event',
                        'fieldname' => 'categories',
                    ],
                );
            }
        }
    }

    protected function createDayRelations(int $workspace = 0, bool $isAdmin = true): void
    {
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->user['username'] = 'acceptanceTestSetup';
        $GLOBALS['BE_USER']->user['admin'] = (int)$isAdmin;
        $GLOBALS['BE_USER']->user['uid'] = 1;
        $GLOBALS['BE_USER']->workspace = $workspace;

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
