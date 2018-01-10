<?php

namespace JWeiland\Events2\Service;

/*
 * This file is part of the TYPO3 CMS project.
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
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
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
     *
     * @return void
     */
    public function injectExtConf(ExtConf $extConf)
    {
        $this->extConf = $extConf;
    }

    /**
     * inject dayGenerator.
     *
     * @param DayGenerator $dayGenerator
     *
     * @return void
     */
    public function injectDayGenerator(DayGenerator $dayGenerator)
    {
        $this->dayGenerator = $dayGenerator;
    }

    /**
     * inject eventRepository
     *
     * @param EventRepository $eventRepository
     *
     * @return void
     */
    public function injectEventRepository(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    /**
     * inject eventService
     *
     * @param EventService $eventService
     *
     * @return void
     */
    public function injectEventService(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * inject dateTimeUtility.
     *
     * @param DateTimeUtility $dateTimeUtility
     *
     * @return void
     */
    public function injectDateTimeUtility(DateTimeUtility $dateTimeUtility)
    {
        $this->dateTimeUtility = $dateTimeUtility;
    }

    /**
     * inject persistenceManager
     *
     * @param PersistenceManager $persistenceManager
     *
     * @return void
     */
    public function injectPersistenceManager(PersistenceManager $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Create day relations for given event
     *
     * @param int|string $eventUid Maybe starting with NEW
     *
     * @return Event
     *
     * @throws \Exception
     */
    public function createDayRelations($eventUid)
    {
        $event = $this->eventRepository->findByIdentifier((int)$eventUid);
        if (!$event instanceof Event) {
            // write a warning (2) to sys_log
            GeneralUtility::sysLog('Related days could not be created, because of an empty event or a non given event uid or pid', 'events2', 2);
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
     *
     * @return void
     */
    public function addDay(Event $event, \DateTime $dateTime)
    {
        // to prevent adding multiple days for ONE day we set them all to midnight 00:00:00
        $dateTime = $this->dateTimeUtility->standardizeDateTimeObject($dateTime);
        $times = $this->getTimesForDateTime($dateTime, $event);
        if (!empty($times)) {
            foreach ($times as $time) {
                $this->addGeneratedDayToEvent($dateTime, $time, $event);
            }
        } else {
            $this->addGeneratedDayToEvent($dateTime, null, $event);
        }
    }

    /**
     * Each event can have one or more times for one day
     * This method looks into all time related records and fetches the times with highest priority.
     *
     * @param \DateTime $day
     * @param Event $event
     *
     * @return array
     */
    public function getTimesForDateTime(\DateTime $day, Event $event)
    {
        // times from exceptions have priority 1
        if ($event->getExceptions()->count()) {
            $times = [];
            /** @var Exception $exception */
            foreach ($event->getExceptions() as $exception) {
                if (
                    $exception->getExceptionDate() == $day &&
                    $exception->getExceptionTime() instanceof Time &&
                    (
                        $exception->getExceptionType() === 'Add' ||
                        $exception->getExceptionType() === 'Time'
                    )
                ) {
                    $times[] = $exception->getExceptionTime();
                }
            }
            if (!empty($times)) {
                return $times;
            }
        }
        // times from event->differentTimes have priority 2
        $differentTimes = $this->getDifferentTimesForDay($day, $event);
        if (!empty($differentTimes)) {
            return $differentTimes;
        }
        // times from event have priority 3
        $eventTimes = $this->getTimesFromEvent($event);
        if (!empty($eventTimes)) {
            return $eventTimes;
        }

        // if there are no times available return empty array
        return [];
    }

    /**
     * You can override the times in an event for a special weekday
     * so this method checks and returns times, if there are times defined for given day.
     *
     * @param \DateTime $day
     * @param Event $event
     *
     * @return array
     */
    protected function getDifferentTimesForDay(\DateTime $day, Event $event)
    {
        $times = [];
        if (
            $event->getEventType() !== 'single' &&
            $event->getDifferentTimes()->count()
        ) {
            // you only can set different times in case of type "duration" and "recurring". But not: single
            /** @var Time $time */
            foreach ($event->getDifferentTimes() as $time) {
                if (strtolower($time->getWeekday()) === strtolower($day->format('l'))) {
                    $times[] = $time;
                }
            }
        }

        return $times;
    }

    /**
     * Each event has ONE time record, but if checkbox "same day" was checked, you can add additional times
     * This method checks both parts, merges them into an array and returns the result.
     *
     * @param Event $event
     *
     * @return array
     */
    protected function getTimesFromEvent(Event $event)
    {
        $times = [];
        // add normal event time
        if ($event->getEventTime() instanceof Time) {
            $times[] = $event->getEventTime();
        }

        // add value of multiple times
        // but only if checkbox "same day" is set
        // and event type is NOT single
        if (
            $event->getEventType() !== 'single' &&
            $event->getSameDay() &&
            $event->getMultipleTimes()->count()
        ) {
            /** @var Time $multipleTime */
            foreach ($event->getMultipleTimes() as $multipleTime) {
                $times[] = $multipleTime;
            }
        }

        return $times;
    }

    /**
     * Add day record
     *
     * @param \DateTime $dateTime
     * @param Time|null $time
     * @param Event $event
     *
     * @return void
     */
    protected function addGeneratedDayToEvent(\DateTime $dateTime, $time = null, Event $event)
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
     *
     * @return \DateTime
     */
    protected function getDayTime(\DateTime $day, $hour, $minute)
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
     *
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
}
