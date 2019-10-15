<?php
declare(strict_types = 1);
namespace JWeiland\Events2\Domain\Repository;

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
use JWeiland\Events2\Domain\Factory\DayFactory;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Model\Search;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository to get and find day records from storage
 */
class DayRepository extends Repository
{
    /**
     * @var DateTimeUtility
     */
    protected $dateTimeUtility;

    /**
     * @var ExtConf
     */
    protected $extConf;

    /**
     * @var DatabaseService
     */
    protected $databaseService;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @param DateTimeUtility $dateTimeUtility
     */
    public function injectDateTimeUtility(DateTimeUtility $dateTimeUtility)
    {
        $this->dateTimeUtility = $dateTimeUtility;
    }

    /**
     * @param ExtConf $extConf
     */
    public function injectExtConf(ExtConf $extConf)
    {
        $this->extConf = $extConf;
    }

    /**
     * @param DatabaseService $databaseService
     */
    public function injectDatabaseService(DatabaseService $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    /**
     * Sets the settings
     *
     * @param array $settings
     */
    public function setSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Find events
     *
     * @param string $type
     * @param Filter $filter
     * @param int $limit As Paginator will override $limit, this will only work within LatestView
     * @return QueryResultInterface|Day[]
     * @throws \Exception
     */
    public function findEvents(string $type, Filter $filter, int $limit = 0): QueryResultInterface
    {
        /** @var Query $extbaseQuery */
        $extbaseQuery = $this->createQuery();
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_day');
        $this->databaseService->addVisibilityConstraintToQuery($queryBuilder);
        $subQueryBuilder = $this->getSubQueryBuilder($queryBuilder);

        // add storage PID for event and day, but not for sys_category
        $this->databaseService->addConstraintForPid(
            $subQueryBuilder,
            $extbaseQuery->getQuerySettings()->getStoragePageIds(),
            $queryBuilder,
            '_sub_query'
        );

        // add categories
        if (!empty($this->settings['categories'])) {
            $this->databaseService->addConstraintForCategories(
                $subQueryBuilder,
                GeneralUtility::trimExplode(',', $this->settings['categories'], true),
                $queryBuilder,
                'event_sub_query'
            );
        }

        // add filter for organizer
        if ($filter->getOrganizer() || $this->settings['preFilterByOrganizer']) {
            $this->databaseService->addConstraintForOrganizer(
                $subQueryBuilder,
                (int)$filter->getOrganizer() ?: (int)$this->settings['preFilterByOrganizer'],
                $queryBuilder,
                'event_sub_query'
            );
        }

        // add date filter
        $this->databaseService->addConstraintForDate(
            $subQueryBuilder,
            $type,
            $queryBuilder,
            'day_sub_query'
        );

        $queryBuilder
            ->select('day.*')
            ->from('tx_events2_domain_model_day', 'day')
            ->leftJoin(
                'day',
                'tx_events2_domain_model_event',
                'event',
                $queryBuilder->expr()->eq(
                    'day.event',
                    $queryBuilder->quoteIdentifier('event.uid')
                )
            )
            ->orderBy('event.top_of_list', 'DESC')
            ->addOrderBy('day.sort_day_time', 'ASC')
            ->addOrderBy('day.day_time', 'ASC');

        if (!empty($limit)) {
            $queryBuilder->setMaxResults((int)$limit);
        }

        $this->addMergeFeatureToQuery($subQueryBuilder);
        $this->joinSubQueryIntoQueryBuilder($queryBuilder, $subQueryBuilder);

        $extbaseQuery->statement($queryBuilder);

        return $extbaseQuery->execute();
    }

    /**
     * Search for events.
     *
     * @param Search $search
     * @return QueryResultInterface
     * @throws \Exception
     */
    public function searchEvents(Search $search): QueryResultInterface
    {
        /** @var Query $extbaseQuery */
        $extbaseQuery = $this->createQuery();
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_day');
        $this->databaseService->addVisibilityConstraintToQuery($queryBuilder);
        $subQueryBuilder = $this->getSubQueryBuilder($queryBuilder);

        // add storage PID for event and day, but not for sys_category
        $this->databaseService->addConstraintForPid(
            $subQueryBuilder,
            $extbaseQuery->getQuerySettings()->getStoragePageIds(),
            $queryBuilder,
            '_sub_query'
        );

        // add query for search string
        if ($search->getSearch()) {
            $subQueryBuilder->andWhere(
                (string)$subQueryBuilder->expr()->orX(
                    $queryBuilder->expr()->like(
                        'event_sub_query.title',
                        $queryBuilder->quote('%' . $search->getSearch() . '%')
                    ),
                    $subQueryBuilder->expr()->like(
                        'event_sub_query.teaser',
                        $queryBuilder->quote('%' . $search->getSearch() . '%')
                    )
                )
            );
        }

        // add query for categories
        if ($search->getMainCategory()) {
            if ($search->getSubCategory()) {
                $this->databaseService->addConstraintForCategories(
                    $subQueryBuilder,
                    [$search->getSubCategory()->getUid()],
                    $queryBuilder,
                    'event_sub_query'
                );
            } else {
                $this->databaseService->addConstraintForCategories(
                    $subQueryBuilder,
                    [$search->getMainCategory()->getUid()],
                    $queryBuilder,
                    'event_sub_query'
                );
            }
        } elseif ($this->settings['categories']) {
            // visitor has not selected any category. Search within allowed categories in plugin configuration
            $this->databaseService->addConstraintForCategories(
                $subQueryBuilder,
                GeneralUtility::trimExplode(',', $this->settings['categories']),
                $queryBuilder,
                'event_sub_query'
            );
        }

        $today = $this->dateTimeUtility->convert('today');
        $startDateTime = $search->getEventBegin() ?: $today;
        $endDateTime = $search->getEventEnd();

        // add startDate and endDate
        $this->databaseService->addConstraintForDateRange(
            $subQueryBuilder,
            $startDateTime,
            $endDateTime,
            $queryBuilder,
            'day_sub_query'
        );

        // add query for event location
        if ($search->getLocation()) {
            $this->databaseService->addConstraintForLocation(
                $subQueryBuilder,
                $search->getLocation()->getUid(),
                $queryBuilder,
                'event_sub_query'
            );
        }

        // add query for free entry
        if ($search->getFreeEntry()) {
            $this->databaseService->addConstraintForEventColumn(
                $subQueryBuilder,
                'free_entry',
                $search->getFreeEntry(),
                \PDO::PARAM_INT,
                $queryBuilder,
                'event_sub_query'
            );
        }

        $queryBuilder
            ->select('day.*')
            ->from('tx_events2_domain_model_day', 'day')
            ->leftJoin(
                'day',
                'tx_events2_domain_model_event',
                'event',
                $queryBuilder->expr()->eq(
                    'day.event',
                    $queryBuilder->quoteIdentifier('event.uid')
                )
            )
            ->orderBy('event.top_of_list', 'DESC')
            ->addOrderBy('day.sort_day_time', 'ASC')
            ->addOrderBy('day.day_time', 'ASC');

        $this->addMergeFeatureToQuery($subQueryBuilder);
        $this->joinSubQueryIntoQueryBuilder($queryBuilder, $subQueryBuilder);

        $extbaseQuery->statement($queryBuilder);

        return $extbaseQuery->execute();
    }

    /**
     * Find all Days for a given Day (Timestamp with time set to 00:00:00).
     *
     * @param int $timestamp
     * @return QueryResultInterface|Day[]
     * @throws \Exception
     */
    public function findByTimestamp(int $timestamp): QueryResultInterface
    {
        /** @var Query $extbaseQuery */
        $extbaseQuery = $this->createQuery();
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_day');
        $this->databaseService->addVisibilityConstraintToQuery($queryBuilder);

        // add storage PID for event and day, but not for sys_category
        $this->databaseService->addConstraintForPid(
            $queryBuilder,
            $extbaseQuery->getQuerySettings()->getStoragePageIds()
        );

        // add categories
        if (!empty($this->settings['categories'])) {
            $this->databaseService->addConstraintForCategories(
                $queryBuilder,
                GeneralUtility::trimExplode(',', $this->settings['categories'], true)
            );
        }

        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                'day.day',
                $queryBuilder->createNamedParameter($timestamp, \PDO::PARAM_INT)
            )
        );

