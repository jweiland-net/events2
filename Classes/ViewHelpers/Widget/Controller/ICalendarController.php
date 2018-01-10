<?php

namespace JWeiland\Events2\ViewHelpers\Widget\Controller;

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
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Service\EventService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ICalendarController extends AbstractWidgetController
{
    /**
     * @var \JWeiland\Events2\Service\EventService
     */
    protected $eventService;

    /**
     * inject Event Service.
     *
     * @param EventService $eventService
     */
    public function injectEventService(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * This index action was called to export an event as ics file.
     *
     * @return string
     */
    public function indexAction()
    {
        // global information
        $day = $this->widgetConfiguration['day'];
        $this->view->assign('PRODID', GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));

        // event information
        $events = $this->getEvents($day);

        $this->view->assign('events', $events);

        $filePath = sprintf(
            'typo3temp/tx_events2/iCal/%s.ics',
            $this->getUniqueIdForDay($day)
        );

        $content = preg_replace('/\h+/', ' ', $this->view->render());
        GeneralUtility::writeFileToTypo3tempDir(PATH_site . $filePath, $content);

        return sprintf(
            '<a href="%s" target="_blank">%s</a>',
            GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $filePath,
            LocalizationUtility::translate('export', 'events2')
        );
    }

    /**
     * Get Events by day
     *
     * @param Day $day
     *
     * @return array
     */
    protected function getEvents(Day $day)
    {
        $events = [];
        switch ($day->getEvent()->getEventType()) {
            case 'duration':
                $firstDay = $this->getFirstDayOfEvent($day->getEvent());
                $lastDay = $this->getLastDayOfEvent($day->getEvent());
                $startTimes = $this->eventService->getTimesForDay($firstDay->getEvent(), $firstDay);
                $endTimes = $this->eventService->getTimesForDay($lastDay->getEvent(), $lastDay);
                $startTimes->rewind();
                $startTime = $startTimes->current();
                $endTimes->rewind();
                $endTime = $endTimes->current();

                $events[] = $this->createEvent(
                    $firstDay,
                    $lastDay,
                    $startTime instanceof Time ? $startTime->getTimeBegin() : '',
                    $endTime instanceof Time ? $endTime->getTimeEnd() : ''
                );
                break;
            case 'recurring':
            case 'single':
            default:
                $times = $this->eventService->getTimesForDay($day->getEvent(), $day);
                if ($times->count()) {
                    /** @var Time $time */
                    foreach ($times as $time) {
                        $events[] = $this->createEvent($day, $day, $time->getTimeBegin(), $time->getTimeEnd());
                    }
                } else {
                    // no time given
                    $events[] = $this->createEvent($day);
                }
                break;
        }
        return $events;
    }

    /**
     * Get first day of event
     * This is needed for events of type "duration"
     *
     * @param Event $event
     *
     * @return Day
     */
    protected function getFirstDayOfEvent(Event $event)
    {
        $days = [];
        /** @var Day $day */
        foreach ($event->getDays() as $day) {
            $days[$day->getDay()->format('U')] = $day;
        }
        ksort($days);

        return reset($days);
    }

    /**
     * Get last day of event
     * This is needed for events of type "duration"
     *
     * @param Event $event
     *
     * @return Day
     */
    protected function getLastDayOfEvent(Event $event)
    {
        $days = [];
        /** @var Day $day */
        foreach ($event->getDays() as $day) {
            $days[$day->getDay()->format('U')] = $day;
        }
        ksort($days);

        return end($days);
    }

    /**
     * create an event array
     * Hint: We can't use DTSTAMP here, because this must be UTC, but we don't have UTC times here.
     *
     * @param Day $day          current Day. In case of duration it will be the first day
     * @param Day $lastDay      current Day. In case of duration it will be the last day
     * @param string $startTime
     * @param string $endTime
     *
     * @return array
     */
    protected function createEvent(Day $day, Day $lastDay = null, $startTime = '', $endTime = '')
    {
        if ($lastDay === null) {
            $lastDay = $day;
        }

        $event = [];
        $event['UID'] = $this->getUniqueIdForDay($day);
        $event['DTSTART'] = $this->convertToTstamp($day->getDay(), $startTime);
        if (!empty($endTime)) {
            $event['DTEND'] = $this->convertToTstamp($lastDay->getDay(), $endTime);
        }
        $event['LOCATION'] = $this->sanitizeString($day->getEvent()->getLocation()->getLocation());
        $event['SUMMARY'] = $this->sanitizeString($day->getEvent()->getTitle());
        $event['DESCRIPTION'] = $this->sanitizeString($day->getEvent()->getDetailInformations());

        return $event;
    }

    /**
     * The uid for ical is not equal to the uid of day
     * It's more a unique string.
     *
     * @param Day $day
     *
     * @return string
     */
    public function getUniqueIdForDay(Day $day)
    {
        return 'event' . uniqid($day->getDay()->format('dmY'), true);
    }

    /**
     * iCal needs a special format for timestamps
     * This method converts a DateTime and a given time string to this format.
     *
     * @param \DateTime $date
     * @param string    $time
     *
     * @return string
     */
    public function convertToTstamp(\DateTime $date, $time = '')
    {
        // don't modify the original Date
        $localDate = clone $date;

        // add hours and minutes to date
        if (!empty($time) && strlen($time) === 5) {
            list($hours, $minutes) = explode(':', $time);
            $diff = new \DateInterval('PT' . (int)$hours . 'H' . (int)$minutes . 'M');
            $localDate->add($diff);
        }

        // set Date to UTC
        $timeStamp = new \DateTimeZone('UTC');
        $localDate->setTimezone($timeStamp);

        return $localDate->format('Ymd\THis\Z');
    }

    /**
     * get formatted time string.
     *
     * @param string $time
     *
     * @return string
     */
    public function getFormattedTime($time = '')
    {
        if (empty($time)) {
            $time = '000000';
        } else {
            $time = str_replace(':', '', $time) . '00';
        }

        return $time;
    }

    /**
     * sanitize Text.
     *
     * @link http://tools.ietf.org/html/rfc5545#page-45
     *
     * @param string $content The text to sanitize for *.ics
     *
     * @return string
     */
    protected function sanitizeString($content)
    {
        // remove tags from content
        $content = htmlspecialchars(strip_tags($content));
        // some chars have to be escaped. See link above
        $content = preg_replace('/([\\\\,;])/', '\\\$1', $content);
        // sanitize all enter chars (vertical white-spaces) to \n
        $content = preg_replace('/\v+/', '\\n', $content);

        return $content;
    }
}
