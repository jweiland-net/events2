<?php
namespace JWeiland\Events2\Tca;

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
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DayGenerator {

	protected $dayStorage = array();
	protected $eventRecord = array();

	/**
	 * @var \JWeiland\Events2\Configuration\ExtConf
	 */
	protected $extConf;

	/**
	 * @var \JWeiland\Events2\Utility\DateTimeUtility
	 */
	protected $dateTimeUtility;

	/**
	 * injects extConf
	 *
	 * @param \JWeiland\Events2\Configuration\ExtConf $extConf
	 * @return void
	 */
	public function injectExtConf(\JWeiland\Events2\Configuration\ExtConf $extConf) {
		$this->extConf = $extConf;
	}

	/**
	 * inject DateTime Utility
	 *
	 * @param \JWeiland\Events2\Utility\DateTimeUtility $dateTimeUtility
	 * @return void
	 */
	public function injectDateTimeUtility(\JWeiland\Events2\Utility\DateTimeUtility $dateTimeUtility) {
		$this->dateTimeUtility = $dateTimeUtility;
	}

	/**
	 * initializes this object
	 *
	 * @param array $eventRecord
	 * @return void
	 */
	public function initialize(array $eventRecord) {
		$this->eventRecord = $eventRecord;

		// check for recurring event
		// do not add event start to recurring events. They will be generated automatically
		// further it could be that event start is not within the generated days
		if ($this->eventRecord['recurring_event']) {
			$this->addRecurringEvents();
		} elseif ($this->eventRecord['event_end']) {
			// if we have no recurrings defined but event_end, this is also a recurring event and we have to add all days in between
			$day = $this->getEventBegin();
			$eventEnd = $this->getEventEnd();
			while ($day <= $eventEnd) {
				$this->addDayToStorage($day);
				$day->modify('+1 day');
			}
		} else {
			// add start day
			$eventBegin = $this->getEventBegin();
			$this->addDayToStorage($eventBegin);
		}
		// exclude or include further days if exceptions are activated
		if (is_array($this->eventRecord['exceptions']) && count($this->eventRecord['exceptions'])) {
			$this->addExceptions();
		}
	}

	/**
	 * Getter for event record
	 *
	 * @return array
	 */
	public function getEventRecord() {
		return $this->eventRecord;
	}

	/**
	 * Setter for event record
	 *
	 * @param $eventRecord
	 * @return void
	 */
	public function setEventRecord($eventRecord) {
		$this->eventRecord = (array)$eventRecord;
	}

	/**
	 * add day to day storage
	 *
	 * @param \DateTime $day Day to add
	 * @return void
	 */
	public function addDayToStorage(\DateTime $day) {
		// group days to make them unique
		// I don't know why, but $day is a reference, so I clone it here to have individual dates in this array
		$this->dayStorage[$day->format('U')] = clone $day;
	}

	/**
	 * remove day to day storage
	 *
	 * @param \DateTime $day Day to remove
	 * @return void
	 */
	public function removeDayFromStorage(\DateTime $day) {
		unset($this->dayStorage[$day->format('U')]);
	}

	/**
	 * getter for day storage
	 *
	 * @return array
	 */
	public function getDayStorage() {
		ksort($this->dayStorage);
		return $this->dayStorage;
	}

	/**
	 * setter for day storage
	 *
	 * @param array $dayStorage
	 * @return array
	 */
	public function setDayStorage(array $dayStorage) {
		$this->dayStorage = $dayStorage;
	}

	/**
	 * getter for start date of event
	 *
	 * @return \DateTime
	 */
	public function getEventBegin() {
		return $this->dateTimeUtility->convert($this->eventRecord['event_begin']);
	}

	/**
	 * getter for end date of event
	 *
	 * @return \DateTime|NULL
	 */
	public function getEventEnd() {
		if (MathUtility::canBeInterpretedAsInteger($this->eventRecord['event_end'])) {
			return $this->dateTimeUtility->convert($this->eventRecord['event_end']);
		} else {
			return NULL;
		}
	}

	/**
	 * get max date for generated day records in future
	 *
	 * @return \DateTime
	 */
	public function getMaxDateForGeneratedDays() {
		$maxEventEnd = clone $this->dateTimeUtility->convert('today');
		$maxEventEnd->modify('+' . $this->extConf->getRecurringFuture() . ' months');
		$eventEnd = $this->getEventEnd();
		if ($eventEnd instanceof \DateTime && $eventEnd < $maxEventEnd) {
			return $eventEnd;
		} else {
			return $maxEventEnd;
		}
	}

	/**
	 * Getter for xth
	 *
	 * @return array $xth
	 */
	public function getXth() {
		$result = array();
		$isXthSet = FALSE;
		foreach ($this->getItemsFromTca('xth') as $key => $item) {
			$value = $this->eventRecord['xth'] & pow(2, $key);
			$result[$item[1]] = $value;
			if ($value) $isXthSet = TRUE;
		}

		// if no xth checkbox was set, each weekday is valid
		if ($isXthSet === FALSE) {
			foreach ($result as $key => $value) $result[$key] = 1;
		}
		return $result;
	}

	/**
	 * Getter for weekday
	 *
	 * @return array $weekday
	 */
	public function getWeekday() {
		$result = array();
		foreach ($this->getItemsFromTca('weekday') as $key => $item) {
			$result[$item[1]] = $this->eventRecord['weekday'] & pow(2, $key);
		}
		return $result;
	}

	/**
	 * get items from TCA
	 *
	 * @param string $field
	 * @return array
	 */
	protected function getItemsFromTca($field) {
		if (isset($GLOBALS['TCA']['tx_events2_domain_model_event']['columns'][$field]['config']['items']) &&
			is_array($GLOBALS['TCA']['tx_events2_domain_model_event']['columns'][$field]['config']['items'])
		) {
			return $GLOBALS['TCA']['tx_events2_domain_model_event']['columns'][$field]['config']['items'];
		} else {
			return array();
		}
	}

	/**
	 * add days for recurring events
	 *
	 * @return void
	 */
	public function addRecurringEvents() {
		if ($this->eventRecord['each_weeks']) {
			// add days for each week(s)
			$this->addRecurringWeeks();
		} else {
			// add days for xth recurring event
			$startDate = $this->getEventBegin();
			$startDate->modify('-1 month');
			
			$maxDate = $this->getMaxDateForGeneratedDays();
			while ($startDate < $maxDate) {
				$startDate->modify('+1 month'); // that's why we subtract 1 month above
				$this->addDaysForMonth($startDate->format('F'), $startDate->format('Y'));
			}
		}
	}

	/**
	 * add days for recurring weeks
	 *
	 * @return void
	 */
	public function addRecurringWeeks() {
		$day = $this->getEventBegin();
		$this->addDayToStorage($day);

		$maxDate = $this->getMaxDateForGeneratedDays();
		$interval = $day->diff($maxDate); // generates an interval object
		$diffDays = (int) $interval->format('%a'); // returns the difference in days
		$daysToGenerate = ceil($diffDays / ($this->eventRecord['each_weeks'] * 7)); // diff in days / weeks in days ==> rounded up to next integer
		for ($week = 0; $week < $daysToGenerate; $week++) {
			$day->modify('+' . $this->eventRecord['each_weeks'] . ' weeks');
			if ($day <= $maxDate) {
				$this->addDayToStorage($day);
			}
		}
	}

	/**
	 * add days for given month
	 *
	 * @param string $month
	 * @param integer $year
	 * @return void
	 */
	public function addDaysForMonth($month, $year) {
		// this is only to have a date where time is set to 00:00:00
		$day = $this->dateTimeUtility->convert('today');
		$lastDayOfMonth = $this->dateTimeUtility->convert('today'); // initialize time
		$lastDayOfMonth->modify('last day of ' . $month . ' ' . $year . '23:59:59');
		$xths = $this->getXth();
		$weekdays = $this->getWeekday();

		foreach ($xths as $xthIndex => $xth) {
			foreach ($weekdays as $weekdayIndex => $weekday) {
				if ($xth && $weekday) {
					// example: 'second wednesday of March 2013'
					$modifyString = $xthIndex . ' ' . $weekdayIndex . ' of ' . $month . ' ' . $year;
					$day->modify($modifyString);
					if ($day >= $this->getEventBegin() && $day < $lastDayOfMonth && $day <= $this->getMaxDateForGeneratedDays()) {
						$this->addDayToStorage($day);
					}
				}
			}
		}
	}

	/**
	 * add event exceptions
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function addExceptions() {
		foreach ($this->eventRecord['exceptions'] as $exception) {
			switch ($exception['exception_type']) {
				case 'Add':
					$this->addDayToStorage($this->dateTimeUtility->convert($exception['exception_date']));
					break;
				case 'Remove':
					$this->removeDayFromStorage($this->dateTimeUtility->convert($exception['exception_date']));
					break;
				case 'Time':
					break;
				case 'Info':
					break;
				default:
					throw new \Exception('"' . $exception['exception_type'] . '" is no valid exception type', 1370003254);
			}
		}
	}

}