        $queryBuilder
            ->select('day.*')
            ->from('tx_events2_domain_model_day', 'day')
            ->leftJoin(
                'day',
                'tx_events2_domain_model_event',
                'event',
                $queryBuilder->expr()->eq(
                    'day.event',
                    $queryBuilder->quoteIdentifier('event.uid')
                )
            )
            ->orderBy('event.top_of_list', 'DESC')
            ->addOrderBy('day.sort_day_time', 'ASC')
            ->addOrderBy('day.day_time', 'ASC')
            ->groupBy('day.uid'); // keep that because of category relation

        $extbaseQuery->statement($queryBuilder);

        return $extbaseQuery->execute();
    }

    /**
     * Apply various merge features to query
     *
     * @param QueryBuilder $subQueryBuilder
     */
    protected function addMergeFeatureToQuery(QueryBuilder $subQueryBuilder)
    {
        if ((bool)$this->settings['mergeRecurringEvents']) {
            // $queryBuilder->groupBy('day.uid');
            $subQueryBuilder->groupBy('day_sub_query.event');
        } elseif ((bool)$this->settings['mergeEventsAtSameDay']) {
            $subQueryBuilder->groupBy('day_sub_query.event', 'day_sub_query.same_day_time');
        } else {
            $subQueryBuilder->groupBy('day_sub_query.event', 'day_sub_query.sort_day_time');
        }
    }

    /**
     * Join SubQuery as SQL-Part into parent QueryBuilder
     *
     * @param QueryBuilder $queryBuilder
     * @param QueryBuilder $subQueryBuilder
     */
    protected function joinSubQueryIntoQueryBuilder(QueryBuilder $queryBuilder, QueryBuilder $subQueryBuilder)
    {
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
     * Get Sub-QueryBuilder
     *
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder
     */
    protected function getSubQueryBuilder(QueryBuilder $queryBuilder): QueryBuilder
    {
        $subQueryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_day');
        $this->databaseService->addVisibilityConstraintToQuery($subQueryBuilder);
        $subQueryBuilder
            ->selectLiteral('MIN(day_sub_query.day_time) as next_day_time', 'day_sub_query.event')
            ->from('tx_events2_domain_model_day', 'day_sub_query')
            ->leftJoin(
                'day_sub_query',
                'tx_events2_domain_model_event',
                'event_sub_query',
                $queryBuilder->expr()->eq(
                    'day_sub_query.event',
                    $queryBuilder->quoteIdentifier('event_sub_query.uid')
                )
            );

        return $subQueryBuilder;
    }

    /**
     * Find one Day by Event and Timestamp.
     * If timestamp is empty, we try to find next possible day in future/past or build our own one.
     *
     * @param int $eventUid
     * @param int $timestamp
     * @return Day
     * @throws \Exception
     */
    public function findDayByEventAndTimestamp(int $eventUid, int $timestamp = 0): Day
    {
        $dayFactory = $this->objectManager->get(DayFactory::class);
        return $dayFactory->findDayByEventAndTimestamp($eventUid, $timestamp, $this->createQuery());
    }

    /**
     * Get TYPO3s Connection Pool
     *
     * @return ConnectionPool
     */
    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
