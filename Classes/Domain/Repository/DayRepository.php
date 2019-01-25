<?php
declare(strict_types=1);
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
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Model\Search;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
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
     * @var array
     */
    protected $defaultOrderings = [
        'event.topOfList' => QueryInterface::ORDER_DESCENDING,
        'sortDayTime' => QueryInterface::ORDER_ASCENDING,
        'dayTime' => QueryInterface::ORDER_ASCENDING
    ];

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
     * @return QueryResultInterface
     * @throws \Exception
     */
    public function findEvents(string $type, Filter $filter, int $limit = 0): QueryResultInterface
    {
        /** @var Query $extbaseQuery */
        $extbaseQuery = $this->createQuery();
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_day');
        $subQueryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_day');

        $constraints = [];

        // add storage PID for event and day, but not for sys_category
        $constraints[] = $this->databaseService->getConstraintForPid(
            $queryBuilder,
            $extbaseQuery->getQuerySettings()->getStoragePageIds()
        );

        // add categories
        if (!empty($this->settings['categories'])) {
            $constraints[] = $this->databaseService->getConstraintForCategories(
                $queryBuilder,
                GeneralUtility::trimExplode(',', $this->settings['categories'], true)
            );
        }

        // add filter for organizer
        if ($filter->getOrganizer() || $this->settings['preFilterByOrganizer']) {
            $constraints[] = $this->databaseService->getConstraintForOrganizer(
                $queryBuilder,
                (int)$filter->getOrganizer() ?: (int)$this->settings['preFilterByOrganizer']
            );
        }

        $constraints[] = $this->databaseService->getConstraintForDate($queryBuilder, $type);
        $this->databaseService->initializeSubQueryBuilder(
            $queryBuilder,
            $subQueryBuilder,
            $this->databaseService->getConstraintForDate($subQueryBuilder, $type, 'day_sub_query'),
            (bool)$this->settings['mergeRecurringEvents'],
            (bool)$this->settings['mergeEventsAtSameDay']
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
            ->where(...$constraints)
            ->orderBy('event.top_of_list', 'DESC')
            ->addOrderBy('day.sort_day_time', 'ASC')
            ->addOrderBy('day.day_time', 'ASC');

        if (!empty($limit)) {
            $queryBuilder->setMaxResults((int)$limit);
        }

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
        $subQueryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_day');

        $constraints = [];

        // add storage PID for event and day, but not for sys_category
        $constraints[] = $this->databaseService->getConstraintForPid(
            $queryBuilder,
            $extbaseQuery->getQuerySettings()->getStoragePageIds()
        );

        // add query for search string
        if ($search->getSearch()) {
            $constraints[] = (string)$queryBuilder->expr()->orX(
                $queryBuilder->expr()->like(
                    'event.title',
                    $queryBuilder->quote('%' . $search->getSearch() . '%')
                ),
                $queryBuilder->expr()->like(
                    'event.teaser',
                    $queryBuilder->quote('%' . $search->getSearch() . '%')
                )
            );
        }

        // add query for categories
        if ($search->getMainCategory()) {
            if ($search->getSubCategory()) {
                $constraints[] = $this->databaseService->getConstraintForCategories(
                    $queryBuilder,
                    [$search->getSubCategory()->getUid()]
                );
            } else {
                $constraints[] = $this->databaseService->getConstraintForCategories(
                    $queryBuilder,
                    [$search->getMainCategory()->getUid()]
                );
            }
        } elseif ($this->settings['categories']) {
            // visitor has not selected any category. Search within allowed categories in plugin configuration
            $constraints[] = $this->databaseService->getConstraintForCategories(
                $queryBuilder,
                GeneralUtility::trimExplode(',', $this->settings['categories'])
            );
        }

        $today = $this->dateTimeUtility->convert('today');
        $startDateTime = $search->getEventBegin() ?: $today;
        $endDateTime = $search->getEventEnd();

        // add startDate and endDate to QueryBuilder
        $constraints[] = $this->databaseService->getConstraintForDateRange(
            $queryBuilder,
            $startDateTime,
            $endDateTime
        );
        // add startDate and endDate to SubQueryBuilder
        $this->databaseService->initializeSubQueryBuilder(
            $queryBuilder,
            $subQueryBuilder,
            $this->databaseService->getConstraintForDateRange(
                $subQueryBuilder,
                $startDateTime,
                $endDateTime,
                'day_sub_query'
            ),
            (bool)$this->settings['mergeRecurringEvents'],
            (bool)$this->settings['mergeEventsAtSameDay']
        );

        // add query for event location
        if ($search->getLocation()) {
            $constraints[] = $this->databaseService->getConstraintForLocation(
                $queryBuilder,
                $search->getLocation()->getUid()
            );
        }

        // add query for free entry
        if ($search->getFreeEntry()) {
            $constraints[] = $this->databaseService->getConstraintForEventColumn(
                $queryBuilder,
                'free_entry',
                \PDO::PARAM_INT
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
            ->where(...$constraints)
            ->orderBy('event.top_of_list', 'DESC')
            ->addOrderBy('day.sort_day_time', 'ASC')
            ->addOrderBy('day.day_time', 'ASC');

        $extbaseQuery->statement($queryBuilder);

        return $extbaseQuery->execute();
    }

    /**
     * Find all Days for a given Day (Timestamp with time set to 00:00:00).
     *
     * @param int $timestamp
     * @return QueryResultInterface
     * @throws \Exception
     */
    public function findByTimestamp(int $timestamp): QueryResultInterface
    {
        /** @var Query $extbaseQuery */
        $extbaseQuery = $this->createQuery();
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_day');
        $subQueryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_day');

        $constraints = [];

        // add storage PID for event and day, but not for sys_category
        $constraints[] = $this->databaseService->getConstraintForPid(
            $queryBuilder,
            $extbaseQuery->getQuerySettings()->getStoragePageIds()
        );

        $this->databaseService->initializeSubQueryBuilder(
            $queryBuilder,
            $subQueryBuilder,
            '',
            (bool)$this->settings['mergeRecurringEvents'],
            (bool)$this->settings['mergeEventsAtSameDay']
        );

        // add categories
        if (!empty($this->settings['categories'])) {
            $constraints[] = $this->databaseService->getConstraintForCategories(
                $queryBuilder,
                GeneralUtility::trimExplode(',', $this->settings['categories'], true)
            );
        }

        $constraints[] = $queryBuilder->expr()->eq(
            'day.day',
            $queryBuilder->createNamedParameter($timestamp, \PDO::PARAM_INT)
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
            ->where(...$constraints)
            ->orderBy('event.top_of_list', 'DESC')
            ->addOrderBy('day.sort_day_time', 'ASC')
            ->addOrderBy('day.day_time', 'ASC');

        $extbaseQuery->statement($queryBuilder);

        return $extbaseQuery->execute();
    }

    /**
     * Find one Day by Event and Timestamp.
     * Instead of findByTimestamp this Timestamp must include the exact time (hours/minutes after 00:00).
     *
     * If timestamp is empty, we try to find next possible day in future.
     *
     * @param int $eventUid
     * @param int $timestamp
     * @return Day|null
     * @throws \Exception
     */
    public function findOneByTimestamp(int $eventUid, int $timestamp = 0)
    {
        /** @var Query $extbaseQuery */
        $extbaseQuery = $this->createQuery();
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_day');

        $constraints = [];

        // add storage PID for event and day, but not for sys_category
        $constraints[] = $this->databaseService->getConstraintForPid(
            $queryBuilder,
            $extbaseQuery->getQuerySettings()->getStoragePageIds()
        );

        $constraints[] = $queryBuilder->expr()->eq(
            'day.event',
            $queryBuilder->createNamedParameter($eventUid, \PDO::PARAM_INT)
        );

        if (empty($timestamp)) {
            $constraints[] = $this->databaseService->getConstraintForDateRange(
                $queryBuilder,
                new \DateTime('now')
            );
            $queryBuilder->orderBy('day.day_time', 'ASC');
        } else {
            $constraints[] = $queryBuilder->expr()->eq(
                'day.day_time',
                $queryBuilder->createNamedParameter($timestamp, \PDO::PARAM_INT)
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
            ->where(...$constraints)
            ->orderBy('event.top_of_list', 'DESC')
            ->addOrderBy('day.sort_day_time', 'ASC')
            ->addOrderBy('day.day_time', 'ASC');

        $extbaseQuery->statement($queryBuilder);

        /** @var Day $day */
        $day = $extbaseQuery->execute()->getFirst();

        return $day;
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
