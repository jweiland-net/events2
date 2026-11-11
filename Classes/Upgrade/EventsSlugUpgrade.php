<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Upgrade;

use JWeiland\Events2\Helper\PathSegmentHelper;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Fills empty slug columns of event records. Existing slugs are kept.
 */
#[UpgradeWizard('events2_updateSlug')]
final readonly class EventsSlugUpgrade implements UpgradeWizardInterface
{
    private const TABLE = 'tx_events2_domain_model_event';

    private const SLUG_COLUMN = 'path_segment';

    public function __construct(
        private PathSegmentHelper $pathSegmentHelper,
        private ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return '[events2] Update Slugs of event records';
    }

    public function getDescription(): string
    {
        return 'Update empty slug column "path_segment" of events2 records with an URI compatible version of the title';
    }

    public function updateNecessary(): bool
    {
        return (bool)$this->getQueryBuilderForEmptySlugs()
            ->count('*')
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Records are processed by UID. So, the first record keeps the slug
     * of its title and following records with the same title get a suffix.
     */
    public function executeUpdate(): bool
    {
        $queryResult = $this->getQueryBuilderForEmptySlugs()
            ->select('*')
            ->orderBy('uid', 'ASC')
            ->executeQuery();

        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
        while ($eventRecord = $queryResult->fetchAssociative()) {
            $connection->update(
                self::TABLE,
                [self::SLUG_COLUMN => $this->pathSegmentHelper->generatePathSegment($eventRecord)],
                ['uid' => (int)$eventRecord['uid']],
            );
        }

        return true;
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

    private function getQueryBuilderForEmptySlugs(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        return $queryBuilder
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq(self::SLUG_COLUMN, $queryBuilder->createNamedParameter('')),
                    $queryBuilder->expr()->isNull(self::SLUG_COLUMN),
                ),
            );
    }
}
