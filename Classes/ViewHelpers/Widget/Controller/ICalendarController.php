<?php

namespace JWeiland\Events2\ViewHelpers\Widget\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Stefan Froemken <projects@jweiland.net>, jweiland.net
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use JWeiland\Events2\Domain\Model\Time;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ICalendarController extends AbstractWidgetController
{
    /**
     * @var \JWeiland\Events2\Utility\EventUtility
     */
    protected $eventUtility;

    /**
     * @var \JWeiland\Events2\Domain\Model\Event
     */
    protected $event = null;

    /**
     * inject Event Utility.
     *
     * @param \JWeiland\Events2\Utility\EventUtility $eventUtility
     */
    public function injectEventUtility(\JWeiland\Events2\Utility\EventUtility $eventUtility)
    {
        $this->eventUtility = $eventUtility;
    }

    /**
     * initializes this object.
     */
    public function init()
    {
        $this->event = $this->widgetConfiguration['event'];
    }

    /**
     * This index action was called to export an event as ics file.
     *
     * @return string
     */
    public function indexAction()
    {
        $this->init();

        // global informations
        $this->view->assign('PRODID', GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));

        // event informations
        $events = array();
        /** @var \SplObjectStorage $times */
        $times = $this->eventUtility->getTimesForDay($this->event, $this->event->getDay());
        if ($times->count()) {
            foreach ($times as $time) {
                $events[] = $this->createEvent($time->getTimeBegin(), $time->getTimeEnd());
            }
        } else {
            // no time given
            $this->widgetConfiguration['startTime'] = false;
            $this->widgetConfiguration['endTime'] = false;
            $events[] = $this->createEvent();
        }

        $this->view->assign('events', $events);

        $filePath = 'typo3temp/tx_events2/iCal/'.$this->getEventUid().'.ics';
        $content = preg_replace('/\h+/', ' ', $this->view->render());
        GeneralUtility::writeFileToTypo3tempDir(PATH_site.$filePath, $content);

        return '
			<a href="'.GeneralUtility::getIndpEnv('TYPO3_SITE_URL').$filePath.'" target="_blank">'.
                LocalizationUtility::translate('export', 'events2').
            '</a>
		';
    }

    /**
     * create an event array
     * Hint: We can't use DTSTAMP here, because this must be UTC, but we don't have UTC times here.
     *
     * @param string $startTime
     * @param string $endTime
     *
     * @return array
     */
    protected function createEvent($startTime = '', $endTime = '')
    {
        $event = array();
        $event['UID'] = $this->getEventUid();
        $event['DTSTART'] = $this->convertToTstamp($this->event->getDay()->getDay(), $startTime);
        if (!empty($endTime)) {
            $event['DTEND'] = $this->convertToTstamp($this->event->getDay()->getDay(), $endTime);
        }
        $event['LOCATION'] = $this->sanitizeString($this->event->getLocation()->getLocation());
        $event['SUMMARY'] = $this->sanitizeString($this->event->getTitle());
        $event['DESCRIPTION'] = $this->sanitizeString($this->event->getDetailInformations());

        return $event;
    }

    /**
     * The uid for ical is not equal to the uid of events2
     * It's more a unique string.
     *
     * @return string
     */
    public function getEventUid()
    {
        return 'event'.uniqid($this->event->getDay()->getDay()->format('dmY'), true);
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
            $diff = new \DateInterval('PT'.(int) $hours.'H'.(int) $minutes.'M');
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
            $time = str_replace(':', '', $time).'00';
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
