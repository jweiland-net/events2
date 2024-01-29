<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\DBALException;
use JWeiland\Events2\Helper\OverlayHelper;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

/*
 * Abstract repository with helpful methods for all repos
 */
class AbstractRepository extends Repository
{
    protected OverlayHelper $overlayHelper;

    public function injectOverlayHelper(OverlayHelper $overlayHelper): void
    {
        $this->overlayHelper = $overlayHelper;
    }

    protected function getQueryBuilderForTable(
        string $table,
        string $alias,
        bool $useLangStrict = false,
        int $overrideLanguageUid = -1
    ): QueryBuilder {
        $extbaseQuery = $this->createQuery();

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        $queryBuilder
            ->from($table, $alias)
            ->andWhere(
                $queryBuilder->expr()->in(
                    $alias . '.pid',
                    $queryBuilder->createNamedParameter(
                        $extbaseQuery->getQuerySettings()->getStoragePageIds(),
                        ArrayParameterType::INTEGER
                    )
                )
            );

        $this->overlayHelper->addWhereForOverlay($queryBuilder, $table, $alias, $useLangStrict, $overrideLanguageUid);

        return $queryBuilder;
    }

    /**
     * Very simple method to just get a record.
     * By default, we will do an overlay for you, too.
     */
    public function getRecordByUid(
        string $tableName,
        string $tableAlias,
        int $uid,
        array $select = ['*'],
        bool $includeHidden = false,
        bool $doOverlay = true
    ): array {
        $queryBuilder = $this->getQueryBuilderForTable($tableName, $tableAlias);

        if ($includeHidden) {
            $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        }

        try {
            $record = $queryBuilder
                ->select(...$select)
                ->where(
                    $queryBuilder->expr()->eq(
                        $tableAlias . '.uid',
                        $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                    )
                )
                ->executeQuery()
                ->fetchAssociative();

            if ($record === false) {
                return [];
            }
        } catch (DBALException $DBALException) {
            return [];
        }

        if ($doOverlay) {
            $record = $this->overlayHelper->doOverlay($tableName, $record);
        }

        return $record;
    }

    /**
     * Very simple method to get records by expression.
     * By default, we will do an overlay for you, too.
     */
    public function getRecordsByExpression(
        string $tableName,
        string $tableAlias,
        array $expressions = [],
        array $select = ['*'],
        bool $includeHidden = false,
        bool $doOverlay = true
    ): array {
        $queryBuilder = $this->getQueryBuilderForTable($tableName, $tableAlias);

        if ($includeHidden) {
            $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        }

        try {
            $queryBuilder->select(...$select);

            if ($expressions !== []) {
                $queryBuilder->where(...$expressions);
            }

            $queryResult = $queryBuilder->executeQuery();

            $records = [];
            while ($record = $queryResult->fetchAssociative()) {
                if ($doOverlay) {
                    $record = $this->overlayHelper->doOverlay($tableName, $record);
                }

                // Do not add empty records, because of overlay
                if ($record !== []) {
                    $records[$record['uid']] = $record;
                }
            }
        } catch (DBALException $DBALException) {
            return [];
        }

        return $records;
    }

    /**
     * Needed to build expressions for ->getRecordsByExpression()
     */
    public function getExpressionBuilder(string $tableName): ExpressionBuilder
    {
        return $this->getConnectionPool()
            ->getConnectionForTable($tableName)
            ->getExpressionBuilder();
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
