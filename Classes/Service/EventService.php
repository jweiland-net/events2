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
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Domain\Repository\EventRepository;

/*
 * f.e. the time domain model can be connected 4 times with an event (exceptionTimes, differentTimes, MultipleTimes and eventTime)
 * So which time has priority, which time has to be merged
 * This Utility tries to help you/me to make life easier with events2.
 */
class EventService
{
    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @var TimeFactory
     */
    protected $timeFactory;

    public function __construct(
        EventRepository $eventRepository,
        TimeFactory $timeFactory
    ) {
        $this->eventRepository = $eventRepository;
        $this->timeFactory = $timeFactory;
    }

    /**
     * Each event can have one or more times for one day
     * This method looks into all time related records and fetches the times with highest priority.
     *
     * @param Event $event
     * @param \DateTime $date
     * @return \SplObjectStorage|Time[]
     * @deprecated
     */
    public function getTimesForDate(Event $event, \DateTime $date): \SplObjectStorage
    {
        trigger_error('getTimesForDate() will be removed in events2 7.0.0. Please use TimeFactory instead.', E_USER_DEPRECATED);
        return $this->timeFactory->getTimesForDate($event, $date);
    }

    /**
     * Get sorted times for specified date
     *
     * @param Event $event
     * @param \DateTime $date
     * @return \SplObjectStorage|Time[]
     * @deprecated
     */
    public function getSortedTimesForDate(Event $event, \DateTime $date): \SplObjectStorage
    {
        trigger_error('getSortedTimesForDate() will be removed in events2 7.0.0. Please use TimeFactory instead.', E_USER_DEPRECATED);
        return $this->timeFactory->getSortedTimesForDate($event, $date);
    }

    /**
     * Get Time object for a given day record, if exists
     *
     * @param Day $day
     * @return Time|null
     * @deprecated
     */
    public function getTimeForDay(Day $day): ?Time
    {
        trigger_error('getTimeForDay() will be removed in events2 7.0.0. Please use TimeFactory instead.', E_USER_DEPRECATED);
        return $this->timeFactory->getTimeForDay($day);
    }

    /**
     * Get exceptions for given day
     * You can limit the result by a given type.
     *
     * @param Event $event
     * @param \DateTime $date
     * @param string $exceptionTypes Type like Add, Remove, Time or Info. If empty add all exceptions
     * @return \SplObjectStorage|Exception[]
     * @deprecated
     */
    public function getExceptionsForDate(
        Event $event,
        \DateTime $date,
        string $exceptionTypes = ''
    ): \SplObjectStorage {
        trigger_error('getExceptionsForDate() will be removed in events2 7.0.0. Please use getExceptionsForDate from Event Model instead.', E_USER_DEPRECATED);
        return $event->getExceptionsForDate($date, $exceptionTypes);
    }

    public function getNextDayForEvent(int $eventUid): ?\DateTime
    {
        /** @var Event $event */
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
     *
     * @param int $eventUid
     * @return \DateTime|null
     */
    public function getLastDayForEvent(int $eventUid): ?\DateTime
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
