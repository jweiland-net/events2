<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Repository;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Factory\DayFactory;
use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Search;
use JWeiland\Events2\Event\ModifyQueriesOfFindEventsEvent;
use JWeiland\Events2\Event\ModifyQueriesOfSearchEventsEvent;
use JWeiland\Events2\Event\ModifyStartEndDateForListTypeEvent;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/*
 * Repository to get and find day records from storage
 */
class DayRepository extends AbstractRepository
{
    public const TABLE = 'tx_events2_domain_model_day';

    protected DateTimeUtility $dateTimeUtility;

    protected ExtConf $extConf;

    protected DatabaseService $databaseService;

    protected DayFactory $dayFactory;

    protected EventDispatcher $eventDispatcher;

    protected array $settings = [];

    public function injectExtConf(ExtConf $extConf): void
    {
        $this->extConf = $extConf;
    }

    public function injectDateTimeUtility(DateTimeUtility $dateTimeUtility): void
    {
        $this->dateTimeUtility = $dateTimeUtility;
    }

    public function injectDatabaseService(DatabaseService $databaseService): void
    {
        $this->databaseService = $databaseService;
    }

    public function injectDayFactory(DayFactory $dayFactory): void
    {
        $this->dayFactory = $dayFactory;
    }

    public function injectEventDispatcher(EventDispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * @return QueryResultInterface|Day[]
     * @throws \Exception
     */
    public function getDaysForListType(string $listType, Filter $filter, int $limit = 0): QueryResultInterface
    {
        /** @var Query $extbaseQuery */
        $extbaseQuery = $this->createQuery();
        $queryBuilder = $this->getQueryBuilderForTable('tx_events2_domain_model_day', 'day');
        $subQueryBuilder = $this->getSubQueryBuilder($queryBuilder);

        // add storage PID for event and day, but not for sys_category
        $this->databaseService->addConstraintForPid(
            $queryBuilder,
            $extbaseQuery->getQuerySettings()->getStoragePageIds()
        );
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
        if (
            (
                isset($this->settings['preFilterByOrganizer'])
                && (int)$this->settings['preFilterByOrganizer'] !== 0
            )
            || $filter->getOrganizer()
        ) {
            $this->databaseService->addConstraintForOrganizer(
                $subQueryBuilder,
                (int)($this->settings['preFilterByOrganizer'] ?: $filter->getOrganizer()),
                $queryBuilder,
                'event_sub_query'
            );
        }

        if ($filter->getTimestamp()) {
            // Add constraint for a specific day.
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'day.day',
                    $queryBuilder->createNamedParameter($filter->getTimestamp(), \PDO::PARAM_INT)
                )
            );
        } else {
            // Add constraint for date by given listType
            $this->addConstraintForDate(
                $subQueryBuilder,
                $listType,
                $queryBuilder,
                'day_sub_query'
            );
        }

        $queryBuilder
            ->select('day.*')
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

        $this->overlayHelper->addWhereForOverlay(
            $queryBuilder,
            'tx_events2_domain_model_event',
            'event'
        );

        if ($limit !== 0) {
            $queryBuilder->setMaxResults($limit);
        }

        $this->addMergeFeatureToQuery($subQueryBuilder);
        $this->eventDispatcher->dispatch(
            new ModifyQueriesOfFindEventsEvent($queryBuilder, $subQueryBuilder, $listType, $filter, $this->settings)
        );
        $this->joinSubQueryIntoQueryBuilder($queryBuilder, $subQueryBuilder);
        $extbaseQuery->statement($queryBuilder);

