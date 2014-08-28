<?php
namespace JWeiland\Events2\ViewHelpers\Widget\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Stefan Froemken <projects@jweiland.net>, jweiland.net
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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ICalendarController extends \TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController {

	/**
	 * initializes this object
	 * and cleans-up the description
	 *
	 * @return void
	 */
	public function init() {
		$description = $this->widgetConfiguration['description'];
		$description = str_replace("\r\n", "\\n", $description);
		$description = str_replace(',', '\,', $description);
		$description = str_replace(';', '\;', $description);
		$this->widgetConfiguration['description'] = $description;		
	}
	
	/**
	 * This index action was called to export an event as ics file
	 *
	 * @return string
	 */
	public function indexAction() {
		$this->init();
	
		// global informations
		$this->view->assign('PRODID', GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
		$this->view->assign('TIMEZONE', date_default_timezone_get());

		// event informations
		// loop through multiple times if possible
		$events = array();
		if ($this->widgetConfiguration['times'] instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage) {
			$times = $this->widgetConfiguration['times'];
			/** @var \JWeiland\Events2\Domain\Model\Time $time */
			foreach ($times as $time) {
				$this->widgetConfiguration['startTime'] = $time->getTimeBegin();
				$this->widgetConfiguration['endTime'] = $time->getTimeEnd();
				$events[] = $this->createEvent();
			}
		} elseif ($this->widgetConfiguration['times'] instanceof \JWeiland\Events2\Domain\Model\Time) {
			/** @var \JWeiland\Events2\Domain\Model\Time $time */
			$time = $this->widgetConfiguration['times'];
			$this->widgetConfiguration['startTime'] = $time->getTimeBegin();
			$this->widgetConfiguration['endTime'] = $time->getTimeEnd();
			$events[] = $this->createEvent();
		} else {
			// no time given
			$this->widgetConfiguration['startTime'] = FALSE;
			$this->widgetConfiguration['endTime'] = FALSE;
		}

		$this->view->assign('events', $events);

		$filePath = 'typo3temp/tx_events2/iCal/' . $this->getEventUid() . '.ics';
		GeneralUtility::writeFileToTypo3tempDir(PATH_site . $filePath, $this->view->render());
		return '
			<a href="' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $filePath . '" target="_blank">' .
				LocalizationUtility::translate('export', 'events2') .
			'</a>
		';
	}
	
	/**
	 * create an event array
	 *
	 * @return array
	 */
	protected function createEvent() {
		$event = array();
		$event['UID'] = $this->getEventUid();
		$event['DTSTAMP'] = $this->convertToTstamp($this->widgetConfiguration['day'], $this->widgetConfiguration['startTime']);
		$event['DTSTART'] = $this->convertToTstamp($this->widgetConfiguration['day'], $this->widgetConfiguration['startTime']);
		if (!empty($this->widgetConfiguration['endTime'])) {
			$event['DTEND'] = $this->convertToTstamp($this->widgetConfiguration['day'], $this->widgetConfiguration['endTime']);
		}
		$event['LOCATION'] = $this->widgetConfiguration['location'];
		$event['SUMMARY'] = $this->widgetConfiguration['title'];
		$event['DESCRIPTION'] = $this->widgetConfiguration['description'];
		
		return $event;
	}

	/**
	 * The uid for ical is not equal to the uid of events2
	 * It's more a unique string
	 *
	 * @return string
	 */
	public function getEventUid() {
		/** @var \DateTime $day */
		$day = $this->widgetConfiguration['day'];
		return 'event' . $day->format('dmY') . $this->getFormattedTime($this->widgetConfiguration['startTime']);
	}

	/**
	 * iCal needs a special format for timestamps
	 * This method converts a DateTime and a given time string to this format
	 *
	 * @param \DateTime $date
	 * @param string $time
	 * @return string
	 */
	public function convertToTstamp(\DateTime $date, $time = '') {
		return $date->format('Ymd') . 'T' . $this->getFormattedTime($time);
	}

	/**
	 * get formatted time string
	 *
	 * @param string $time
	 * @return string
	 */
	public function getFormattedTime($time = '') {
		if (empty($time)) {
			$time = '000000';
		} else $time = str_replace(':', '', $time) . '00';
		return $time;
	}

}