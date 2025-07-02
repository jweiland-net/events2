<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A little helper to organize our DB queries
 */
readonly class DatabaseService
{
    public function __construct(
        protected ExtConf $extConf,
        protected DateTimeUtility $dateTimeUtility,
    ) {}

    /**
     * Truncate table by TableName
     *
     * It's not a really TRUNCATE, it a DELETE FROM.
     * Set $really to true, to do a really TRUNCATE, which also sets starting increment back to 1.
     *
     * @link: https://stackoverflow.com/questions/9686888/how-to-truncate-a-table-using-doctrine-2
     */
    public function truncateTable(string $tableName, bool $really = false): void
    {
        if ($really) {
            $connection = $this->getConnectionPool()->getConnectionForTable($tableName);
            $connection->truncate($tableName);
        } else {
            $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder
                ->delete($tableName)
                ->from($tableName)
                ->executeStatement();
        }
    }

    /**
     * Returns a QueryBuilder to work on all event records.
     * Will be used in services and commands to update/delete the day records.
     */
    public function getQueryBuilderForAllEvents(): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_event');

        // Updating the day records is needed for frontend
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

        return $queryBuilder->from('tx_events2_domain_model_event');
    }

    /**
     * Get days in range.
     * This method was used by Ajax call: findDaysByMonth
     */
    public function getDaysInRange(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        array $storagePids = [],
        array $categories = [],
    ): array {
        $constraint = [];

        // Create a basic query with QueryBuilder. Where-clause will be added dynamically
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_day');
        $queryBuilder = $queryBuilder
            ->select('event.uid', 'event.title', 'day.day')
            ->from('tx_events2_domain_model_day', 'day')
            ->leftJoin(
                'day',
                'tx_events2_domain_model_event',
                'event',
                $queryBuilder->expr()->eq(
                    'day.event',
                    $queryBuilder->quoteIdentifier('event.uid'),
                ),
            );

        // Add relation to sys_category_record_mm only if categories were set
        if (!empty($categories)) {
            $queryBuilder = $queryBuilder
                ->leftJoin(
                    'event',
                    'sys_category_record_mm',
                    'category_mm',
                    (string)$queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq(
                            'event.uid',
                            $queryBuilder->quoteIdentifier('category_mm.uid_foreign'),
                        ),
                        $queryBuilder->expr()->eq(
                            'category_mm.tablenames',
                            $queryBuilder->createNamedParameter(
                                'tx_events2_domain_model_event',
                            ),
                        ),
                        $queryBuilder->expr()->eq(
                            'category_mm.fieldname',
                            $queryBuilder->createNamedParameter(
                                'categories',
                            ),
                        ),
                    ),
                );

            $constraint[] = $queryBuilder->expr()->in(
                'category_mm.uid_local',
                $queryBuilder->createNamedParameter($categories, ArrayParameterType::INTEGER),
            );
        }

        // Reduce ResultSet to configured StoragePids
        if (!empty($storagePids)) {
            $constraint[] = $queryBuilder->expr()->in(
                'event.pid',
                $queryBuilder->createNamedParameter($storagePids, ArrayParameterType::INTEGER),
            );
        }

        // Get days greater than first date of month
        $constraint[] = $queryBuilder->expr()->gte(
            'day.day',
            $queryBuilder->createNamedParameter($startDate->format('U'), Connection::PARAM_INT),
        );

        // Get days lower than last date of month
        $constraint[] = $queryBuilder->expr()->lt(
            'day.day',
            $queryBuilder->createNamedParameter($endDate->format('U'), Connection::PARAM_INT),
        );

        return $queryBuilder
            ->where(...$constraint)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param \DateTimeImmutable $startDateTime
     * @param \DateTimeImmutable|null $endDateTime
     * @param QueryBuilder|null $parentQueryBuilder
     * @param string $alias
     */
    public function addConstraintForDateRange(
        QueryBuilder $queryBuilder,
        \DateTimeImmutable $startDateTime,
        ?\DateTimeImmutable $endDateTime = null,
        QueryBuilder $parentQueryBuilder = null,
        string $alias = 'day',
    ): void {
        if ($parentQueryBuilder === null) {
            $parentQueryBuilder = $queryBuilder;
        }

        $constraintForDateTime = $queryBuilder->expr()->gte(
            $alias . '.day_time',
            $parentQueryBuilder->createNamedParameter(
                $startDateTime->format('U'),
                Connection::PARAM_INT,
                ':eventStartDate',
            ),
        );

        if ($endDateTime instanceof \DateTimeImmutable) {
            $endDateTimeNight = $endDateTime->modify('23:59:59');
            $constraintForDateTime = (string)$queryBuilder->expr()->and(
                $constraintForDateTime,
                $queryBuilder->expr()->lt(
                    $alias . '.day_time',
                    $parentQueryBuilder->createNamedParameter(
                        $endDateTimeNight->format('U'),
                        Connection::PARAM_INT,
                        ':eventEndDate',
                    ),
                ),
            );
        }

        $queryBuilder->andWhere($constraintForDateTime);
    }

    public function addConstraintForPid(
        QueryBuilder $queryBuilder,
        array $storagePageIds,
        QueryBuilder $parentQueryBuilder = null,
        string $postAlias = '',
    ): void {
        if ($parentQueryBuilder === null) {
            $parentQueryBuilder = $queryBuilder;
        }

        $queryBuilder->andWhere(
            (string)$queryBuilder->expr()->and(
                $queryBuilder->expr()->in(
                    'day' . $postAlias . '.pid',
                    $parentQueryBuilder->createNamedParameter(
                        $storagePageIds,
                        ArrayParameterType::INTEGER,
                    ),
                ),
                $queryBuilder->expr()->in(
                    'event' . $postAlias . '.pid',
                    $parentQueryBuilder->createNamedParameter(
                        $storagePageIds,
                        ArrayParameterType::INTEGER,
                    ),
                ),
            ),
        );
    }

    public function addConstraintForCategories(
        QueryBuilder $queryBuilder,
        array $categories,
        QueryBuilder $parentQueryBuilder = null,
        string $alias = 'event',
    ): void {
        if ($parentQueryBuilder === null) {
            $parentQueryBuilder = $queryBuilder;
        }

        $queryBuilder->leftJoin(
            $alias,
            'sys_category_record_mm',
            'category_mm',
            (string)$queryBuilder->expr()->and(
                $queryBuilder->expr()->eq(
                    $alias . '.uid',
                    $queryBuilder->quoteIdentifier('category_mm.uid_foreign'),
                ),
                $queryBuilder->expr()->eq(
                    'category_mm.tablenames',
                    $parentQueryBuilder->createNamedParameter(
                        'tx_events2_domain_model_event',
                    ),
                ),
                $queryBuilder->expr()->eq(
                    'category_mm.fieldname',
                    $parentQueryBuilder->createNamedParameter(
                        'categories',
                    ),
                ),
            ),
        );

        $queryBuilder->andWhere(
            $queryBuilder->expr()->in(
                'category_mm.uid_local',
                $parentQueryBuilder->createNamedParameter(
                    $categories,
                    ArrayParameterType::INTEGER,
                ),
            ),
        );
    }

    public function addConstraintForOrganizer(
        QueryBuilder $queryBuilder,
        int $organizer,
        QueryBuilder $parentQueryBuilder = null,
        string $alias = 'event',
    ): void {
        if ($parentQueryBuilder === null) {
            $parentQueryBuilder = $queryBuilder;
        }

        $queryBuilder
            ->innerJoin(
                $alias,
                'tx_events2_event_organizer_mm',
                'eo_mm',
                $queryBuilder->expr()->eq(
                    $alias . '.uid',
                    $queryBuilder->quoteIdentifier('eo_mm.uid_local'),
                ),
            )
            ->andWhere(
                $queryBuilder->expr()->eq(
                    'eo_mm.uid_foreign',
                    $parentQueryBuilder->createNamedParameter(
                        $organizer,
                        Connection::PARAM_INT,
                    ),
                ),
            );
    }

    public function addConstraintForLocation(
        QueryBuilder $queryBuilder,
        int $location,
        QueryBuilder $parentQueryBuilder = null,
        string $alias = 'event',
    ): void {
        if ($parentQueryBuilder === null) {
            $parentQueryBuilder = $queryBuilder;
        }

        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                $alias . '.location',
                $parentQueryBuilder->createNamedParameter(
                    $location,
                    Connection::PARAM_INT,
                ),
            ),
        );
    }

    /**
     * Add Constraint for various columns of the event table
     */
    public function addConstraintForEventColumn(
        QueryBuilder $queryBuilder,
        string $column,
        mixed $value,
        ParameterType $dataType = Connection::PARAM_STR,
        QueryBuilder $parentQueryBuilder = null,
        string $alias = 'event',
    ): void {
        if ($parentQueryBuilder === null) {
            $parentQueryBuilder = $queryBuilder;
        }

        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                $alias . '.' . $column,
                $parentQueryBuilder->createNamedParameter(
                    $value,
                    $dataType,
                ),
            ),
        );
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
