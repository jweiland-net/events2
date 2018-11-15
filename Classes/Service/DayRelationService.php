<?php

namespace JWeiland\Events2\Service;

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
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * While saving an event in backend, this class generates all the day records
 * and sets them in relation to the event record.
 */
class DayRelationService
{
    /**
     * @var array
     */
    protected $eventRecord = [];

    /**
     * @var ExtConf
     */
    protected $extConf;

    /**
     * @var DayGenerator
     */
    protected $dayGenerator;

    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @var EventService
     */
    protected $eventService;

    /**
     * @var DateTimeUtility
     */
    protected $dateTimeUtility;

    /**
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @var array
     */
    protected $cachedSortDayTime = [];

    /**
     * inject extConf
     *
     * @param ExtConf $extConf
     */
    public function injectExtConf(ExtConf $extConf)
    {
        $this->extConf = $extConf;
    }

    /**
     * inject dayGenerator.
     *
     * @param DayGenerator $dayGenerator
     */
    public function injectDayGenerator(DayGenerator $dayGenerator)
    {
        $this->dayGenerator = $dayGenerator;
    }

    /**
     * inject eventRepository
     *
     * @param EventRepository $eventRepository
     */
    public function injectEventRepository(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    /**
     * inject eventService
     *
     * @param EventService $eventService
     */
    public function injectEventService(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * inject dateTimeUtility.
     *
     * @param DateTimeUtility $dateTimeUtility
     */
    public function injectDateTimeUtility(DateTimeUtility $dateTimeUtility)
    {
        $this->dateTimeUtility = $dateTimeUtility;
    }

    /**
     * inject persistenceManager
     *
     * @param PersistenceManager $persistenceManager
     */
    public function injectPersistenceManager(PersistenceManager $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Create day relations for given event
     *
     * @param int|string $eventUid Maybe starting with NEW
     * @return Event
     * @throws \Exception
     */
    public function createDayRelations($eventUid)
    {
        $event = $this->eventRepository->findByIdentifier((int)$eventUid);
        if (!$event instanceof Event) {
            // write a warning (2) to sys_log
            $this->getLogger()->warning('Related days could not be created, because of an empty event or a non given event uid or pid.');
        } else {
            $event->setDays(new ObjectStorage());

            $this->dayGenerator->initialize($event);
            $dateTimeStorage = $this->dayGenerator->getDateTimeStorage();
            foreach ($dateTimeStorage as $dateTime) {
                $this->addDay($event, $dateTime);
                // in case of recurring event, cached sort_day_time is only valid for one day (same_day-checkbox)
                if ($event->getEventType() === 'recurring') {
                    unset($this->cachedSortDayTime[$event->getUid()]);
                }
            }
            $this->cachedSortDayTime = [];
            $this->persistenceManager->update($event);
            $this->persistenceManager->persistAll();
        }

        return $event;
    }

    /**
     * Add day to db
     * Also MM-Tables will be filled.
     *
     * @param Event $event
     * @param \DateTime $dateTime
     */
    public function addDay(Event $event, \DateTime $dateTime)
    {
        // to prevent adding multiple days for ONE day we set them all to midnight 00:00:00
        $dateTime = $this->dateTimeUtility->standardizeDateTimeObject($dateTime);
        $times = $this->eventService->getTimesForDate($event, $dateTime);
        if ($times->count()) {
            foreach ($times as $time) {
                $this->addGeneratedDayToEvent($dateTime, $event, $time);
            }
        } else {
            $this->addGeneratedDayToEvent($dateTime, $event, null);
        }
    }

    /**
     * Add day record
     *
     * @param \DateTime $dateTime
     * @param Event $event
     * @param Time|null $time
     */
    protected function addGeneratedDayToEvent(\DateTime $dateTime, Event $event, $time = null)
    {
        $hour = $minute = 0;
        if (
            $time instanceof Time &&
            preg_match('@^([0-1][0-9]|2[0-3]):[0-5][0-9]$@', $time->getTimeBegin())
        ) {
            list($hour, $minute) = explode(':', $time->getTimeBegin());
        }

        /** @var Day $day */
        $day = GeneralUtility::makeInstance(Day::class);
        $day->setPid($event->getPid());
        $day->setDay($dateTime);
        $day->setDayTime($this->getDayTime($dateTime, $hour, $minute));
        $day->setSortDayTime($this->getSortDayTime($dateTime, $hour, $minute, $event));
        $day->setEvent($event);

        $event->addDay($day);
    }

    /**
     * Get day time
     * Each individual hour and minute will be added to day
     *
     * Day: 17.01.2017 00:00:00 + 8h + 30m
     * Day: 18.01.2017 00:00:00 + 10h + 15m
     * Day: 19.01.2017 00:00:00 + 9h + 25m
     * Day: 20.01.2017 00:00:00 + 14h + 45m
     *
     * @param \DateTime $day
     * @param int $hour
     * @param int $minute
     * @return \DateTime
     */
    protected function getDayTime(\DateTime $day, $hour, $minute): \DateTime
    {
        // Don't modify original day
        $dayTime = clone $day;
        $dayTime->modify(sprintf(
            '+%d hour +%d minute',
            (int)$hour,
            (int)$minute
        ));
        return $dayTime;
    }

    /**
     * Get timestamp which is the same for all event days of type duration
     * Instead of getDayTime this method will return the same timestamp for all days in event
     *
     * Day: 17.01.2017 00:00:00 + 8h + 30m  = 17.01.2017 08:30:00
     * Day: 18.01.2017 00:00:00 + 10h + 15m = 17.01.2017 08:30:00
     * Day: 19.01.2017 00:00:00 + 9h + 25m  = 17.01.2017 08:30:00
     * Day: 20.01.2017 00:00:00 + 14h + 45m = 17.01.2017 08:30:00
     *
     * @param \DateTime $day
     * @param int $hour
     * @param int $minute
     * @param Event $event
     * @return \DateTime
     */
    protected function getSortDayTime(\DateTime $day, $hour, $minute, Event $event)
    {
        // cachedSortDayTime will be unset for type "recurring" after processing one day
        if (array_key_exists($event->getUid(), $this->cachedSortDayTime)) {
            return $this->cachedSortDayTime[$event->getUid()];
        }

        $sortDayTime = $this->getDayTime($day, $hour, $minute);

        if (in_array($event->getEventType(), ['duration', 'recurring'])) {
            // Group multiple days for duration or group multiple times for one day
            if ($event->getEventType() === 'duration' || $this->extConf->getMergeEvents()) {
                $this->cachedSortDayTime[$event->getUid()] = $sortDayTime;
            }
        }

        return $sortDayTime;
    }

    /**
     * @return Logger
     */
    protected function getLogger()
    {
        return GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }
}
