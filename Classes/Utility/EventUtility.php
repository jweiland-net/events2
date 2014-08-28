<?php
namespace JWeiland\Events2\Utility;

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
use JWeiland\Events2\Domain\Model\Time;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * f.e. the time domain model can be connected 4 times with an event (exceptionTimes, differentTimes, MultipleTimes and eventTime)
 * So which time has priority, which time has to be merged
 * This Utility tries to help you/me to make live easier with events2
 *
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class EventUtility {

	/**
	 * @var \JWeiland\Events2\Utility\DateTimeUtility
	 */
	protected $dateTimeUtility = NULL;

	/**
	 * inject DateTime Utility
	 *
	 * @param DateTimeUtility $dateTimeUtility
	 * @return void
	 */
	public function injectDateTimeUtility(\JWeiland\Events2\Utility\DateTimeUtility $dateTimeUtility) {
		$this->dateTimeUtility = $dateTimeUtility;
	}

	/**
	 * get exceptions for given day
	 * you can limit the result by a given type
	 *
	 * @param \JWeiland\Events2\Domain\Model\Event $event
	 * @param \JWeiland\Events2\Domain\Model\Day $day
	 * @param string $type There are different exception types like Add, Remove, Time or Info. If empty add all exceptions
	 * @return \SplObjectStorage
	 */
	public function getExceptionsForDay(\JWeiland\Events2\Domain\Model\Event $event, \JWeiland\Events2\Domain\Model\Day $day, $type = '') {
		$type = GeneralUtility::trimExplode(',', strtolower($type), TRUE);
		$exceptions = new \SplObjectStorage();
		/** @var \JWeiland\Events2\Domain\Model\Exception $exception */
		foreach ($event->getExceptions() as $exception) {
			$exceptionDate = $this->dateTimeUtility->standardizeDateTimeObject($exception->getExceptionDate());
			$currentDate = $this->dateTimeUtility->standardizeDateTimeObject($day->getDay());
			// we compare objects here so no === possible
			if ($exceptionDate == $currentDate) {
				if ($type === array() || in_array(strtolower($exception->getExceptionType()), $type)) {
					$exceptions->attach($exception);
				}
			}
		}
		return $exceptions;
	}

	/**
	 * each event can have one ore more times for one day
	 * This method looks into all time related records and fetches the times with highest priority
	 *
	 * @param \JWeiland\Events2\Domain\Model\Event $event
	 * @param \JWeiland\Events2\Domain\Model\Day $day
	 * @return \SplObjectStorage
	 */
	public function getTimesForDay(\JWeiland\Events2\Domain\Model\Event $event, \JWeiland\Events2\Domain\Model\Day $day) {
		// times from exceptions have priority 1
		// The exceptions of type "Add" were already moved to event->getDays (DayGenerator), but not their time records
		// that's why we collect exceptions of type "Add" and "Time" here
		$timesFromExceptions = $this->getExceptionsForDay($event, $day, 'add, time');
		if ($timesFromExceptions->count()) {
			$times = new \SplObjectStorage();
			/** @var \JWeiland\Events2\Domain\Model\Exception $exception */
			foreach ($timesFromExceptions as $exception) {
				$time = $exception->getExceptionTime();
				if ($time instanceof Time) {
					$times->attach($exception->getExceptionTime());
				}
			}
			return $times;
		}
		// times from event->differentTimes have priority 2
		$differentTimes = $this->getDifferentTimesForDay($event, $day);
		if ($differentTimes->count()) {
			return $differentTimes;
		}
		// times from event have priority 3
		$eventTimes = $this->getTimesFromEvent($event);
		if ($eventTimes->count()) {
			return $eventTimes;
		}

		// if there are no times available return empty SplObjectStorage
		return new \SplObjectStorage();
	}

	/**
	 * you can override the times in an event for a special weekday
	 * so this method checks and returns times, if there are times defined for given day
	 *
	 * @param \JWeiland\Events2\Domain\Model\Event $event
	 * @param \JWeiland\Events2\Domain\Model\Day $day
	 * @return \SplObjectStorage
	 */
	protected function getDifferentTimesForDay(\JWeiland\Events2\Domain\Model\Event $event, \JWeiland\Events2\Domain\Model\Day $day) {
		$times = new \SplObjectStorage();
		/** @var \JWeiland\Events2\Domain\Model\Time $time */
		foreach ($event->getDifferentTimes() as $time) {
			if (strtolower($time->getWeekday()) === strtolower($day->getDay()->format('l'))) {
				$times->attach($time);
			}
		}
		return $times;
	}

	/**
	 * Each event has ONE time record, but if checkbox "same day" was checked, you can add additional times
	 * This method checks both parts, merges them to one SplObjectStorage and returns the result
	 *
	 * @param \JWeiland\Events2\Domain\Model\Event $event
	 * @return \SplObjectStorage
	 */
	protected function getTimesFromEvent(\JWeiland\Events2\Domain\Model\Event $event) {
		$times = new \SplObjectStorage();
		// add normal event time
		$eventTime = $event->getEventTime();
		if ($eventTime instanceof Time) {
			$times->attach($eventTime);
		}

		// add value of multiple times
		// but only if checkbox "same day" is set
		if ($event->getSameDay()) {
			$multipleTimes = $event->getMultipleTimes();
			/** @var \JWeiland\Events2\Domain\Model\Time $time */
			foreach ($multipleTimes as $multipleTime) {
				$times->attach($multipleTime);
			}
		}
		return $times;
	}

}