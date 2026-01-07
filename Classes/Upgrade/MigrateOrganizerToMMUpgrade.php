<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Upgrade;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Updater to migrate organizer into MM table
 */
#[UpgradeWizard('events2_migrateOrganizer')]
class MigrateOrganizerToMMUpgrade implements UpgradeWizardInterface
{
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

        try {
            $schemaManager = $queryBuilder->getConnection()->createSchemaManager();
        } catch (Exception $e) {
            return false;
        }

        if (!array_key_exists('organizer', $schemaManager->listTableColumns('tx_events2_domain_model_event'))) {
            return false;
        }

        $amountOfMigratedRecords = (int)$queryBuilder
            ->count('*')
            ->innerJoin(
                'e',
                'tx_events2_event_organizer_mm',
                'eo_mm',
                $queryBuilder->expr()->eq(
                    'e.organizer',
                    $queryBuilder->quoteIdentifier('eo_mm.uid_local'),
                ),
            )
            ->executeQuery()
            ->fetchOne();

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

        $queryResult = $queryBuilder
            ->select('e.uid', 'e.organizer')
            ->executeQuery();

        $mmConnection = $this->getConnectionPool()->getConnectionForTable('tx_events2_event_organizer_mm');
        $eventConnection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_event');
        while ($event = $queryResult->fetchAssociative()) {
            $mmConnection->insert(
                'tx_events2_event_organizer_mm',
                [
                    'uid_local' => (int)$event['uid'],
                    'uid_foreign' => (int)$event['organizer'],
                    'sorting' => 1,
                    'sorting_foreign' => 0,
                ],
            );
            // event->organizer was an 1:1 relation, so organizer = 1 should be OK here
            $eventConnection->update(
                'tx_events2_domain_model_event',
                [
                    'organizers' => 1,
                ],
                [
                    'uid' => (int)$event['uid'],
                ],
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
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                ),
            );
    }

    /**
     * @return array<class-string<DatabaseUpdatedPrerequisite>>
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
