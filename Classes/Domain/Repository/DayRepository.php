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
use JWeiland\Events2\Persistence\Typo384\Generic\Query;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
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
     * Sets the settings
     *
     * @param array $settings
     * @return void
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
     * @param int $limit
     * @return QueryResultInterface
     * @throws \Exception
     */
    public function findEvents(string $type, Filter $filter, int $limit = 0): QueryResultInterface
    {
        /** @var Query $extbaseQuery */
        $extbaseQuery = $this->createQuery();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_events2_domain_model_day');
        $subQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_events2_domain_model_day');

        $constraints = [];

        // add storage PID for event and day, but not for sys_category
        $constraints[] = $this->getConstraintsForPid($queryBuilder, $extbaseQuery);

        // add categories
        if (!empty($this->settings['categories'])) {
            $constraints[] = $this->getConstraintsForCategories($queryBuilder);
        }

        // add filter for organizer
        if ($filter->getOrganizer() || $this->settings['preFilterByOrganizer']) {
            $constraints[] = $this->getConstraintsForOrganizer(
                $queryBuilder,
                $filter->getOrganizer() ?: $this->settings['preFilterByOrganizer']
            );
        }

        if (!empty($limit)) {
            $queryBuilder->setMaxResults((int)$limit);
        }

        $subQueryBuilder
            ->selectLiteral('MIN(sub_d.day_time) as next_day_time', 'MIN(sub_d.sort_day_time) as next_sort_day_time', 'sub_d.day', 'sub_d.day_time', 'sub_d.event')
            ->from('tx_events2_domain_model_day', 'sub_d')
            ->where($this->getConstraintsForDate($subQueryBuilder, $type, 'sub_d'));

        // @ToDo: merge events
        if ($this->settings['showOnlyNextEvent']) {
            $subQueryBuilder->groupBy('sub_d.event');
        } else {
            $subQueryBuilder->groupBy('sub_d.event', 'sub_d.sort_day_time');
        }

        $constraints[] = $this->getConstraintsForDate($queryBuilder, $type);
        $constraints[] = $queryBuilder->expr()->andX(
            $queryBuilder->expr()->eq(
                'd.event',
                $queryBuilder->quoteIdentifier('dg.event')
            ),
            $queryBuilder->expr()->eq(
                'd.day_time',
                $queryBuilder->quoteIdentifier('dg.next_day_time')
            ),
            $queryBuilder->expr()->eq(
                'd.sort_day_time',
                $queryBuilder->quoteIdentifier('dg.next_sort_day_time')
            )
        );

        $queryBuilder
            ->select('d.*')
            ->from('tx_events2_domain_model_day', 'd')
            ->leftJoin(
                'd',
                'tx_events2_domain_model_event',
                'e',
                $queryBuilder->expr()->eq(
                    'd.event',
                    $queryBuilder->quoteIdentifier('e.uid')
                )
            )
            ->getConcreteQueryBuilder()->leftJoin(
                $queryBuilder->quoteIdentifier('d'),
                sprintf('(%s)', $subQueryBuilder->getSQL()),
                $queryBuilder->quoteIdentifier('dg'),
                $queryBuilder->expr()->eq(
                    'd.event',
                    $queryBuilder->quoteIdentifier('dg.event')
                )
            )
            ->where(...$constraints)
            ->orderBy('e.top_of_list', 'DESC')
            ->addOrderBy('d.sort_day_time', 'ASC')
            ->addOrderBy('d.day_time', 'ASC');

        $extbaseQuery->statement($queryBuilder);

        return $extbaseQuery->execute();
    }

    /**
     * Get constraints for pid
     *
     * @param QueryBuilder $queryBuilder
     * @param QueryInterface $extbaseQuery
     * @return CompositeExpression
     */
    protected function getConstraintsForPid(QueryBuilder $queryBuilder, QueryInterface $extbaseQuery)
    {
        return $queryBuilder->expr()->andX(
            $queryBuilder->expr()->in(
                'd.pid',
                $queryBuilder->createNamedParameter(
                    $extbaseQuery->getQuerySettings()->getStoragePageIds(),
                    Connection::PARAM_INT_ARRAY
                )
            ),
            $queryBuilder->expr()->in(
                'e.pid',
                $queryBuilder->createNamedParameter(
                    $extbaseQuery->getQuerySettings()->getStoragePageIds(),
                    Connection::PARAM_INT_ARRAY
                )
            )
        );
    }

    /**
     * Get constraints for categories
     *
     * @param QueryBuilder $queryBuilder
     * @return string
     */
    protected function getConstraintsForCategories(QueryBuilder $queryBuilder)
    {
        $queryBuilder->leftJoin(
            'e',
            'sys_category_record_mm',
            'cmm',
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq(
                    'e.uid',
                    $queryBuilder->quoteIdentifier('cmm.uid_foreign')
                ),
                $queryBuilder->expr()->eq(
                    'cmm.tablenames',
                    $queryBuilder->createNamedParameter('tx_events2_domain_model_event', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'cmm.fieldname',
                    $queryBuilder->createNamedParameter('categories', \PDO::PARAM_STR)
                )
            )
        );

        return $queryBuilder->expr()->in(
            'cmm.uid_local',
            $queryBuilder->createNamedParameter(
                GeneralUtility::intExplode(',', $this->settings['categories'], true),
                Connection::PARAM_INT_ARRAY
            )
        );
    }

    /**
     * Get constraints for organizer
     *
     * @param QueryBuilder $queryBuilder
     * @param $organizerUid
     * @return string
     */
    protected function getConstraintsForOrganizer(QueryBuilder $queryBuilder, $organizerUid)
    {
        $queryBuilder->leftJoin(
            'd',
            'tx_events2_domain_model_organizer',
            'o',
            $queryBuilder->expr()->eq(
                'e.organizer',
                $queryBuilder->quoteIdentifier('o.uid')
            )
        );

        return $queryBuilder->expr()->eq(
            'o.uid',
            $queryBuilder->createNamedParameter($organizerUid, \PDO::PARAM_INT)
        );
    }

    /**
     * Get constraints for date by type
     *
     * @param QueryBuilder $queryBuilder
     * @param string $type
     * @param string $alias
     * @return CompositeExpression
     */
    protected function getConstraintsForDate(QueryBuilder $queryBuilder, string $type, string $alias = 'd')
    {
        $dateConstraints = [];

        switch ($type) {
            case 'today':
                $today = $this->dateTimeUtility->convert('today');
                $tomorrow = $this->dateTimeUtility->convert('today');
                $tomorrow->modify('+1 day');

                $dateConstraints[] = $queryBuilder->expr()->gte(
                    $alias . '.day',
                    $queryBuilder->createNamedParameter($today->format('U'), \PDO::PARAM_INT, ':eventStartDate')
                );
                $dateConstraints[] = $queryBuilder->expr()->lt(
                    $alias . '.day',
                    $queryBuilder->createNamedParameter($tomorrow->format('U'), \PDO::PARAM_INT, ':eventEndDate')
                );
                break;
            case 'range':
                $today = $this->dateTimeUtility->convert('today');
                $in4months = $this->dateTimeUtility->convert('today');
                $in4months->modify('+4 weeks');
                $dateConstraints[] = $queryBuilder->expr()->gte(
                    $alias . '.day',
                    $queryBuilder->createNamedParameter($today->format('U'), \PDO::PARAM_INT, ':eventStartDate')
                );
                $dateConstraints[] = $queryBuilder->expr()->lt(
                    $alias . '.day',
                    $queryBuilder->createNamedParameter($in4months->format('U'), \PDO::PARAM_INT, ':eventEndDate')
                );
                break;
            case 'thisWeek':
                $weekStart = $this->dateTimeUtility->convert('today');
                $weekStart->modify('this week'); // 'first day of' does not work for 'weeks'
                $weekEnd = $this->dateTimeUtility->convert('today');
                $weekEnd->modify('this week +6 days'); // 'last day of' does not work for 'weeks'
                $dateConstraints[] = $queryBuilder->expr()->gte(
                    $alias . '.day',
                    $queryBuilder->createNamedParameter($weekStart->format('U'), \PDO::PARAM_INT, ':eventStartDate')
                );
                $dateConstraints[] = $queryBuilder->expr()->lt(
                    $alias . '.day',
                    $queryBuilder->createNamedParameter($weekEnd->format('U'), \PDO::PARAM_INT, ':eventEndDate')
                );
                break;
            case 'latest':
            case 'list':
            default:
                if ($this->extConf->getRecurringPast() === 0) {
                    // including current time as events in past are not allowed to be displayed
                    $today = new \DateTime('now');
                } else {
                    // exclude current time. Start with 00:00:00
                    $today = $this->dateTimeUtility->convert('today');
                }
                $dateConstraints[] = $queryBuilder->expr()->gte(
                    $alias . '.day_time',
                    $queryBuilder->createNamedParameter($today->format('U'), \PDO::PARAM_INT, ':eventStartDate')
                );
        }

        return $queryBuilder->expr()->andX(...$dateConstraints);
    }

    /**
     * Find events
     *
     * @param string $type
     * @param Filter $filter
     * @param int $limit
     * @return QueryResultInterface
     * @throws \Exception
     */
    public function findOriginalEvents(string $type, Filter $filter, int $limit = 0): QueryResultInterface
    {
        /** @var Query $query */
        $query = $this->createQuery();
        $this->addGroupingToQuery($query);
        $constraints = [];

        // add categories
        if (!empty($this->settings['categories'])) {
            $constraints[] = $query->in('event.categories.uid', GeneralUtility::intExplode(',', $this->settings['categories'], true));
        }

        // add storage PIDs. But not for sys_category
        // @link: https://forge.typo3.org/issues/83296
        $query->getQuerySettings()->setRespectStoragePage(false);
        $constraints[] = $query->in('pid', $query->getQuerySettings()->getStoragePageIds());
        $constraints[] = $query->in('event.pid', $query->getQuerySettings()->getStoragePageIds());

        // add filter for organizer
        if ($filter->getOrganizer()) {
            $constraints[] = $query->equals('event.organizer', $filter->getOrganizer());
        } elseif ($this->settings['preFilterByOrganizer']) {
            $constraints[] = $query->equals('event.organizer', $this->settings['preFilterByOrganizer']);
        }

        switch ($type) {
            case 'today':
                $today = $this->dateTimeUtility->convert('today');
                $tomorrow = $this->dateTimeUtility->convert('today');
                $tomorrow->modify('+1 day');
                $constraints[] = $query->greaterThanOrEqual('day', $today);
                $constraints[] = $query->lessThan('day', $tomorrow);
                break;
            case 'range':
                $today = $this->dateTimeUtility->convert('today');
                $in4months = $this->dateTimeUtility->convert('today');
                $in4months->modify('+4 weeks');
                $constraints[] = $query->greaterThanOrEqual('day', $today);
                $constraints[] = $query->lessThanOrEqual('day', $in4months);
                break;
            case 'thisWeek':
                $weekStart = $this->dateTimeUtility->convert('today');
                $weekStart->modify('this week'); // 'first day of' does not work for 'weeks'
                $weekEnd = $this->dateTimeUtility->convert('today');
                $weekEnd->modify('this week +6 days'); // 'last day of' does not work for 'weeks'
                $constraints[] = $query->greaterThanOrEqual('day', $weekStart);
                $constraints[] = $query->lessThanOrEqual('day', $weekEnd);
                break;
            case 'latest':
            case 'list':
            default:
                if ($this->extConf->getRecurringPast() === 0) {
                    // including current time as events in past are not allowed to be displayed
                    $today = new \DateTime('now');
                } else {
                    // exclude current time. Start with 00:00:00
                    $today = $this->dateTimeUtility->convert('today');
                }
                $constraints[] = $query->greaterThanOrEqual('dayTime', $today);
        }

        if (!empty($limit)) {
            $query->setLimit((int)$limit);
        }

        return $query->matching($query->logicalAnd($constraints))->execute();
    }

    /**
     * This is a very special method for list views.
     * It groups day records by event and keeps the event with earliest date in array
     *
     * That way we prevent the problem with SQL, where GROUP BY was executed before ORDER BY and we may get a
     * random value for day, day_time and sort_day_time.
     *
     * In short: This method takes 15 records from DB and groups them. Now we may have 4 records left. Start again
     * and take next 15 records from DB, group them and add them to Collection. Repeat it as often until
     * $maxRecords is reached.
     *
     * @param QueryResultInterface $queryResult
     * @param int $maxRecords
     * @return Day[]
     * @throws \Exception
     */
    public function groupDaysByEventAndSort(QueryResultInterface $queryResult, int $maxRecords)
    {
        $collectedDays = [];
        $reset = true;
        $limit = 15;
        $offset = 0;
        $query = $queryResult->getQuery();

        do {
            // Get first or next 15 records
            $records = $query
                ->setLimit($limit)
                ->setOffset($offset)
                ->execute();

            if (!$records->count()) {
                break;
            }

            /** @var Day $record */
            foreach ($records as $record) {
                // add record to collectedDays as long as $maxRecords has not been reached
                if (count($collectedDays) < $maxRecords && !array_key_exists($record->getEvent()->getUid(), $collectedDays)) {
                    $collectedDays[$record->getEvent()->getUid()] = $record;
                }

                // replace older with earlier record, if event exists in day array
                if (array_key_exists($record->getEvent()->getUid(), $collectedDays)) {
                    /** @var Day $day */
                    $day = $collectedDays[$record->getEvent()->getUid()];
                    if ($record->getSortDayTime() < $day->getSortDayTime()) {
                        $collectedDays[$record->getEvent()->getUid()] = $record;
                    }
                }
                // we can not break out of this foreach/do loop as we have to find ALL related day records
                // maybe there are some more collectedDays with an earlier date
            }

            $offset += 15;
            if (count($collectedDays) === (int)$maxRecords && $reset) {
                // as some customers may have thousands of day records we reduce the records to the just known events,
                // if count() matches $maxRecords and resets the offset
                $query->matching($query->logicalAnd([
                    $query->getConstraint(),
                    $query->in('event.uid', array_keys($collectedDays))
                ]));
                $offset = 0;
                $reset = false;
            }
        } while (1 == 1);

        return $this->sortDays($collectedDays);
    }

    /**
     * This method belongs to groupDaysByEventAndSort
     * and is only valid for latest view
     *
     * @param Day[] $records
     * @param string $sortBy
     * @return Day[]
     * @throws \Exception
     */
    protected function sortDays(array $records, string $sortBy = 'day'): array
    {
        $dates = [];

        $getter = 'get' . ucfirst($sortBy);
        if (!method_exists(Day::class, $getter)) {
            throw new \Exception('Method "' . $getter . '" does not exists in Day', 1499429014);
        }

        foreach ($records as $key => $record) {
            $dates[$key] = $record->{$getter}()->format('U');
        }
        array_multisort($dates, SORT_ASC, SORT_NUMERIC, $records);

        return $records;
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
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Query $query */
        $query = $this->createQuery();
        $constraints = [];

        // add storage PIDs. But not for sys_category
        // @link: https://forge.typo3.org/issues/83296
        $query->getQuerySettings()->setRespectStoragePage(false);
        $constraints[] = $query->in('pid', $query->getQuerySettings()->getStoragePageIds());
        $constraints[] = $query->in('event.pid', $query->getQuerySettings()->getStoragePageIds());

        // add query for search string
        if ($search->getSearch()) {
            $orConstraints = [];
            $orConstraints[] = $query->like('event.title', '%' . $search->getSearch() . '%');
            $orConstraints[] = $query->like('event.teaser', '%' . $search->getSearch() . '%');
            $constraints[] = $query->logicalOr($orConstraints);
        }

        // add query for categories
        if ($search->getMainCategory()) {
            if ($search->getSubCategory()) {
                $constraints[] = $query->contains('event.categories', $search->getSubCategory()->getUid());
            } else {
                $constraints[] = $query->contains('event.categories', $search->getMainCategory()->getUid());
            }
        } elseif ($this->settings['categories']) {
            // visitor has not selected any category. Search within allowed categories in plugin configuration
            $constraints[] = $query->in('event.categories.uid', GeneralUtility::trimExplode(',', $this->settings['categories']));
        }

        // add query for event begin
        if ($search->getEventBegin()) {
            $constraints[] = $query->greaterThanOrEqual('day', $search->getEventBegin());
        } else {
            $today = $this->dateTimeUtility->convert('today');
            $constraints[] = $query->greaterThanOrEqual('day', $today);
        }

        // add query for event end
        if ($search->getEventEnd()) {
            $constraints[] = $query->lessThanOrEqual('day', $search->getEventEnd());
        }

        // add query for event location
        if ($search->getLocation()) {
            $constraints[] = $query->equals('event.location', $search->getLocation()->getUid());
        }

        // add query for free entry
        if ($search->getFreeEntry()) {
            $constraints[] = $query->equals('event.freeEntry', $search->getFreeEntry());
        }

        if (count($constraints)) {
            return $query->matching($query->logicalAnd($constraints))->execute();
        } else {
            return $query->execute();
        }
    }

    /**
     * Find day by UID
     *
     * @param int $day
     * @return Day
     */
    public function findByDay(int $day): Day
    {
        /** @var \JWeiland\Events2\Persistence\Typo384\Generic\Query $query */
        $query = $this->createQuery();
        $this->addGroupingToQuery($query);
        $query->matching($query->equals('uid', (int)$day));
        /** @var Day $day */
        $day = $query->execute()->getFirst();
        return $day;
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
        $constraints = [];
        $query = $this->createQuery();

        // add storage PIDs. But not for sys_category
        // @link: https://forge.typo3.org/issues/83296
        $query->getQuerySettings()->setRespectStoragePage(false);
        $constraints[] = $query->in('pid', $query->getQuerySettings()->getStoragePageIds());
        $constraints[] = $query->in('event.pid', $query->getQuerySettings()->getStoragePageIds());

        $this->addGroupingToQuery($query);
        if (!empty($this->settings['categories'])) {
            $constraints[] = $query->in('event.categories.uid', GeneralUtility::intExplode(',', $this->settings['categories']));
        }
        $constraints[] = $query->equals('day', $timestamp);

        /** @var QueryResult $result */
        $result = $query->matching($query->logicalAnd($constraints))->execute();

        return $result;
    }

    /**
     * Find one Day by Event and Timestamp.
     * Instead of findByTimestamp this Timestamp must include the exact time.
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
        $query = $this->createQuery();

        $constraints = [];
        // add storage PIDs. But not for sys_category
        // @link: https://forge.typo3.org/issues/83296
        $query->getQuerySettings()->setRespectStoragePage(false);
        $constraints[] = $query->in('pid', $query->getQuerySettings()->getStoragePageIds());
        $constraints[] = $query->in('event.pid', $query->getQuerySettings()->getStoragePageIds());
        $constraints[] = $query->equals('event', (int)$eventUid);

        if (empty($timestamp)) {
            $today = new \DateTime('now');
            $constraints[] = $query->greaterThanOrEqual('dayTime', $today);

            $query->setOrderings([
                'dayTime' => QueryInterface::ORDER_ASCENDING
            ]);
        } else {
            $constraints[] = $query->equals('dayTime', $timestamp);
        }

        /** @var Day $day */
        $day = $query->matching($query->logicalAnd($constraints))->execute()->getFirst();

        return $day;
    }

    /**
     * Add special grouping
     *
     * @param QueryInterface $query
     */
    protected function addGroupingToQuery(QueryInterface $query)
    {
        /** @var Query $query */
        //if ($this->settings['mergeEvents']) {
        if ($this->settings['showOnlyNextEvent']) {
            // merge: We want one day record for each event
            $query->setGroupings(['event']);
        } else {
            // default. We want individual day records for each event
            $query->setGroupings(['event', 'sortDayTime']);
        }
    }
}
