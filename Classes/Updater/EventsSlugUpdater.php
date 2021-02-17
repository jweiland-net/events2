<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Updater;

use Doctrine\DBAL\Driver\Statement;
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Helper\PathSegmentHelper;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/*
 * Updater to fill empty slug columns of event records
 */
class EventsSlugUpdater implements UpgradeWizardInterface
{
    /**
     * @var string
     */
    protected $tableName = 'tx_events2_domain_model_event';

    /**
     * @var string
     */
    protected $slugColumn = 'path_segment';

    /**
     * @var string
     */
    protected $titleColumn = 'title';

    /**
     * @var PathSegmentHelper
     */
    protected $pathSegmentHelper;

    /**
     * @var ExtConf
     */
    protected $extConf;

    public function __construct(PathSegmentHelper $pathSegmentHelper = null, ExtConf $extConf = null)
    {
        $this->pathSegmentHelper = $pathSegmentHelper ?? GeneralUtility::makeInstance(PathSegmentHelper::class);
        $this->extConf = $extConf ?? GeneralUtility::makeInstance(ExtConf::class);
    }

    /**
     * Return the identifier for this wizard
     * This should be the same string as used in the ext_localconf class registration
     *
     * @return string
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
            ->execute()
            ->fetchColumn(0);

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
        $statement = $queryBuilder
            ->select('uid', 'pid', $this->titleColumn)
            ->execute();

        $connection = $this->getConnectionPool()->getConnectionForTable($this->tableName);
        while ($recordToUpdate = $statement->fetch()) {
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
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq(
                        $this->slugColumn,
                        $queryBuilder->createNamedParameter('', Connection::PARAM_STR)
                    ),
                    $queryBuilder->expr()->isNull(
                        $this->slugColumn
                    )
                )
            );
    }

    /**
     * @param int $uid
     * @param string $slug
     * @return string
     */
    protected function getUniqueValue(int $uid, string $slug): string
    {
        $statement = $this->getUniqueSlugStatement($uid, $slug);
        $counter = $this->slugCache[$slug] ?? 1;
        while ($statement->fetch()) {
            $newSlug = $slug . '-' . $counter;
            $statement->bindValue(1, $newSlug);
            $statement->execute();

            // Do not cache every slug, because of memory consumption. I think 5 is a good value to start caching.
            if ($counter > 5) {
                $this->slugCache[$slug] = $counter;
            }
            $counter++;
        }

        return $newSlug ?? $slug;
    }

    protected function getUniqueSlugStatement(int $uid, string $slug): Statement
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
                    $queryBuilder->createPositionalParameter($slug, Connection::PARAM_STR)
                ),
                $queryBuilder->expr()->neq(
                    'uid',
                    $queryBuilder->createPositionalParameter($uid, Connection::PARAM_INT)
                )
            )
            ->execute();
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
