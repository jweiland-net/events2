<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Factory;

use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Service\DayRelationService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/*
 * This class contains methods to find a day by a given event and exact timestamp.
 * If day was not found it will automatically search for next day.
 * If day was not found it will automatically search for previous day.
 * If day was not found it will automatically build a temporary day without any DateTime-Objects.
 */
class DayFactory
{
    /**
     * @var DatabaseService
     */
    protected $databaseService;

    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @var DayRelationService
     */
    protected $dayRelationService;

    /**
     * @var array
     */
    protected $processOrderedMethods = [
        'findExactDay',
        'findNextDay',
        'findPreviousDay',
        'buildDay',
    ];

    public function __construct(
        DatabaseService $databaseService,
        EventRepository $eventRepository,
        DayRelationService $dayRelationService
    ) {
        $this->databaseService = $databaseService;
        $this->eventRepository = $eventRepository;
        $this->dayRelationService = $dayRelationService;
    }

    /**
     * Find one Day by Event and Timestamp.
     *
     * @param Event $event
     * @param int $timestamp
     * @param QueryInterface|Query $query
     * @return Day
     */
    public function findDayByEventAndTimestamp(Event $event, int $timestamp, QueryInterface $query): Day
    {
        $day = null;

        foreach ($this->processOrderedMethods as $methodName) {
            if (!method_exists($this, $methodName)) {
                continue;
            }

            $day = $this->{$methodName}($event, $timestamp, $query);
            if ($day instanceof Day) {
                break;
            }
        }

        return $day;
    }

    protected function findExactDay(Event $event, int $timestamp, QueryInterface $query): ?Day
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                'day.day_time',
                $queryBuilder->createNamedParameter(
                    $timestamp,
                    \PDO::PARAM_INT
                )
            )
        );

        return $this->findDayByEvent($event, $queryBuilder, $query);
    }

    protected function findNextDay(Event $event, int $timestamp, QueryInterface $query): ?Day
    {
        $queryBuilder = $this->getQueryBuilder();
        $this->databaseService->addConstraintForDateRange(
            $queryBuilder,
            new \DateTime('now')
        );

        return $this->findDayByEvent($event, $queryBuilder, $query);
    }

    protected function findPreviousDay(Event $event, int $timestamp, QueryInterface $query): ?Day
    {
        $queryBuilder = $this->getQueryBuilder(QueryInterface::ORDER_DESCENDING);
        $this->databaseService->addConstraintForDateRange(
            $queryBuilder,
            new \DateTime('now')
        );

        return $this->findDayByEvent($event, $queryBuilder, $query);
    }

    /**
     * Build day object on our own.
     * It will not get an UID or PID
     *
     * @param Event $event
     * @param int $timestamp
     * @param QueryInterface|Query $query
     * @return Day
     * @throws \Exception
     */
    protected function buildDay(Event $event, int $timestamp, QueryInterface $query): Day
    {
        if (!$event->getEventBegin() instanceof \DateTime) {
            // Normally this can't be thrown, as event begin is a required field.
            throw new \Exception('Given event does not have an event begin date assigned.', 1548927203);
        }

        $event->getDays()->rewind();

        if (!$event->getDays()->count()) {
            // event seems to be out of time frame. Try to re-generate day records
            $this->dayRelationService->addDay($event, $event->getEventBegin());
        }

        $day = null;
        if ($event->getDays()->count()) {
            $event->getDays()->rewind();
            $day = $event->getDays()->current();
        }

        if (!$day instanceof Day) {
            // Only a fallback to be really really safe.
            // Normally this can not be called, as DayRelationService will always assign a Day record to event.
            $day = new Day();
            $day->setEvent($event);
            $day->setDay($event->getEventBegin());
            $day->setDayTime($event->getEventBegin());
            $day->setSortDayTime($event->getEventBegin());
            $day->setSameDayTime($event->getEventBegin());
        }

        return $day;
    }

    /**
     * Find Day record by event and by additional where-constraints
     *
     * @param Event $event
     * @param QueryBuilder $queryBuilder
     * @param QueryInterface|Query $query
     * @return Day|null
     */
    protected function findDayByEvent(Event $event, QueryBuilder $queryBuilder, QueryInterface $query): ?Day
    {
        $this->addBaseConstraint($queryBuilder, $query, $event->getUid());

        $query->statement($queryBuilder);

        $day = $query->execute()->getFirst();
        if ($day instanceof Day) {
            return $day;
        }

        return null;
    }

    /**
     * Get pre initialized QueryBuilder
     * Only the where-part has to be added in later process
     *
     * @param string $order ASC or DESC
     * @return QueryBuilder
     */
    protected function getQueryBuilder(string $order = QueryInterface::ORDER_ASCENDING): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_day');
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
            ->orderBy('day.day_time', $order);

        return $queryBuilder;
    }

    /**
     * Add base constraints (pid and event) which are valid for all queries in this class.
     *
     * @param QueryBuilder $queryBuilder
     * @param QueryInterface $query
     * @param int $eventUid
     */
    protected function addBaseConstraint(QueryBuilder $queryBuilder, QueryInterface $query, int $eventUid): void
    {
        // Add storage PID for event and day, but not for sys_category
        $this->databaseService->addConstraintForPid(
            $queryBuilder,
            $query->getQuerySettings()->getStoragePageIds()
        );

        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                'day.event',
                $queryBuilder->createNamedParameter($eventUid, \PDO::PARAM_INT)
            )
        );
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
