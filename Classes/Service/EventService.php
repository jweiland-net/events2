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

/*
 * f.e. the time domain model can be connected 4 times with an event (exceptionTimes, differentTimes, MultipleTimes and eventTime)
 * So which time has priority, which time has to be merged
 * This Utility tries to help you/me to make life easier with events2.
 */
class EventService
{
    protected EventRepository $eventRepository;

    protected TimeFactory $timeFactory;

    public function __construct(
        EventRepository $eventRepository,
        TimeFactory $timeFactory
    ) {
        $this->eventRepository = $eventRepository;
        $this->timeFactory = $timeFactory;
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
}
