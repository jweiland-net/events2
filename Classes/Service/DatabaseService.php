<?php
namespace JWeiland\Events2\Service;

/*
 * This file is part of the events2 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A little helper to organize our DB queries
 */
class DatabaseService
{
    /**
     * @var ExtConf
     */
    protected $extConf;

    /**
     * @param ExtConf $extConf
     */
    public function injectExtConf(ExtConf $extConf)
    {
        $this->extConf = $extConf;
    }

    /**
     * Get column definitions from table
     *
     * @param string $tableName
     * @return array
     */
    public function getColumnsFromTable($tableName): array
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
     * With this method you get all current and future events of all event types.
     * It does not select hidden records as eventRepository->findByIdentifier will not find them.
     *
     * @return array
     */
    public function getCurrentAndFutureEvents()
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
    public function getDaysInRange(\DateTime $startDate, \DateTime $endDate, array $storagePids = [], array $categories = [])
    {
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
                    $queryBuilder->expr()->andX(
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

        $daysInMonth = $queryBuilder
            ->where(...$constraint)
            ->execute()
            ->fetchAll();

        return $daysInMonth;
    }

    /**
     * Get Constraint for single events
     *
     * @param QueryBuilder $queryBuilder
     * @return string
     */
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

    /**
     * Get Constraint for duration events
     *
     * @param QueryBuilder $queryBuilder
     * @return string
     */
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

    /**
     * Get Constraint for recurring events
     *
     * @param QueryBuilder $queryBuilder
     * @return string
     */
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

    /**
     * Initialize QueryBuilder as SubQuery for day table
     *
     * @param QueryBuilder $queryBuilder
     * @param QueryBuilder $subQueryBuilder
     * @param string $constraint
     * @param bool $mergeRecurringEvents
     * @param bool $mergeEventsAtSameDay
     */
    public function initializeSubQueryBuilder(
        QueryBuilder $queryBuilder,
        QueryBuilder $subQueryBuilder,
        string $constraint,
        bool $mergeRecurringEvents,
        bool $mergeEventsAtSameDay
    ) {
        $subQueryBuilder
            ->selectLiteral('MIN(day_sub_query.day_time) as next_day_time', 'day_sub_query.event')
            ->from('tx_events2_domain_model_day', 'day_sub_query')
            ->where($constraint);

        if ($mergeRecurringEvents) {
            $subQueryBuilder->groupBy('day_sub_query.event');
        } elseif ($mergeEventsAtSameDay) {
            $subQueryBuilder->groupBy('day_sub_query.event', 'day_sub_query.day');
        } else {
            $subQueryBuilder->groupBy('day_sub_query.event', 'day_sub_query.sort_day_time');
        }

        $queryBuilder->getConcreteQueryBuilder()->join(
            $queryBuilder->quoteIdentifier('day'),
            sprintf(
                '(%s)',
                $subQueryBuilder->getSQL()
            ),
            $queryBuilder->quoteIdentifier('day_sub_group'),
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq(
                    'day.event',
                    $queryBuilder->quoteIdentifier('day_sub_group.event')
                ),
                $queryBuilder->expr()->eq(
                    'day.day_time',
                    $queryBuilder->quoteIdentifier('day_sub_group.next_day_time')
                )
            )
        );
    }

