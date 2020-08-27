<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

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

/*
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
     * @var \DateTime
     */
    protected $firstDateTime;

    /**
     * @var Time|null
     */
    protected $firstTime;

    public function __construct(
        ExtConf $extConf,
        DayGenerator $dayGenerator,
        DateTimeUtility $dateTimeUtility
    ) {
        $this->extConf = $extConf;
        $this->dayGenerator = $dayGenerator;
        $this->dateTimeUtility = $dateTimeUtility;
    }

    public function injectEventRepository(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    public function injectEventService(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    public function injectPersistenceManager(PersistenceManager $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Delete all related day records of given event and
     * start re-creating the day records.
     *
     * @param int $eventUid Event UID. This also can be a hidden event.
     * @return Event
     * @throws \Exception
     */
    public function createDayRelations(int $eventUid): ?Event
    {
        // As create/update action will set event as hidden, we have to search for them, too.
        $event = $this->eventRepository->findHiddenEntry($eventUid);
        if (!$event instanceof Event) {
            // write a warning (2) to sys_log
            $this->getLogger()->warning('Related days could not be created, because of an empty event or a non given event uid or pid.');
        } else {
            // Delete all days from Repo and DB
            $event->setDays(new ObjectStorage());
            $this->persistenceManager->update($event);
            $this->persistenceManager->persistAll();

            // Create days from scratch and store to DB
            $this->dayGenerator->initialize($event);
            $dateTimeStorage = $this->dayGenerator->getDateTimeStorage();
            foreach ($dateTimeStorage as $dateTime) {
                if ($this->firstDateTime === null) {
                    $this->firstDateTime = $dateTime;
                }
                $this->addDay($event, $dateTime);
            }
            $this->firstDateTime = null;
            $this->persistenceManager->update($event);
            $this->persistenceManager->persistAll();
        }

        return $event;
    }

    /**
     * Add day to DB
     * Also MM-Tables will be filled.
     *
     * @param Event $event
     * @param \DateTime $dateTime
     */
    public function addDay(Event $event, \DateTime $dateTime): void
    {
        // to prevent adding multiple day records for ONE day we set them all to midnight 00:00:00
        $dateTime = $this->dateTimeUtility->standardizeDateTimeObject($dateTime);
        $times = $this->eventService->getTimesForDate($event, $dateTime);
        if ($times->count()) {
            foreach ($times as $time) {
                if ($this->firstTime === null) {
                    $this->firstTime = $time;
                }
                $this->addGeneratedDayToEvent($dateTime, $event, $time);
            }

            // sort_day_time of all duration days have to be the same value, so do not reset this value in that case.
            if ($event->getEventType() !== 'duration') {
                $this->firstTime = null;
            }
        } else {
            $this->addGeneratedDayToEvent($dateTime, $event, null);
        }
    }

    protected function addGeneratedDayToEvent(\DateTime $dateTime, Event $event, ?Time $time = null)
    {
        list($hour, $minute) = $this->getHourAndMinuteFromTime($time);

        /** @var Day $day */
        $day = GeneralUtility::makeInstance(Day::class);
        $day->setPid($event->getPid());
        $day->setDay($dateTime);
        $day->setDayTime($this->getDayTime($dateTime, (int)$hour, (int)$minute));
        $day->setSortDayTime($this->getSortDayTime($dateTime, (int)$hour, (int)$minute, $event));
        $day->setSameDayTime($this->getSameDayTime($dateTime, (int)$hour, (int)$minute, $event));
        $day->setEvent($event);

        $event->addDay($day);
    }

    /**
     * Analyze for valid time value like "21:40" and return exploded time parts: hour (21) and minute (40)
     *
     * @param Time|null $time
     * @return array
     */
    protected function getHourAndMinuteFromTime(?Time $time = null): array
    {
        $hourAndMinute = [0, 0];
        if (
            $time instanceof Time &&
            preg_match('@^([0-1][0-9]|2[0-3]):[0-5][0-9]$@', $time->getTimeBegin())
        ) {
            $hourAndMinute = explode(':', $time->getTimeBegin());
        }
        return $hourAndMinute;
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
    protected function getDayTime(\DateTime $day, int $hour, int $minute): \DateTime
    {
        // Don't modify original day
        $dayTime = clone $day;
        $dayTime->modify(sprintf(
            '+%d hour +%d minute',
            $hour,
            $minute
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
    protected function getSortDayTime(\DateTime $day, int $hour, int $minute, Event $event): \DateTime
    {
        if ($event->getEventType() === 'duration') {
            list($hour, $minute) = $this->getHourAndMinuteFromTime($this->firstTime);
            $sortDayTime = $this->getDayTime($this->firstDateTime, (int)$hour, (int)$minute);
        } else {
            $sortDayTime = $this->getDayTime($day, $hour, $minute);
        }

        return $sortDayTime;
    }

    /**
     * Get timestamp which is the same for all time records of same day.
     * This column is only needed if mergeEventsAtSameTime is set.
     * It helps to GROUP BY these records in SQL statement.
     *
     * Day: 17.01.2017 00:00:00 + 8h + 30m  = 17.01.2017 08:30:00
     * Day: 17.01.2017 00:00:00 + 10h + 15m = 17.01.2017 08:30:00
     * Day: 18.01.2017 00:00:00 + 8h + 30m  = 18.01.2017 08:30:00
     * Day: 28.01.2017 00:00:00 + 10h + 15m = 18.01.2017 08:30:00
     *
     * @param \DateTime $day
     * @param int $hour
     * @param int $minute
     * @param Event $event
     * @return \DateTime
     */
    protected function getSameDayTime(\DateTime $day, int $hour, int $minute, Event $event): \DateTime
    {
        if ($event->getEventType() !== 'duration') {
            list($hour, $minute) = $this->getHourAndMinuteFromTime($this->firstTime);
        }

        return $this->getSortDayTime($day, (int)$hour, (int)$minute, $event);
    }

    protected function getLogger(): Logger
    {
        return GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }
}
