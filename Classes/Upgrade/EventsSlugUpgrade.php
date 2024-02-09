<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Upgrade;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Helper\PathSegmentHelper;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Updater to fill empty slug columns of event records
 */
class EventsSlugUpgrade implements UpgradeWizardInterface
{
    protected string $tableName = 'tx_events2_domain_model_event';

    protected string $slugColumn = 'path_segment';

    protected string $titleColumn = 'title';

    /**
     * Cache to boost incrementation of slugs
     */
    protected array $slugCache = [];

    public function __construct(
        protected readonly PathSegmentHelper $pathSegmentHelper,
        protected readonly ExtConf $extConf
    ) {
    }

    /**
     * Return the identifier for this wizard
     * This should be the same string as used in the ext_localconf class registration
     */
    public function getIdentifier(): string
    {
        return 'events2UpdateSlug';
    }

    public function getTitle(): string
    {
        return 'Update Slug of events2 records';
    }

    public function getDescription(): string
    {
        return 'Update empty slug column "path_segment" of events2 records with an URI compatible version of the title';
    }

    public function updateNecessary(): bool
    {
        if ($this->extConf->getPathSegmentType() === 'empty') {
            return false;
        }

        $queryBuilder = $this->getQueryBuilder();
        $amountOfRecordsWithEmptySlug = $queryBuilder
            ->count('*')
            ->executeQuery()
            ->fetchOne();

        return (bool)$amountOfRecordsWithEmptySlug;
    }

    /**
     * Performs the accordant updates.
     *
     * @return bool Whether everything went smoothly or not
     */
    public function executeUpdate(): bool
    {
        if ($this->extConf->getPathSegmentType() === 'empty') {
            return false;
        }

        $queryBuilder = $this->getQueryBuilder();
        $queryResult = $queryBuilder
            ->select('uid', 'pid', $this->titleColumn)
            ->executeQuery();

        $connection = $this->getConnectionPool()->getConnectionForTable($this->tableName);
        while ($recordToUpdate = $queryResult->fetchAssociative()) {
            if ((string)$recordToUpdate[$this->titleColumn] !== '') {
                $connection->update(
                    $this->tableName,
                    [
                        $this->slugColumn => $this->pathSegmentHelper->generatePathSegment($recordToUpdate)
                    ],
                    [
                        'uid' => (int)$recordToUpdate['uid']
                    ]
                );
            }
        }

        return true;
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($this->tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq(
                        $this->slugColumn,
                        $queryBuilder->createNamedParameter('')
                    ),
                    $queryBuilder->expr()->isNull(
                        $this->slugColumn
                    )
                )
            );
    }

    protected function getUniqueValue(int $uid, string $slug): string
    {
        $queryBuilder = $this->getUniqueSlugQueryBuilder($uid, $slug);
        $statement = $queryBuilder->prepare();
        $queryResult = $statement->executeQuery();
        if ($queryResult->fetchOne()) {
            for ($counter = 1; $counter <= 100; $counter++) {
                $queryResult->free();
                $newSlug = $slug . '-' . $counter;
                $statement->bindValue(1, $newSlug);
                $queryResult = $statement->executeQuery();
                if (!$queryResult->fetchOne()) {
                    break;
                }
            }
            $queryResult->free();
        }

        return $newSlug ?? $slug;
    }

    protected function getUniqueSlugQueryBuilder(int $uid, string $slug): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($this->tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->select('uid')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    $this->slugColumn,
                    $queryBuilder->createPositionalParameter($slug)
                ),
                $queryBuilder->expr()->neq(
                    'uid',
                    $queryBuilder->createPositionalParameter($uid, Connection::PARAM_INT)
                )
            );
    }

    /**
     * @return array<class-string<DatabaseUpdatedPrerequisite>>
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
