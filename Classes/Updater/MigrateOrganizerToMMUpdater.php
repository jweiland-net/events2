<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Updater;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/*
 * Updater to migrate organizer into MM table
 */
class MigrateOrganizerToMMUpdater implements UpgradeWizardInterface
{
    /**
     * Return the identifier for this wizard
     * This should be the same string as used in the ext_localconf class registration
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'events2MigrateOrganizer';
    }

    public function getTitle(): string
    {
        return '[events2] Migrate organizer';
    }

    public function getDescription(): string
    {
        return 'Migrate organizer records into new mm-table';
    }

    public function updateNecessary(): bool
    {
        $queryBuilder = $this->getQueryBuilder();

        $amountOfMigratedRecords = (int)$queryBuilder
            ->count('*')
            ->innerJoin(
                'e',
                'tx_events2_event_organizer_mm',
                'eo_mm',
                $queryBuilder->expr()->eq(
                    'e.organizer',
                    $queryBuilder->quoteIdentifier('eo_mm.uid_local')
                )
            )
            ->execute()
            ->fetchColumn(0);

        return $amountOfMigratedRecords === 0;
    }

    /**
     * Performs the accordant updates.
     *
     * @return bool Whether everything went smoothly or not
     */
    public function executeUpdate(): bool
    {
        $queryBuilder = $this->getQueryBuilder();

        $statement = $queryBuilder
            ->select('e.uid', 'e.organizer')
            ->execute();

        $mmConnection = $this->getConnectionPool()->getConnectionForTable('tx_events2_event_organizer_mm');
        $eventConnection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_event');
        while ($event = $statement->fetch()) {
            $mmConnection->insert(
                'tx_events2_event_organizer_mm',
                [
                    'uid_local' => (int)$event['uid'],
                    'uid_foreign' => (int)$event['organizer'],
                    'sorting' => 1,
                    'sorting_foreign' => 0
                ]
            );
            // event->organizer was an 1:1 relation, so organizer = 1 should be OK here
            $eventConnection->update(
                'tx_events2_domain_model_event',
                [
                    'organizers' => 1
                ],
                [
                    'uid' => (int)$event['uid']
                ]
            );
        }

        return true;
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_event');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->from('tx_events2_domain_model_event', 'e')
            ->where(
                $queryBuilder->expr()->gt(
                    'e.organizer',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_STR)
                )
            );
    }

    /**
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
