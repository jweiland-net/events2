<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Updater;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/*
 * Updater to migrate column detail_informations to detail_information
 */
class MigrateDetailInformationsUpdater implements UpgradeWizardInterface
{
    /**
     * Return the identifier for this wizard
     * This should be the same string as used in the ext_localconf class registration
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'events2MigrateDetailInformations';
    }

    public function getTitle(): string
    {
        return '[events2] Migrate column detail_informations';
    }

    public function getDescription(): string
    {
        return 'Migrate detail_informations to detail_information';
    }

    public function updateNecessary(): bool
    {
        $queryBuilder = $this->getQueryBuilder();
        $schemaManager = $queryBuilder->getConnection()->getSchemaManager();
        if ($schemaManager === null) {
            return false;
        }

        $columns = array_keys($schemaManager->listTableColumns('tx_events2_domain_model_event'));
        if (!in_array('detail_informations', $columns, true)) {
            return false;
        }

        $amountOfMigratedRecords = (int)$queryBuilder
            ->count('*')
            ->where(
                $queryBuilder->expr()->isNotNull(
                    'e.detail_informations'
                ),
                $queryBuilder->expr()->neq(
                    'e.detail_informations',
                    $queryBuilder->quoteIdentifier('e.detail_information')
                )
            )
            ->execute()
            ->fetchColumn();

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
            ->select('e.uid', 'e.detail_informations')
            ->execute();

        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_event');
        while ($event = $statement->fetch()) {
            $connection->update(
                'tx_events2_domain_model_event',
                [
                    'detail_information' => $event['detail_informations']
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

        return $queryBuilder->from('tx_events2_domain_model_event', 'e');
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
