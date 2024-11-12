<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use JWeiland\Events2\Domain\Factory\TimeFactory;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Repository\EventRepository;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

/*
 * f.e. the time domain model can be connected 4 times with an event (exceptionTimes, differentTimes, MultipleTimes and eventTime)
 * So which time has priority, which time has to be merged
 * This Utility tries to help you/me to make life easier with events2.
 */
class EventService
{
    private const TABLE = 'tx_events2_domain_model_event';

    protected EventRepository $eventRepository;

    protected TimeFactory $timeFactory;

    protected DataMapper $dataMapper;

    /**
     * Must be called by ObjectManager, because of EventRepository which has inject methods
     */
    public function __construct(
        EventRepository $eventRepository,
        TimeFactory $timeFactory,
        DataMapper $dataMapper
    ) {
        $this->eventRepository = $eventRepository;
        $this->timeFactory = $timeFactory;
        $this->dataMapper = $dataMapper;
    }

    public function getNextDayForEvent(int $eventUid): ?\DateTimeImmutable
    {
        /** @var Event|null $event */
        $event = $this->eventRepository->findByIdentifier($eventUid);
        if (!$event instanceof Event) {
            return null;
        }

        $days = $event->getFutureDatesGroupedAndSorted();

        if (!empty($days)) {
            return current($days);
        }

        return null;
    }

    /**
     * Get last day for event
     * Useful to check, if an event is over.
     * Needed by SolrIndexer, as we can't create JOIN Queries in Solr configuration
     */
    public function getLastDayForEvent(int $eventUid): ?\DateTimeImmutable
    {
        /** @var Event $event */
        $event = $this->eventRepository->findByIdentifier($eventUid);
        $days = $event->getFutureDatesGroupedAndSorted();
        krsort($days);
        reset($days);

        $day = current($days);

        return $day ?: null;
    }

    /**
     * @return Event[]
     */
    public function getEventsForExport(array $storagePages, \DateTimeImmutable $endDate): array
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->selectLiteral('DISTINCT e.*')
            ->from(self::TABLE, 'e')
            ->leftJoin(
                'e',
                'tx_events2_domain_model_day',
                'd',
                $queryBuilder->expr()->eq(
                    'e.uid',
                    $queryBuilder->quoteIdentifier('d.event'),
                ),
            )
            ->where(
                $queryBuilder->expr()->gte(
                    'd.day',
                    $queryBuilder->createNamedParameter(time(), \PDO::PARAM_INT),
                ),
                $queryBuilder->expr()->lte(
                    'd.day',
                    $queryBuilder->createNamedParameter((int)$endDate->format('U'), \PDO::PARAM_INT),
                ),
            );

        if ($storagePages !== []) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->in(
                        'e.pid',
                        $queryBuilder->createNamedParameter($storagePages, Connection::PARAM_INT_ARRAY),
                    ),
                );
        }

        $statement = $queryBuilder->execute();

        $events = [];
        while ($event = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $events[$event['uid']] = current($this->dataMapper->map(Event::class, [$event]));
        }

        return $events;
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

        return $queryBuilder;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
