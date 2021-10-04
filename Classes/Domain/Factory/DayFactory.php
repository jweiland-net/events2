<?php
declare(strict_types=1);
namespace JWeiland\Events2\Domain\Factory;

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
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Service\DayRelationService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
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
     * @var array
     */
    protected $processOrderedMethods = [
        'findExactDay',
        'findNextDay',
        'findPreviousDay',
        'buildDay',
    ];

    /**
     * inject databaseService
     *
     * @param DatabaseService $databaseService
     */
    public function injectDatabaseService(DatabaseService $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    /**
     * Find one Day by Event and Timestamp.
     *
     * @param int $eventUid
     * @param int $timestamp
     * @param QueryInterface|Query $query
     * @return Day
     */
    public function findDayByEventAndTimestamp(int $eventUid, int $timestamp, QueryInterface $query): Day
    {
        $day = null;
        $data = [
            'event' => $eventUid,
            'timestamp' => $timestamp
        ];

        foreach ($this->processOrderedMethods as $methodName) {
            if (!method_exists($this, $methodName)) {
                continue;
            }

            $day = $this->{$methodName}($data, $query);
            if ($day instanceof Day) {
                break;
            }
        }

        return $day;
    }

    /**
     * Find exact matching day record
     *
     * @param array $searchValues
     * @param QueryInterface|Query $query
     * @return Day|null
     */
    protected function findExactDay(array $searchValues, QueryInterface $query)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                'day.day_time',
                $queryBuilder->createNamedParameter(
                    $searchValues['timestamp'],
                    \PDO::PARAM_INT
                )
            )
        );

        return $this->findDayByEvent(
            $searchValues['event'],
            $queryBuilder,
            $query
        );
    }

    /**
     * Find next matching day record
     *
     * @param array $searchValues
     * @param QueryInterface|Query $query
     * @return Day|null
     */
    protected function findNextDay(array $searchValues, QueryInterface $query)
    {
        $queryBuilder = $this->getQueryBuilder();
        $this->databaseService->addConstraintForDateRange(
            $queryBuilder,
            new \DateTime('now')
        );

        return $this->findDayByEvent(
            $searchValues['event'],
            $queryBuilder,
            $query
        );
    }

    /**
     * Find previous matching day record
     *
     * @param array $searchValues
     * @param QueryInterface|Query $query
     * @return Day|null
     */
    protected function findPreviousDay(array $searchValues, QueryInterface $query)
    {
        $queryBuilder = $this->getQueryBuilder(QueryInterface::ORDER_DESCENDING);
        $this->databaseService->addConstraintForDateRange(
            $queryBuilder,
            new \DateTime('now')
        );

        return $this->findDayByEvent(
            $searchValues['event'],
            $queryBuilder,
            $query
        );
    }

    /**
     * Build day object on our own.
     * It will not get an UID or PID
     *
     * @param array $searchValues
     * @param QueryInterface|Query $query
     * @return Day
     * @throws \Exception
     */
    protected function buildDay(array $searchValues, QueryInterface $query): Day
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $eventRepository = $objectManager->get(EventRepository::class);
        $dayRelationService = $objectManager->get(DayRelationService::class);

        /** @var Event|null $event */
        $event = $eventRepository->findByIdentifier($searchValues['event']);
        if (!$event instanceof Event) {
            // Normally this can't be thrown, as this class will only be called at a detail page.
            // So action controller will throw Exception first, if event is not given.
            throw new \Exception('Given event could not be found in DayFactory', 1548927197);
        }

        if (!$event->getEventBegin() instanceof \DateTime) {
            // Normally this can't be thrown, as event begin is a required field.
            throw new \Exception('Given event does not have an event begin date assigned.', 1548927203);
        }

        $event->getDays()->rewind();

        if (!$event->getDays()->count()) {
            // event seems to be out of time frame. Try to re-generate day records
            $dayRelationService->addDay($event, $event->getEventBegin());
        }

        $event->getDays()->rewind();
        $day = $event->getDays()->current();

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
     * @param int $eventUid
     * @param QueryBuilder $queryBuilder
     * @param QueryInterface|Query $query
     * @return Day|null
     */
    protected function findDayByEvent(int $eventUid, QueryBuilder $queryBuilder, QueryInterface $query)
    {
        $this->addBaseConstraint($queryBuilder, $query, $eventUid);

        $query->statement($queryBuilder);

        /** @var Day $day */
        $day = $query->execute()->getFirst();

        return $day;
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
    protected function addBaseConstraint(QueryBuilder $queryBuilder, QueryInterface $query, int $eventUid)
    {
        // add storage PID for event and day, but not for sys_category
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
