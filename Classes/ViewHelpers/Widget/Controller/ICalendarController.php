<?php

declare(strict_types = 1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\ViewHelpers\Widget\Controller;

use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Service\EventService;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController;

/*
 * A Fluid widget to create a link for downloading an iCal file
 */
class ICalendarController extends AbstractWidgetController
{
    /**
     * @var EventService
     */
    protected $eventService;

    public function injectEventService(EventService $eventService): void
    {
        $this->eventService = $eventService;
    }

    /**
     * This index action was called to export an event as ics file.
     *
     * @return string
     */
    public function indexAction(): string
    {
        // global information
        $this->view->assign('PRODID', GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
        $day = $this->widgetConfiguration['day'];
        if (!$day instanceof Day) {
            // never try to generate an iCal export for an event without a day record
            return '';
        }

        // event information
        $events = $this->getEvents($day);

        $this->view->assign('events', $events);

        $filePath = sprintf(
            'typo3temp/tx_events2/iCal/%s.ics',
            $this->getUniqueIdForDay($day)
        );

        $content = preg_replace('/\h+/', ' ', $this->view->render());
        GeneralUtility::writeFileToTypo3tempDir(Environment::getPublicPath() . '/' . $filePath, $content);

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
     * @return array
     */
    protected function getEvents(Day $day): array
    {
        $events = [];
        switch ($day->getEvent()->getEventType()) {
            case 'duration':
                $firstDay = $this->getFirstDayOfEvent($day->getEvent());
                $lastDay = $this->getLastDayOfEvent($day->getEvent());
                $startTimes = $this->eventService->getTimesForDate($firstDay->getEvent(), $firstDay->getDay());
                $endTimes = $this->eventService->getTimesForDate($lastDay->getEvent(), $lastDay->getDay());
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
                $times = $this->eventService->getTimesForDate($day->getEvent(), $day->getDay());
                if ($times->count()) {
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
     * @return Day|null
     */
    protected function getFirstDayOfEvent(Event $event)
    {
        $days = [];
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
     * @return Day|null
     */
    protected function getLastDayOfEvent(Event $event)
    {
        $days = [];
        foreach ($event->getDays() as $day) {
            $days[$day->getDay()->format('U')] = $day;
        }
        ksort($days);

        return end($days);
    }

    /**
     * Create an event array
     * Hint: We can't use DTSTAMP here, because this must be UTC, but we don't have UTC times here.
     *
     * @param Day $day current Day. In case of duration it will be the first day
     * @param Day $lastDay current Day. In case of duration it will be the last day
     * @param string $startTime Something like 15:30
     * @param string $endTime Something like 17:30
     * @return array
     */
    protected function createEvent(Day $day, Day $lastDay = null, string $startTime = '', string $endTime = ''): array
    {
        if ($lastDay === null) {
            $lastDay = $day;
        }

        $event = [];
        $event['UID'] = $this->getUniqueIdForDay($day);
        $event['DTSTART'] = $this->convertToTstamp($day->getDay(), $startTime);
        if ($lastDay instanceof Day) {
            if (empty($endTime)) {
                $endTime = '23:59';
            }
            $event['DTEND'] = $this->convertToTstamp($lastDay->getDay(), $endTime);
        }
        $event['LOCATION'] = $day->getEvent()->getLocationAsString();
        $event['SUMMARY'] = $this->sanitizeString($day->getEvent()->getTitle());
        $event['DESCRIPTION'] = $this->sanitizeString($day->getEvent()->getDetailInformations());

        return $event;
    }

    /**
     * The uid for ical is not equal to the uid of day
     * It's more a unique string.
     *
     * @param Day $day
     * @return string
     */
    protected function getUniqueIdForDay(Day $day): string
    {
        return 'event' . uniqid($day->getDay()->format('dmY'), true);
    }

    /**
     * iCal needs a special format for timestamps
     * This method converts a DateTime and a given time string to this format.
     * iCal supports 3 different formats. Please keep current format, as this is the only one Outlook supports.
     *
     * @param \DateTime $date
     * @param string $time
     * @return string
     */
    protected function convertToTstamp(\DateTime $date, string $time = ''): string
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
     * Get formatted time string.
     *
     * @param string $time
     * @return string
     */
    protected function getFormattedTime(string $time = ''): string
    {
        if (empty($time)) {
            $time = '000000';
        } else {
            $time = str_replace(':', '', $time) . '00';
        }

        return $time;
    }

    /**
     * Sanitize Text.
     *
     * @link http://tools.ietf.org/html/rfc5545#page-45
     *
     * @param string $content The text to sanitize for *.ics
     * @return string
     */
    protected function sanitizeString(string $content): string
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
