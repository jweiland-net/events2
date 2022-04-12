<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Helper;

use JWeiland\Events2\Domain\Factory\TimeFactory;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Event\PostProcessICalRowsForICalDownloadEvent;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * Helper class to build an ical export
 */
class ICalendarHelper
{
    protected array $iCalHeader = [
        0 => 'BEGIN:VCALENDAR'
    ];

    protected array $iCalFooter = [
        0 => 'END:VCALENDAR'
    ];

    protected string $iCalVersion = '2.0';

    protected string $lineBreak = LF;

    protected TimeFactory $timeFactory;

    protected DateTimeUtility $dateTimeUtility;

    protected EventDispatcher $eventDispatcher;

    public function __construct(
        TimeFactory $eventService,
        DateTimeUtility $dateTimeUtility,
        EventDispatcher $eventDispatcher
    ) {
        $this->timeFactory = $eventService;
        $this->dateTimeUtility = $dateTimeUtility;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function buildICalExport(Day $day, array $parameters = []): string
    {
        $this->lineBreak = $parameters['lineBreak'] ?? $this->lineBreak;

        $iCalRows = [];
        $this->addICalHeader($iCalRows);
        $this->addICalVersion($iCalRows);
        $this->addICalProdId($iCalRows);

        $this->addICalEvent($iCalRows, $day);

        $this->addICalFooter($iCalRows);

        return implode($this->lineBreak, $iCalRows);
    }

    protected function addICalHeader(array &$iCal): void
    {
        array_push($iCal, ...$this->iCalHeader);
    }

    protected function addICalVersion(array &$iCal): void
    {
        $iCal[] = 'VERSION:' . $this->iCalVersion;
    }

    protected function addICalProdId(array &$iCal): void
    {
        $iCal[] = 'PRODID:' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
    }

    protected function addICalFooter(array &$iCal): void
    {
        array_push($iCal, ...$this->iCalFooter);
    }

    protected function addICalEvent(array &$iCal, Day $day): void
    {
        $iCalRowsForAnEvent = [];
        $this->addEventBegin($iCalRowsForAnEvent);
        $this->addEventUid($iCalRowsForAnEvent, $day);
        $this->addEventDateTime($iCalRowsForAnEvent, $day);
        $this->addEventLocation($iCalRowsForAnEvent, $day);
        $this->addEventSummary($iCalRowsForAnEvent, $day);
        $this->addEventDescription($iCalRowsForAnEvent, $day);
        $this->addEventEnd($iCalRowsForAnEvent);

        $this->eventDispatcher->dispatch(
            new PostProcessICalRowsForICalDownloadEvent($iCalRowsForAnEvent, $day)
        );

        array_push($iCal, ...$iCalRowsForAnEvent);
    }

    protected function addEventBegin(array &$event): void
    {
        $event[] = 'BEGIN:VEVENT';
    }

    protected function addEventUid(array &$event, Day $day): void
    {
        $event[] = 'UID:' . 'event' . uniqid($day->getDay()->format('dmY'), true);
    }

    protected function addEventDateTime(array &$event, Day $day): void
    {
        [$dateStart, $dateEnd] = $this->getStartAndEndDateForDay($day);

        $event[] = 'DTSTART:' . $dateStart;
        if ($dateEnd) {
            $event[] = 'DTEND:' . $dateEnd;
        }
    }

    protected function addEventLocation(array &$event, Day $day): void
    {
        if ($day->getEvent()->getLocationAsString()) {
            $event[] = 'LOCATION:' . $day->getEvent()->getLocationAsString();
        }
    }

    protected function addEventSummary(array &$event, Day $day): void
    {
        $event[] = 'SUMMARY:' . $this->sanitizeString($day->getEvent()->getTitle());
    }

    protected function addEventDescription(array &$event, Day $day): void
    {
        $description = $this->sanitizeString($day->getEvent()->getDetailInformation());
        if ($description) {
            $event[] = 'DESCRIPTION:' . $this->sanitizeString($day->getEvent()->getDetailInformation());
        }
    }

    protected function addEventEnd(array &$event): void
    {
        $event[] = 'END:VEVENT';
    }

    /**
     * @return string[]
     */
    protected function getStartAndEndTimeForDay(Day $day): array
    {
        $timeStart = '00:00:00';
        $timeEnd = '23:59:59';
        $time = $this->timeFactory->getTimeForDay($day);
        if ($time instanceof Time) {
            $timeStart = $time->getTimeBegin() ? $time->getTimeBegin() . ':00' : $timeStart;
            $timeEnd = $time->getTimeEnd() ? $time->getTimeEnd() . ':00' : $timeEnd;
        }

        return [$timeStart, $timeEnd];
    }

    protected function getStartAndEndDateForDay(Day $day): array
    {
        [$timeStart, $timeEnd] = $this->getStartAndEndTimeForDay($day);

        if ($day->getEvent()->getEventType() === 'duration') {
            $dateStart = $this->dateTimeUtility->combineAndFormat($day->getEvent()->getEventBegin(), $timeStart);
            $dateEnd = $this->dateTimeUtility->combineAndFormat($day->getEvent()->getEventEnd(), $timeEnd);
        } else {
            $dateStart = $this->dateTimeUtility->combineAndFormat($day->getDay(), $timeStart);
            $dateEnd = $this->dateTimeUtility->combineAndFormat($day->getDay(), $timeEnd);
        }

        return [$dateStart, $dateEnd];
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
        return preg_replace('/\v+/', '\\n', $content);
    }

    /**
     * Get unique ID for given day.
     * This method is public and will be used in ICalController, too.
     */
    public function getEventUid(Day $day): string
    {
        return 'event' . uniqid($day->getDay()->format('dmY'), true);
    }
}
