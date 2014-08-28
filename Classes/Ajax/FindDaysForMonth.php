<?php
namespace JWeiland\Events2\Ajax;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\PreparedStatement;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FindDaysForMonth {

	/**
	 * arguments from GET
	 *
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * database
	 *
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseConnection = NULL;

	/**
	 * @var \JWeiland\Events2\Utility\DateTimeUtility
	 */
	protected $dateTimeUtility = NULL;

	/**
	 * @var \TYPO3\CMS\Frontend\Page\CacheHashCalculator
	 */
	protected $cacheHashCalculator;

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
	 * inject CacheHash Calculator
	 *
	 * @param \TYPO3\CMS\Frontend\Page\CacheHashCalculator $cacheHashCalculator
	 * @return void
	 */
	public function injectCacheHashCalculator(\TYPO3\CMS\Frontend\Page\CacheHashCalculator $cacheHashCalculator) {
		$this->cacheHashCalculator = $cacheHashCalculator;
	}

	/**
	 * process ajax request
	 *
	 * @param array $arguments Arguments to process
	 * @return string
	 */
	public function processAjaxRequest(array $arguments) {
		$this->initialize($arguments);
		$month = $this->getArgument('month');
		$year = $this->getArgument('year');

		// save a session for selected month
		$this->saveMonthAndYearInSession($month, $year);

		$dayArray = array();
		$days = $this->findAllDaysInMonth($month, $year);
		/** @var \JWeiland\Events2\Domain\Model\Day $day */
		foreach($days as $day) {
			$timestamp = $this->dateTimeUtility->convert($day['day'])->format('j');
			$uri = $this->getUriForDay($day['uid']);
			$dayArray[$timestamp][] = array(
				'uid' => $day['eventUid'],
				'title' => $day['eventTitle'],
				'uri' => $uri
			);
		}
		return json_encode($dayArray, JSON_FORCE_OBJECT);
	}

	/**
	 * initializes this class
	 *
	 * @param array $arguments
	 */
	protected function initialize(array $arguments) {
		$this->databaseConnection = $GLOBALS['TYPO3_DB'];
		$this->setArguments($arguments);
	}

	/**
	 * set and check GET Arguments
	 *
	 * @param array $arguments
	 */
	protected function setArguments(array $arguments) {
		$arguments['categories'] = implode(',', GeneralUtility::intExplode(',', (string)$arguments['categories']));
		$arguments['month'] = (int)$arguments['month'];
		$arguments['year'] = (int)$arguments['year'];
		$arguments['pidOfListPage'] = (int)$arguments['pidOfListPage'];
		$arguments['storagePids'] = implode(',', GeneralUtility::intExplode(',', (string)$arguments['storagePids']));

		$this->arguments = $arguments;
	}

	/**
	 * get an argument from GET
	 *
	 * @param string $argumentName
	 * @return string
	 */
	protected function getArgument($argumentName) {
		if (isset($this->arguments[$argumentName])) {
			return $this->arguments[$argumentName];
		} else {
			return '';
		}
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
		// uriBuilder is very slow: 223ms for 31 links */
		/*$uri = $this->uriBuilder
			->reset()
			->setTargetPageUid($pid)
			->uriFor('show', array('day' => $dayUid), 'Day', 'events2', 'events');*/

		// create uri manually instead of uriBuilder
		$siteUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'index.php?';
		$query = array(
			'id' => $this->getArgument('pidOfListPage'),
			'tx_events2_events' => array(
				'controller' => 'Day',
				'action' => 'show',
				'day' => (int)$dayUid
			)
		);
		$cacheHashArray = $this->cacheHashCalculator->getRelevantParameters(GeneralUtility::implodeArrayForUrl('', $query));
		$query['cHash'] = $this->cacheHashCalculator->calculateCacheHash($cacheHashArray);
		$uri = $siteUrl . http_build_query($query);

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
		/** @var \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication $userAuthentication */
		$userAuthentication = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Authentication\\FrontendUserAuthentication');
		$userAuthentication->start();
		$userAuthentication->setAndSaveSessionData(
			'events2MonthAndYearForCalendar',
			array(
				'month' => $month,
				'year' => $year
			)
		);
	}