    /**
     * Get Constraints for Date
     *
     * @param QueryBuilder $queryBuilder
     * @param string $type
     * @param string $alias
     * @return string
     */
    public function getConstraintForDate(QueryBuilder $queryBuilder, string $type, string $alias = 'day')
    {
        $dateTimeUtility = GeneralUtility::makeInstance(DateTimeUtility::class);
        $startDateTime = null;
        $endDateTime = null;

        switch ($type) {
            case 'today':
                $startDateTime = $dateTimeUtility->convert('today');
                $endDateTime = $dateTimeUtility->convert('today');
                $endDateTime->modify('+1 day');
                break;
            case 'range':
                $startDateTime = $dateTimeUtility->convert('today');
                $endDateTime = $dateTimeUtility->convert('today');
                $endDateTime->modify('+4 weeks');
                break;
            case 'thisWeek':
                $startDateTime = $dateTimeUtility->convert('today');
                $startDateTime->modify('this week'); // 'first day of' does not work for 'weeks'
                $endDateTime = $dateTimeUtility->convert('today');
                $endDateTime->modify('this week +6 days'); // 'last day of' does not work for 'weeks'
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

        return $this->getConstraintForDateRange($queryBuilder, $startDateTime, $endDateTime, $alias);
    }

    /**
     * Get constraint for Date within a given range
     *
     * @param QueryBuilder $queryBuilder
     * @param \DateTime $startDateTime
     * @param \DateTime|null $endDateTime
     * @param string $alias
     * @return string
     */
    public function getConstraintForDateRange(QueryBuilder $queryBuilder, \DateTime $startDateTime, \DateTime $endDateTime = null, string $alias = 'day'): string
    {
        $constraintForStartDateTime = $queryBuilder->expr()->gte(
            $alias . '.day_time',
            $queryBuilder->createNamedParameter(
                $startDateTime->format('U'),
                \PDO::PARAM_INT,
                ':eventStartDate'
            )
        );

        if (!$endDateTime) {
            return $constraintForStartDateTime;
        }

        return (string)$queryBuilder->expr()->andX(
            $constraintForStartDateTime,
            $queryBuilder->expr()->lt(
                $alias . '.day_time',
                $queryBuilder->createNamedParameter(
                    $endDateTime->format('U'),
                    \PDO::PARAM_INT,
                    ':eventEndDate'
                )
            )
        );
    }

    /**
     * Get Constraint for storage page UIDs
     *
     * @param QueryBuilder $queryBuilder
     * @param array $storagePageIds
     * @return string
     */
    public function getConstraintForPid(QueryBuilder $queryBuilder, array $storagePageIds): string
    {
        return (string)$queryBuilder->expr()->andX(
            $queryBuilder->expr()->in(
                'day.pid',
                $queryBuilder->createNamedParameter(
                    $storagePageIds,
                    Connection::PARAM_INT_ARRAY
                )
            ),
            $queryBuilder->expr()->in(
                'event.pid',
                $queryBuilder->createNamedParameter(
                    $storagePageIds,
                    Connection::PARAM_INT_ARRAY
                )
            )
        );
    }

    /**
     * Get Constraint for Categories
     *
     * @param QueryBuilder $queryBuilder
     * @param array $categories
     * @return string
     */
    public function getConstraintForCategories(QueryBuilder $queryBuilder, array $categories): string
    {
        $queryBuilder->leftJoin(
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
                    $queryBuilder->createNamedParameter('tx_events2_domain_model_event', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'category_mm.fieldname',
                    $queryBuilder->createNamedParameter('categories', \PDO::PARAM_STR)
                )
            )
        );

        return $queryBuilder->expr()->in(
            'category_mm.uid_local',
            $queryBuilder->createNamedParameter(
                $categories,
                Connection::PARAM_INT_ARRAY
            )
        );
    }

    /**
     * Get Constraint for Organizer
     *
     * @param QueryBuilder $queryBuilder
     * @param int $organizer
     * @return string
     */
    public function getConstraintForOrganizer(QueryBuilder $queryBuilder, int $organizer): string
    {
        return $queryBuilder->expr()->eq(
            'event.organizer',
            $queryBuilder->createNamedParameter($organizer, \PDO::PARAM_INT)
        );
    }

    /**
     * Get Constraint for Location
     *
     * @param QueryBuilder $queryBuilder
     * @param int $location
     * @return string
     */
    public function getConstraintForLocation(QueryBuilder $queryBuilder, int $location): string
    {
        return $queryBuilder->expr()->eq(
            'event.location',
            $queryBuilder->createNamedParameter($location, \PDO::PARAM_INT)
        );
    }

    /**
     * Get Constraint for various columns of event table
     *
     * @param QueryBuilder $queryBuilder
     * @param string $column
     * @param string $value
     * @param int $dataType
     * @return string
     */
    public function getConstraintForEventColumn(QueryBuilder $queryBuilder, string $column, $value, int $dataType = \PDO::PARAM_STR): string
    {
        return $queryBuilder->expr()->eq(
            'event.' . $column,
            $queryBuilder->createNamedParameter($value, $dataType)
        );
    }
}
