<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/*
 * A little helper to organize our DB queries
 */
class DatabaseService
{
    /**
     * @var ExtConf
     */
    protected $extConf;

    public function __construct(ExtConf $extConf = null)
    {
        $this->extConf = $extConf ?? GeneralUtility::makeInstance(ExtConf::class);
    }

    /**
     * Get column definitions from table
     *
     * @param string $tableName
     * @return array
     */
    public function getColumnsFromTable(string $tableName): array
    {
        $output = [];
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);
        $statement = $connection->query('SHOW FULL COLUMNS FROM `' . $tableName . '`');
        while ($fieldRow = $statement->fetch()) {
            $output[$fieldRow['Field']] = $fieldRow;
        }
        return $output;
    }

    /**
     * Truncate table by TableName
     *
     * It's not a really TRUNCATE, it a DELETE FROM.
     * Set $really to true, to do a really TRUNCATE, which also sets starting increment back to 1.
     *
     * @link: https://stackoverflow.com/questions/9686888/how-to-truncate-a-table-using-doctrine-2
     * @param string $tableName
     * @param bool $really
     */
    public function truncateTable(string $tableName, bool $really = false): void
    {
        if ($really) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);
            $connection->truncate($tableName);
        } else {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder
                ->delete($tableName)
                ->from($tableName)
                ->execute();
        }
    }

    /**
     * With this method you get all current and future events of all event types.
     * It does not select hidden records as eventRepository->findByIdentifier will not find them.
     *
     * @return array
     */
    public function getCurrentAndFutureEvents(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_events2_domain_model_event');

        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class))
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $orConstraints = [];

        $orConstraints[] = $this->getConstraintForSingleEvents($queryBuilder);
        $orConstraints[] = $this->getConstraintForDurationEvents($queryBuilder);
        $orConstraints[] = $this->getConstraintForRecurringEvents($queryBuilder);

        $events = $queryBuilder
            ->select('uid', 'pid')
            ->from('tx_events2_domain_model_event')
            ->where(
                $queryBuilder->expr()->orX(...$orConstraints)
            )
            ->execute()
            ->fetchAll();

        if (empty($events)) {
            $events = [];
        }

        return $events;
    }

    /**
     * Get days in range.
     * This method was used by Ajax call: findDaysByMonth
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param array $storagePids
     * @param array $categories
     * @return array Days with event UID, event title and day timestamp
     */
    public function getDaysInRange(
        \DateTime $startDate,
        \DateTime $endDate,
        array $storagePids = [],
        array $categories = []
    ): array {
        $constraint = [];

        // Create basic query with QueryBuilder. Where-clause will be added dynamically
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_events2_domain_model_day');
        $queryBuilder = $queryBuilder
            ->select('event.uid', 'event.title', 'day.day')
            ->from('tx_events2_domain_model_day', 'day')
            ->leftJoin(
                'day',
                'tx_events2_domain_model_event',
                'event',
                $queryBuilder->expr()->eq(
                    'day.event',
                    $queryBuilder->quoteIdentifier('event.uid')
                )
            );

        // Add relation to sys_category_record_mm only if categories were set
        if (!empty($categories)) {
            $queryBuilder = $queryBuilder
                ->leftJoin(
                    'event',
                    'sys_category_record_mm',
                    'category_mm',
                    (string)$queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(
                            'event.uid',
                            $queryBuilder->quoteIdentifier('category_mm.uid_foreign')
                        ),
                        $queryBuilder->expr()->eq(
                            'category_mm.tablenames',
                            $queryBuilder->createNamedParameter(
                                'tx_events2_domain_model_event',
                                \PDO::PARAM_STR
                            )
                        ),
                        $queryBuilder->expr()->eq(
                            'category_mm.fieldname',
                            $queryBuilder->createNamedParameter(
                                'categories',
                                \PDO::PARAM_STR
                            )
                        )
                    )
                );

            $constraint[] = $queryBuilder->expr()->in(
                'category_mm.uid_local',
                $queryBuilder->createNamedParameter($categories, Connection::PARAM_INT_ARRAY)
            );
        }

        // Reduce ResultSet to configured StoragePids
        if (!empty($storagePids)) {
            $constraint[] = $queryBuilder->expr()->in(
                'event.pid',
                $queryBuilder->createNamedParameter($storagePids, Connection::PARAM_INT_ARRAY)
            );
        }

        // Get days greater than first date of month
        $constraint[] = $queryBuilder->expr()->gte(
            'day.day',
            $queryBuilder->createNamedParameter($startDate->format('U'), \PDO::PARAM_INT)
        );

        // Get days lower than last date of month
        $constraint[] = $queryBuilder->expr()->lt(
            'day.day',
            $queryBuilder->createNamedParameter($endDate->format('U'), \PDO::PARAM_INT)
        );

        return $queryBuilder
            ->where(...$constraint)
            ->execute()
            ->fetchAll();
    }

    public function getConstraintForSingleEvents(QueryBuilder $queryBuilder): string
    {
        // add where clause for single events
        return (string)$queryBuilder->expr()->andX(
            $queryBuilder->expr()->eq(
                'event_type',
                $queryBuilder->createNamedParameter('single', \PDO::PARAM_STR)
            ),
            $queryBuilder->expr()->gt(
                'event_begin',
                $queryBuilder->createNamedParameter(time(), \PDO::PARAM_INT)
            )
        );
    }

    public function getConstraintForDurationEvents(QueryBuilder $queryBuilder): string
    {
        return (string)$queryBuilder->expr()->andX(
            $queryBuilder->expr()->eq(
                'event_type',
                $queryBuilder->createNamedParameter('duration', \PDO::PARAM_STR)
            ),
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq(
                    'event_end',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->gt(
                    'event_end',
                    $queryBuilder->createNamedParameter(time(), \PDO::PARAM_INT)
                )
            )
        );
    }

    public function getConstraintForRecurringEvents(QueryBuilder $queryBuilder): string
    {
        return (string)$queryBuilder->expr()->andX(
            $queryBuilder->expr()->eq(
                'event_type',
                $queryBuilder->createNamedParameter('recurring', \PDO::PARAM_STR)
            ),
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq(
                    'recurring_end',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->gt(
                    'recurring_end',
                    $queryBuilder->createNamedParameter(time(), \PDO::PARAM_INT)
                )
            )
        );
    }

    public function addConstraintForDate(
        QueryBuilder $queryBuilder,
        string $type,
        QueryBuilder $parentQueryBuilder = null,
        string $alias = 'day'
    ): void {
        $dateTimeUtility = GeneralUtility::makeInstance(DateTimeUtility::class);
        $startDateTime = null;
        $endDateTime = null;

        switch ($type) {
            case 'today':
                $startDateTime = $dateTimeUtility->convert('today');
                $endDateTime = clone $startDateTime;
                $endDateTime->modify('23:59:59');
                break;
            case 'range':
                $startDateTime = $dateTimeUtility->convert('today');
                $endDateTime = $dateTimeUtility->convert('today');
                $endDateTime->modify('+4 weeks');
                break;
            case 'thisWeek':
                // 'first day of' does not work for 'weeks'. Using 'this week' jumps to first day of week. Monday
                $startDateTime = $dateTimeUtility->convert('today');
                $startDateTime->modify('this week');
                $endDateTime = $dateTimeUtility->convert('today');
                $endDateTime->modify('this week +6 days');
                break;
            case 'latest':
            case 'list':
            default:
                if ($this->extConf->getRecurringPast() === 0) {
                    // including current time as events in past are not allowed to be displayed
                    $startDateTime = new \DateTime('now');
                } else {
                    // exclude current time. Start with 00:00:00
                    $startDateTime = $dateTimeUtility->convert('today');
                }
        }

        $this->addConstraintForDateRange($queryBuilder, $startDateTime, $endDateTime, $parentQueryBuilder, $alias);
    }

    public function addConstraintForDateRange(
        QueryBuilder $queryBuilder,
        \DateTime $startDateTime,
        \DateTime $endDateTime = null,
        QueryBuilder $parentQueryBuilder = null,
        string $alias = 'day'
    ): void {
        if ($parentQueryBuilder === null) {
            $parentQueryBuilder = $queryBuilder;
        }

        $constraintForDateTime = $queryBuilder->expr()->gte(
            $alias . '.day_time',
            $parentQueryBuilder->createNamedParameter(
                $startDateTime->format('U'),
                \PDO::PARAM_INT,
                ':eventStartDate'
            )
        );

        if ($endDateTime instanceof \DateTime) {
            $endDateTime->modify('23:59:59');
            $constraintForDateTime = (string)$queryBuilder->expr()->andX(
                $constraintForDateTime,
                $queryBuilder->expr()->lt(
                    $alias . '.day_time',
                    $parentQueryBuilder->createNamedParameter(
                        $endDateTime->format('U'),
                        \PDO::PARAM_INT,
                        ':eventEndDate'
                    )
                )
            );
        }

        $queryBuilder->andWhere($constraintForDateTime);
    }

    public function addConstraintForPid(
        QueryBuilder $queryBuilder,
        array $storagePageIds,
        QueryBuilder $parentQueryBuilder = null,
        string $postAlias = ''
    ): void {
        if ($parentQueryBuilder === null) {
            $parentQueryBuilder = $queryBuilder;
        }

        $queryBuilder->andWhere(
            (string)$queryBuilder->expr()->andX(
                $queryBuilder->expr()->in(
                    'day' . $postAlias . '.pid',
                    $parentQueryBuilder->createNamedParameter(
                        $storagePageIds,
                        Connection::PARAM_INT_ARRAY
                    )
                ),
                $queryBuilder->expr()->in(
                    'event' . $postAlias . '.pid',
                    $parentQueryBuilder->createNamedParameter(
                        $storagePageIds,
                        Connection::PARAM_INT_ARRAY
                    )
                )
            )
        );
    }

    public function addConstraintForCategories(
        QueryBuilder $queryBuilder,
        array $categories,
        QueryBuilder $parentQueryBuilder = null,
        string $alias = 'event'
    ): void {
        if ($parentQueryBuilder === null) {
            $parentQueryBuilder = $queryBuilder;
        }

        $queryBuilder->leftJoin(
            $alias,
            'sys_category_record_mm',
            'category_mm',
            (string)$queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq(
                    $alias . '.uid',
                    $queryBuilder->quoteIdentifier('category_mm.uid_foreign')
                ),
                $queryBuilder->expr()->eq(
                    'category_mm.tablenames',
                    $parentQueryBuilder->createNamedParameter(
                        'tx_events2_domain_model_event',
                        \PDO::PARAM_STR
                    )
                ),
                $queryBuilder->expr()->eq(
                    'category_mm.fieldname',
                    $parentQueryBuilder->createNamedParameter(
                        'categories',
                        \PDO::PARAM_STR
                    )
                )
            )
        );

        $queryBuilder->andWhere(
            $queryBuilder->expr()->in(
                'category_mm.uid_local',
                $parentQueryBuilder->createNamedParameter(
                    $categories,
                    Connection::PARAM_INT_ARRAY
                )
            )
        );
    }

    public function addConstraintForOrganizer(
        QueryBuilder $queryBuilder,
        int $organizer,
        QueryBuilder $parentQueryBuilder = null,
        string $alias = 'event'
    ): void {
        if ($parentQueryBuilder === null) {
            $parentQueryBuilder = $queryBuilder;
        }

        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                $alias . '.organizer',
                $parentQueryBuilder->createNamedParameter(
                    $organizer,
                    \PDO::PARAM_INT
                )
            )
        );
    }

    public function addConstraintForLocation(
        QueryBuilder $queryBuilder,
        int $location,
        QueryBuilder $parentQueryBuilder = null,
        string $alias = 'event'
    ): void {
        if ($parentQueryBuilder === null) {
            $parentQueryBuilder = $queryBuilder;
        }

        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                $alias . '.location',
                $parentQueryBuilder->createNamedParameter(
                    $location,
                    \PDO::PARAM_INT
                )
            )
        );
    }

    /**
     * Add Constraint for various columns of event table
     *
     * @param QueryBuilder $queryBuilder
     * @param string $column
     * @param mixed $value
     * @param int $dataType
     * @param QueryBuilder $parentQueryBuilder
     * @param string $alias
     */
    public function addConstraintForEventColumn(
        QueryBuilder $queryBuilder,
        string $column,
        $value,
        int $dataType = \PDO::PARAM_STR,
        QueryBuilder $parentQueryBuilder = null,
        string $alias = 'event'
    ): void {
        if ($parentQueryBuilder === null) {
            $parentQueryBuilder = $queryBuilder;
        }

        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                $alias . '.' . $column,
                $parentQueryBuilder->createNamedParameter(
                    $value,
                    $dataType
                )
            )
        );
    }

    /**
     * Working with own QueryBuilder queries does not respect showHiddenContent settings of TYPO3, that's why
     * we have to manually remove Hidden constraint from restriction container.
     *
     * @param QueryBuilder $queryBuilder
     */
    public function addVisibilityConstraintToQuery(QueryBuilder $queryBuilder): void
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $showHiddenRecords = (bool)$context->getPropertyFromAspect(
            'visibility',
            'includeHiddenContent',
            false
        );

        if ($showHiddenRecords) {
            $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        }
    }

    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        if ($GLOBALS['TSFE'] === null) {
            $GLOBALS['TSFE'] = GeneralUtility::makeInstance(
                TypoScriptFrontendController::class,
                [],
                1,
                0
            );
        }
        return $GLOBALS['TSFE'];
    }
}