        return $extbaseQuery->execute();
    }

    /**
     * Search for events.
     *
     * @throws \Exception
     */
    public function searchEvents(Search $search): QueryResultInterface
    {
        /** @var Query $extbaseQuery */
        $extbaseQuery = $this->createQuery();
        $queryBuilder = $this->getQueryBuilderForTable('tx_events2_domain_model_day', 'day', true);
        $subQueryBuilder = $this->getSubQueryBuilder($queryBuilder, true);

        // add storage PID for event and day, but not for sys_category
        $this->databaseService->addConstraintForPid(
            $subQueryBuilder,
            $search->getStoragePids() ?: $extbaseQuery->getQuerySettings()->getStoragePageIds(),
            $queryBuilder,
            '_sub_query'
        );

        // add query for search string
        if ($search->getSearch() !== '') {
            $subQueryBuilder->andWhere(
                (string)$subQueryBuilder->expr()->orX(
                    $queryBuilder->expr()->like(
                        'event_sub_query.title',
                        $queryBuilder->quote('%' . $search->getSearch() . '%')
                    ),
                    $subQueryBuilder->expr()->like(
                        'event_sub_query.teaser',
                        $queryBuilder->quote('%' . $search->getSearch() . '%')
                    ),
                    $subQueryBuilder->expr()->like(
                        'event_sub_query.detail_information',
                        $queryBuilder->quote('%' . $search->getSearch() . '%')
                    )
                )
            );
        }

        // add query for categories
        if ($search->getMainCategory() instanceof Category) {
            if ($search->getSubCategory() instanceof Category) {
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
        } elseif (($this->settings['categories'] ?? '') !== '') {
            // visitor has not selected any category. Search within allowed categories in plugin configuration
            $this->databaseService->addConstraintForCategories(
                $subQueryBuilder,
                GeneralUtility::trimExplode(',', $this->settings['categories']),
                $queryBuilder,
                'event_sub_query'
            );
        }

        $startDateTime = $search->getEventBegin() ?: $this->dateTimeUtility->convert('today');
        if ($startDateTime instanceof \DateTimeImmutable) {
            // add startDate and endDate
            $this->databaseService->addConstraintForDateRange(
                $subQueryBuilder,
                $startDateTime,
                $search->getEventEnd(),
                $queryBuilder,
                'day_sub_query'
            );
        }

        // add query for event location
        if ($search->getLocation() instanceof Location) {
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

        if (!empty($search->getLimit())) {
            $queryBuilder->setMaxResults($search->getLimit());
        }

        $this->addMergeFeatureToQuery($subQueryBuilder);
        $this->eventDispatcher->dispatch(
            new ModifyQueriesOfSearchEventsEvent($queryBuilder, $subQueryBuilder, $search, $this->settings)
        );
        $this->joinSubQueryIntoQueryBuilder($queryBuilder, $subQueryBuilder);
        $extbaseQuery->statement($queryBuilder);

        return $extbaseQuery->execute();
    }

    public function addConstraintForDate(
        QueryBuilder $queryBuilder,
        string $listType,
        QueryBuilder $parentQueryBuilder = null,
        string $alias = 'day'
    ): void {
        $startDateTime = $this->dateTimeUtility->convert('today');
        $endDateTime = $this->dateTimeUtility->convert('today');
        if (
            !$startDateTime instanceof \DateTimeImmutable
            || !$endDateTime instanceof \DateTimeImmutable
        ) {
            return;
        }

        switch ($listType) {
            case 'listToday':
                $endDateTime = $endDateTime->modify('23:59:59');
                break;
            case 'listRange':
                // @ToDo: Implement start-/end-date from settings
                $endDateTime = $endDateTime->modify('+4 weeks');
                break;
            case 'listWeek':
                // 'first day of' does not work for 'weeks'. Using 'this week' jumps to first day of week. Monday
                $startDateTime = $startDateTime->modify('this week');
                $endDateTime = $endDateTime->modify('this week +6 days');
                break;
            case 'listLatest':
            case 'list':
            default:
                $endDateTime = null;
        }

        // Use current DateTime as StartDateTime, if it is not allowed to have StartDateTime in the past.
        if (
            $this->extConf->getRecurringPast() === 0
            && ($currentDateTime = $this->dateTimeUtility->convert('now'))
            && $currentDateTime instanceof \DateTimeImmutable
            && $startDateTime < $currentDateTime
        ) {
            $startDateTime = $currentDateTime;
        }

        /** @var ModifyStartEndDateForListTypeEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ModifyStartEndDateForListTypeEvent($listType, $startDateTime, $endDateTime, $this->settings)
        );

        $this->databaseService->addConstraintForDateRange(
            $queryBuilder,
            $event->getStartDateTime(),
            $event->getEndDateTime(),
            $parentQueryBuilder,
            $alias
        );
    }

    /**
     * ->select() and ->groupBy() has to be the same in DB configuration
     * where only_full_group_by is activated.
     *
     * @param array $additionalColumns Must contain table: [table].[column]
     * @return array
     */
    protected function getColumnsForDayTable(array $additionalColumns = []): array
    {
        $columns = [];
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_day');
        if ($connection->getSchemaManager() instanceof AbstractSchemaManager) {
            $dayColumns = array_map(
                static fn ($column): string => 'day.' . $column,
                array_keys(
                    $connection->getSchemaManager()->listTableColumns('tx_events2_domain_model_day') ?? []
                )
            );
            $columns = array_merge($dayColumns, $additionalColumns);
        }

        return $columns;
    }

    /*
     * Apply various merge features to query
     */
    protected function addMergeFeatureToQuery(QueryBuilder $subQueryBuilder): void
    {
        if (($this->settings['mergeRecurringEvents'] ?? '0') === '1') {
            $subQueryBuilder->groupBy('day_sub_query.event');
        } elseif (($this->settings['mergeEventsAtSameDay'] ?? '0') === '1') {
            $subQueryBuilder->groupBy('day_sub_query.event', 'day_sub_query.same_day_time');
        } else {
            $subQueryBuilder->groupBy('day_sub_query.event', 'day_sub_query.sort_day_time');
        }
    }

    /*
     * Join SubQuery as SQL-Part into parent QueryBuilder
     */
    protected function joinSubQueryIntoQueryBuilder(QueryBuilder $queryBuilder, QueryBuilder $subQueryBuilder): void
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
     * Find one Day by Event and Timestamp.
     * If timestamp is empty, we try to find next possible day in future/past or build our own one.
     *
     * @throws \Exception
     */
    public function findDayByEventAndTimestamp(int $eventUid, int $timestamp = 0): Day
    {
        return $this->dayFactory->findDayByEventAndTimestamp($eventUid, $timestamp, $this->createQuery());
    }

    /*
     * Nearly the same as "findDayByEventAndTimestamp", but this method was used by PageTitleProvider
     * which is out of Extbase context. So we are using a plain Doctrine Query here.
     */
    public function getDayRecord(int $eventUid, int $timestamp): array
    {
        $queryBuilder = $this->getQueryBuilderForTable('tx_events2_domain_model_day', 'day');
        $day = $queryBuilder
            ->select('day.event')
            ->where(
                $queryBuilder->expr()->eq(
                    'day.event',
                    $queryBuilder->createNamedParameter($eventUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'day.day_time',
                    $queryBuilder->createNamedParameter($timestamp, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch(\PDO::FETCH_ASSOC);

        if (empty($day)) {
            $day = [];
        }
        return $day;
    }

    /*
     * Get Sub-QueryBuilder
     */
    protected function getSubQueryBuilder(QueryBuilder $queryBuilder, bool $useStrictLang = false): QueryBuilder
    {
        $subQueryBuilder = $this->getQueryBuilderForTable(
            'tx_events2_domain_model_day',
            'day_sub_query',
            $useStrictLang
        );

        $subQueryBuilder
            ->selectLiteral('MIN(day_sub_query.day_time) as next_day_time', 'day_sub_query.event')
            ->leftJoin(
                'day_sub_query',
                'tx_events2_domain_model_event',
                'event_sub_query',
                $queryBuilder->expr()->eq(
                    'day_sub_query.event',
                    $queryBuilder->quoteIdentifier('event_sub_query.uid')
                )
            );

        $this->overlayHelper->addWhereForOverlay(
            $subQueryBuilder,
            'tx_events2_domain_model_event',
            'event_sub_query',
            $useStrictLang
        );

        return $subQueryBuilder;
    }

    public function removeAllByEventRecord(array $eventRecord): void
    {
        $eventUid = (int)($eventRecord['uid'] ?? 0);
        if ($eventUid === 0) {
            return;
        }

        $connection = $this->getConnectionPool()->getConnectionForTable(self::TABLE);
        $connection->delete(
            self::TABLE,
            [
                'event' => $eventUid,
                't3ver_wsid' => $eventRecord['t3ver_wsid'] ?? 0
            ]
        );
    }

    public function createAll(array $days, array $columnsToWrite = []): void
    {
        if ($days === []) {
            return;
        }

        $fallbackColumns = [
            'pid',
            'tstamp',
            'crdate',
            'hidden',
            'fe_group',
            't3ver_wsid',
            'day',
            'day_time',
            'sort_day_time',
            'same_day_time',
            'is_removed_date',
            'event'
        ];

        $this
            ->getConnectionPool()
            ->getConnectionForTable(self::TABLE)
            ->bulkInsert(
                self::TABLE,
                $days,
                $columnsToWrite ?: $fallbackColumns
            );
    }
}
