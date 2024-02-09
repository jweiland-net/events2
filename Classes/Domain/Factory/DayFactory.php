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
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Service\DatabaseService;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * This class contains methods to find a day by a given event and exact timestamp.
 * If day was not found it will automatically search for next day.
 * If day was not found it will automatically search for previous day.
 * If day was not found it will automatically build a temporary day without any DateTime-Objects.
 */
class DayFactory
{
    protected array $processOrderedMethods = [
        'findExactDay',
        'findNextDay',
        'findPreviousDay',
        'buildDay',
    ];

    public function __construct(
        protected readonly DatabaseService $databaseService,
        protected readonly DayRepository $dayRepository,
        protected readonly EventRepository $eventRepository
    ) {}

    /**
     * Find one Day by Event and Timestamp.
     */
    public function findDayByEventAndTimestamp(int $eventUid, int $timestamp, QueryInterface $query): Day
    {
        $day = null;
        $data = [
            'event' => $eventUid,
            'timestamp' => $timestamp,
        ];

        foreach ($this->processOrderedMethods as $methodName) {
            $day = $this->{$methodName}($data, $query);
            if ($day instanceof Day) {
                break;
            }
        }

        return $day;
    }

    protected function findExactDay(array $searchValues, QueryInterface $query): ?Day
    {
        $queryBuilder = $this->getQueryBuilder($query, $searchValues['event']);
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                'day.day_time',
                $queryBuilder->createNamedParameter(
                    $searchValues['timestamp'],
                    Connection::PARAM_INT
                )
            )
        );

        return $this->findDayByQueryBuilder($queryBuilder);
    }

    protected function findNextDay(array $searchValues, QueryInterface $query): ?Day
    {
        $date = new \DateTimeImmutable('now');

        $queryBuilder = $this->getQueryBuilder($query, $searchValues['event']);

        $queryBuilder->expr()->gte(
            'day.day_time',
            $queryBuilder->createNamedParameter((int)$date->format('U'), Connection::PARAM_INT)
        );

        return $this->findDayByQueryBuilder($queryBuilder);
    }

    protected function findPreviousDay(array $searchValues, QueryInterface $query): ?Day
    {
        $date = new \DateTimeImmutable('now');

        $queryBuilder = $this->getQueryBuilder($query, $searchValues['event'], QueryInterface::ORDER_DESCENDING);

        $queryBuilder->expr()->lte(
            'day.day_time',
            $queryBuilder->createNamedParameter((int)$date->format('U'), Connection::PARAM_INT)
        );

        return $this->findDayByQueryBuilder($queryBuilder);
    }

    /**
     * Build day object on our own.
     * It will not get an UID or PID
     *
     * @throws \Exception
     */
    protected function buildDay(array $searchValues): Day
    {
        /** @var Event|null $event */
        $event = $this->eventRepository->findByIdentifier($searchValues['event']);
        if (!$event instanceof Event) {
            // Normally this can't be thrown, as this class will only be called at a detail page.
            // So action controller will throw Exception first, if event is not given.
            throw new \Exception('Given event could not be found in DayFactory', 1548927197);
        }

        if (!$event->getEventBegin() instanceof \DateTimeImmutable) {
            // Normally this can't be thrown, as event begin is a required field.
            throw new \Exception('Given event does not have an event begin date assigned.', 1548927203);
        }

        $day = null;
        if ($event->getDays()->count()) {
            $event->getDays()->rewind();
            $day = $event->getDays()->current();
        }

        if (!$day instanceof Day) {
            // Only a fallback to be really safe.
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
     * Find Day record by given QueryBuilder constraint
     */
    protected function findDayByQueryBuilder(QueryBuilder $queryBuilder): ?Day
    {
        $dayRecord = $queryBuilder
            ->executeQuery()
            ->fetchAssociative();

        $day = null;
        if (is_array($dayRecord) && isset($dayRecord['uid'])) {
            $day = $this->dayRepository->findByIdentifier((int)$dayRecord['uid']);
        }

        return $day instanceof Day ? $day : null;
    }

    protected function getQueryBuilder(
        QueryInterface $query,
        int $eventUid,
        string $order = QueryInterface::ORDER_ASCENDING
    ): QueryBuilder {
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

        $this->addBaseConstraint($queryBuilder, $query, $eventUid);

        return $queryBuilder;
    }

    /**
     * Add base constraints (pid and event) which are valid for all queries in this class.
     */
    protected function addBaseConstraint(QueryBuilder $queryBuilder, QueryInterface $query, int $eventUid): void
    {
        // add storage PID for event and day, but not for sys_category
        $this->databaseService->addConstraintForPid(
            $queryBuilder,
            $query->getQuerySettings()->getStoragePageIds()
        );

        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                'day.event',
                $queryBuilder->createNamedParameter($eventUid, Connection::PARAM_INT)
            )
        );
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
