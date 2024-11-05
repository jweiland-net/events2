<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use JWeiland\Events2\Domain\Factory\TimeFactory;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Repository\EventRepository;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

/**
 * Service to handle event data.
 * Get next possible day by event.
 * Get events for export.
 */
class EventService
{
    private const TABLE = 'tx_events2_domain_model_event';

    public function __construct(
        protected readonly EventRepository $eventRepository,
        protected readonly TimeFactory $timeFactory,
        protected readonly DataMapper $dataMapper,
    ) {}

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
                    $queryBuilder->quoteIdentifier('d.event')
                ),
            )
            ->where(
                $queryBuilder->expr()->gte(
                    'd.day',
                    $queryBuilder->createNamedParameter(time(), Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->lte(
                    'd.day',
                    $queryBuilder->createNamedParameter((int)$endDate->format('U'), Connection::PARAM_INT),
                ),
            );

        if ($storagePages !== []) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->in(
                        'e.pid',
                        $queryBuilder->createNamedParameter($storagePages, ArrayParameterType::INTEGER),
                    ),
                );
        }

        $queryResult = $queryBuilder->executeQuery();

        $events = [];
        try {
            while ($event = $queryResult->fetchAssociative()) {
                $events[$event['uid']] = current($this->dataMapper->map(Event::class, [$event]));
            }
        } catch (Exception $e) {
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