	/**
	 * find all days in given month
	 *
	 * @param integer $month
	 * @param integer $year
	 * @return array
	 */
	public function findAllDaysInMonth($month, $year) {
		// get start and ending of given month
		// get start and ending of given month
		$monthBegin = $this->dateTimeUtility->standardizeDateTimeObject(\DateTime::createFromFormat('d.m.Y', '1.' . $month . '.' . $year));
		$monthEnd = clone $monthBegin;
		$monthEnd->modify('last day of this month')->modify('tomorrow');

		$categories = $this->getArgument('categories');

		if (!empty($categories)) {
			$statement = $this->databaseConnection->prepare_SELECTquery(
				'tx_events2_domain_model_day.uid, tx_events2_domain_model_day.day, tx_events2_domain_model_event.uid as eventUid, tx_events2_domain_model_event.title as eventTitle',
				'tx_events2_domain_model_day
				LEFT JOIN tx_events2_event_day_mm ON tx_events2_domain_model_day.uid=tx_events2_event_day_mm.uid_foreign
				LEFT JOIN tx_events2_domain_model_event ON tx_events2_domain_model_event.uid=tx_events2_event_day_mm.uid_local
				LEFT JOIN sys_category_record_mm ON tx_events2_domain_model_event.uid=sys_category_record_mm.uid_foreign',
				'sys_category_record_mm.tablenames = :tablename
				AND tx_events2_domain_model_day.day >= :monthBegin
				AND tx_events2_domain_model_day.day < :monthEnd
				AND FIND_IN_SET(tx_events2_domain_model_event.pid, :storagePids)
				AND sys_category_record_mm.uid_local IN (' . $categories . ')' .
				BackendUtility::BEenableFields('tx_events2_domain_model_day') .
				BackendUtility::deleteClause('tx_events2_domain_model_day') .
				BackendUtility::BEenableFields('tx_events2_domain_model_event') .
				BackendUtility::deleteClause('tx_events2_domain_model_event')
			);
			$statement->execute(array(
				':tablename' => 'tx_events2_domain_model_event',
				':monthBegin' => $monthBegin->format('U'),
				':monthEnd' => $monthEnd->format('U'),
				':storagePids' => $this->getArgument('storagePids')
			));
		} else {
			$statement = $this->databaseConnection->prepare_SELECTquery(
				'tx_events2_domain_model_day.uid, tx_events2_domain_model_day.day, tx_events2_domain_model_event.uid as eventUid, tx_events2_domain_model_event.title as eventTitle',
				'tx_events2_domain_model_day
				LEFT JOIN tx_events2_event_day_mm ON tx_events2_domain_model_day.uid=tx_events2_event_day_mm.uid_foreign
				LEFT JOIN tx_events2_domain_model_event ON tx_events2_domain_model_event.uid=tx_events2_event_day_mm.uid_local',
				'tx_events2_domain_model_day.day >= :monthBegin
				AND tx_events2_domain_model_day.day < :monthEnd
				AND FIND_IN_SET(tx_events2_domain_model_event.pid, :storagePids)' .
				BackendUtility::BEenableFields('tx_events2_domain_model_day') .
				BackendUtility::deleteClause('tx_events2_domain_model_day') .
				BackendUtility::BEenableFields('tx_events2_domain_model_event') .
				BackendUtility::deleteClause('tx_events2_domain_model_event')
			);
			$statement->execute(array(
				':monthBegin' => $monthBegin->format('U'),
				':monthEnd' => $monthEnd->format('U'),
				':storagePids' => $this->getArgument('storagePids')
			));
		}
		$rows = $statement->fetchAll(PreparedStatement::FETCH_ASSOC);
		$statement->free();
		return $rows;
	}
}

/** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

$request = GeneralUtility::_GPmerged('tx_events2_events');

/** @var \JWeiland\Events2\Ajax\FindDaysForMonth $ajaxObject */
$ajaxObject = $objectManager->get('JWeiland\\Events2\\Ajax\\FindDaysForMonth');
echo $ajaxObject->processAjaxRequest($request['arguments']);
