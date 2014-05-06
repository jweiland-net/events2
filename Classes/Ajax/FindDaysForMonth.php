<?php
namespace JWeiland\Events2\Ajax;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Stefan Froemken <sfroemken@jweiland.net>, jweiland.net
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
use JWeiland\Events2\Converter\DateTimeConverter;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FindDaysForMonth extends \JWeiland\Events2\Ajax\AbstractAjaxRequest {

	/**
	 * @var \JWeiland\Events2\Domain\Repository\DayRepository
	 * @inject
	 */
	protected $dayRepository;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
	 * @inject
	 */
	protected $uriBuilder;





	/**
	 * process ajax request
	 *
	 * @param array $arguments Arguments to process
	 * @return string
	 */
	public function processAjaxRequest(array $arguments) {
		// cast arguments
		if (empty($arguments['categories'])) {
			$categories = array();
		} else $categories = GeneralUtility::intExplode(',', (string) $arguments['categories']);
		$month = (int) $arguments['month'];
		$year = (int) $arguments['year'];

		// save a session for selected month
		$this->saveMonthAndYearInSession($month, $year);

		$dayArray = array();
		$days = $this->dayRepository->findAllDaysInMonth($month, $year, $categories);
		/** @var \JWeiland\Events2\Domain\Model\Day $day */
		foreach($days as $day) {
			$dayArray[DateTimeConverter::convert($day['day'])->format('j')][] = array(
				'uid' => $day['eventUid'],
				'title' => $day['eventTitle'],
				'uri' => $this->getUriForDay($day['uid'])
			);
		}
		return json_encode($dayArray, JSON_FORCE_OBJECT);
	}

	/**
	 * We can't create the uri within a JavaScript for-loop.
	 * This way we also have realurl functionality
	 * We need the current day for calendar and day controller
	 *
	 * @param integer $dayUid
	 * @return mixed
	 */
	public function getUriForDay($dayUid) {
		if (empty($this->settings['pidOfListPage'])) {
			$pid = NULL;
		} else $pid = $this->settings['pidOfListPage'];
		$uri = $this->uriBuilder
			->reset()
			->setTargetPageUid($pid)
			->uriFor('show', array('day' => $dayUid), 'Day', 'events2', 'events');

		return $uri;
	}

	/**
	 * save selected month and year in an user session
	 *
	 * @param $month
	 * @param $year
	 * @return void
	 */
	public function saveMonthAndYearInSession($month, $year) {
		$GLOBALS['TSFE']->fe_user->setKey(
			'ses',
			'events2MonthAndYearForCalendar',
			array(
				'month' => $month,
				'year' => $year
			)
		);
	}

}