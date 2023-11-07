<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use Doctrine\DBAL\Driver\Statement;
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Utility\DateTimeUtility;
use org\bovigo\vfs\vfsStreamContainerIterator;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
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

    /**
     * @var DateTimeUtility
     */
    protected $dateTimeUtility;

    public function __construct(ExtConf $extConf, DateTimeUtility $dateTimeUtility)
    {
        $this->extConf = $extConf;
        $this->dateTimeUtility = $dateTimeUtility;
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
        $connection = $this->getConnectionPool()->getConnectionForTable($tableName);
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
            $connection = $this->getConnectionPool()->getConnectionForTable($tableName);
            $connection->truncate($tableName);
        } else {
            $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder
                ->delete($tableName)
                ->from($tableName)
                ->execute();
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
     * With this method you get all current and future events of all event types.
     * It does not select hidden records as eventRepository->findByIdentifier will not find them.
     *
     * @deprecated
     */
    public function getCurrentAndFutureEvents(): array
    {
        trigger_error(
            'Using \JWeiland\Events2\Service\DatabaseService::getCurrentAndFutureEvents is deprecated, please use getQueryBuilderForAllEvents.',
            E_USER_DEPRECATED
        );

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_event');

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
                                'tx_events2_domain_model_event'
                            )
                        ),
                        $queryBuilder->expr()->eq(
                            'category_mm.fieldname',
                            $queryBuilder->createNamedParameter(
                                'categories'
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

    /**
     * @param QueryBuilder $queryBuilder
     * @return string
     * @deprecated
     */
    public function getConstraintForSingleEvents(QueryBuilder $queryBuilder): string
    {
        trigger_error(
            'Using \JWeiland\Events2\Service\DatabaseService::getConstraintForSingleEvents is deprecated, please use getQueryBuilderForAllEvents.',
            E_USER_DEPRECATED
        );

        // add where clause for single events
        return (string)$queryBuilder->expr()->andX(
            $queryBuilder->expr()->eq(
                'event_type',
                $queryBuilder->createNamedParameter('single')
            ),
            $queryBuilder->expr()->gt(
                'event_begin',
                $queryBuilder->createNamedParameter(time(), \PDO::PARAM_INT)
            )
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return string
     * @deprecated
     */
    public function getConstraintForDurationEvents(QueryBuilder $queryBuilder): string
    {
        trigger_error(
            'Using \JWeiland\Events2\Service\DatabaseService::getConstraintForDurationEvents is deprecated, please use getQueryBuilderForAllEvents.',
            E_USER_DEPRECATED
        );

        return (string)$queryBuilder->expr()->andX(
            $queryBuilder->expr()->eq(
                'event_type',
                $queryBuilder->createNamedParameter('duration')
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

    /**
     * @param QueryBuilder $queryBuilder
     * @return string
     * @deprecated
     */
    public function getConstraintForRecurringEvents(QueryBuilder $queryBuilder): string
    {
        trigger_error(
            'Using \JWeiland\Events2\Service\DatabaseService::getConstraintForRecurringEvents is deprecated, please use getQueryBuilderForAllEvents.',
            E_USER_DEPRECATED
        );

        return (string)$queryBuilder->expr()->andX(
            $queryBuilder->expr()->eq(
                'event_type',
                $queryBuilder->createNamedParameter('recurring')
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
        $endDateTime = null;

        switch ($type) {
            case 'today':
                $startDateTime = $this->dateTimeUtility->convert('today');
                $endDateTime = clone $startDateTime;
                $endDateTime->modify('23:59:59');
                break;
            case 'range':
                $startDateTime = $this->dateTimeUtility->convert('today');
                $endDateTime = $this->dateTimeUtility->convert('today');
                $endDateTime->modify('+4 weeks');
                break;
            case 'thisWeek':
                // 'first day of' does not work for 'weeks'. Using 'this week' jumps to first day of week. Monday
                $startDateTime = $this->dateTimeUtility->convert('today');
                $startDateTime->modify('this week');
                $endDateTime = $this->dateTimeUtility->convert('today');
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
                    $startDateTime = $this->dateTimeUtility->convert('today');
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
                        'tx_events2_domain_model_event'
                    )
                ),
                $queryBuilder->expr()->eq(
                    'category_mm.fieldname',
                    $parentQueryBuilder->createNamedParameter(
                        'categories'
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

        $queryBuilder
            ->innerJoin(
                $alias,
                'tx_events2_event_organizer_mm',
                'eo_mm',
                $queryBuilder->expr()->eq(
                    $alias . '.uid',
                    $queryBuilder->quoteIdentifier('eo_mm.uid_local')
                )
            )
            ->andWhere(
                $queryBuilder->expr()->eq(
                    'eo_mm.uid_foreign',
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
     * @param QueryBuilder|null $parentQueryBuilder
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

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
