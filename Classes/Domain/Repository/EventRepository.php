<?php
namespace JWeiland\Events2\Domain\Repository;

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
class EventRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
	 * @inject
	 */
	protected $dataMapper;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var array
	 */
	protected $defaultOrderings = array(
		'eventBegin' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING
	);

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 * @inject
	 */
	protected $configurationManager;





	/**
	 * find all events which can be released
	 * -> facebook must be checked
	 * -> releaseDate can not be empty
	 */
	public function findUnreleasedEvents() {
		$query = $this->createQuery();
		$constraint = array();
		$constraint[] = $query->equals('facebook', 1);
		$constraint[] = $query->equals('releaseDate', 0);
		return $query->matching($query->logicalAnd($constraint))->execute();
	}

	/**
	 * find top events
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	public function findTopEvents() {
		$today = DateTimeConverter::convert('today');

		// create query and get results
		$query = $this->createQuery();
		$records = $query->statement('
			SELECT tx_events2_domain_model_event.*, tableDay.uid as dayUid, tableDay.pid as dayPid, tableDay.day as dayDay, tableDay.events as dayEvents
			FROM tx_events2_domain_model_event
			LEFT JOIN tx_events2_event_day_mm ON tx_events2_domain_model_event.uid=tx_events2_event_day_mm.uid_local
			LEFT JOIN (
				SELECT *
				FROM tx_events2_domain_model_day
  			WHERE day >= ?
  			ORDER BY day ASC
			) tableDay
			ON tableDay.uid = tx_events2_event_day_mm.uid_foreign
			WHERE FIND_IN_SET(tx_events2_domain_model_event.pid, ?)
			AND tx_events2_domain_model_event.top_of_list = ?
			AND tableDay.uid IS NOT NULL' .
			\t3lib_BEfunc::BEenableFields('tx_events2_domain_model_event') .
			\t3lib_BEfunc::deleteClause('tx_events2_domain_model_event') . '
			GROUP BY tx_events2_domain_model_event.uid',
			array(
				$today->format('U'),
				implode(',', $query->getQuerySettings()->getStoragePageIds()),
				1
			)
		)->execute(TRUE);

		// As long as domain models will be cached by their UID, we have to create our own event storage
		/** @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage $eventStorage */
		$eventStorage = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');
		/** @var \JWeiland\Events2\Domain\Model\Event $event */
		foreach ($records as $record) {
			list($event) = $this->dataMapper->map('JWeiland\\Events2\\Domain\\Model\\Event', array($record));
			$event->setDay($this->getDayFromEvent($record));
			$eventStorage->attach(clone $event);
		}
		return $eventStorage;
	}

	/**
	 * find next events
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findNextEvents() {
		$today = DateTimeConverter::convert('today');

		// create query and return results
		$query = $this->createQuery();
		return $query->statement('
			SELECT ###SELECT###
			FROM tx_events2_domain_model_event
			LEFT JOIN tx_events2_event_day_mm ON tx_events2_domain_model_event.uid=tx_events2_event_day_mm.uid_local
			LEFT JOIN tx_events2_domain_model_day ON tx_events2_event_day_mm.uid_foreign=tx_events2_domain_model_day.uid
			WHERE FIND_IN_SET(tx_events2_domain_model_event.pid, ?)
			AND tx_events2_domain_model_day.day >= ?' .
			\t3lib_BEfunc::BEenableFields('tx_events2_domain_model_event') .
			\t3lib_BEfunc::deleteClause('tx_events2_domain_model_event') .
			\t3lib_BEfunc::BEenableFields('tx_events2_domain_model_day') .
			\t3lib_BEfunc::deleteClause('tx_events2_domain_model_day') . '
			###LIMIT###',
			array(
				implode(',', $query->getQuerySettings()->getStoragePageIds()),
				$today->format('U')
			)
		)->execute();
	}

	/**
	 * find all events with given categories
	 *
	 * @param string $categories comma seperated list of categories
	 * @param integer $mergeOnCategory a category UID
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findNextEventsByCategories($categories, $mergeOnCategory) {
		$categoryOrQuery = array();

		// create OR-Query for categories
		foreach (GeneralUtility::trimExplode(',', $categories) as $category) {
			$categoryOrQuery[] = 'sys_category_record_mm.uid_local IN (\'' . (int) $category . '\')';
		}

		$today = DateTimeConverter::convert('today');

		// create query and return results
		$query = $this->createQuery();
		return $query->statement('
			SELECT ###SELECT###
			FROM tx_events2_domain_model_event
			LEFT JOIN tx_events2_event_day_mm ON tx_events2_domain_model_event.uid=tx_events2_event_day_mm.uid_local
			LEFT JOIN tx_events2_domain_model_day ON tx_events2_event_day_mm.uid_foreign=tx_events2_domain_model_day.uid
			LEFT JOIN sys_category_record_mm ON tx_events2_domain_model_event.uid=sys_category_record_mm.uid_foreign
			WHERE (' . implode(' OR ', $categoryOrQuery) . ')
			AND sys_category_record_mm.tablenames = ?
			AND FIND_IN_SET(tx_events2_domain_model_event.pid, ?)
			AND tx_events2_domain_model_day.day > ?' .
			\t3lib_BEfunc::BEenableFields('tx_events2_domain_model_event') .
			\t3lib_BEfunc::deleteClause('tx_events2_domain_model_event') .
			\t3lib_BEfunc::BEenableFields('tx_events2_domain_model_day') .
			\t3lib_BEfunc::deleteClause('tx_events2_domain_model_day') . '
			###LIMIT###',
			array(
				'tx_events2_domain_model_event',
				implode(',', $query->getQuerySettings()->getStoragePageIds()),
				$today->format('U')
			)
		)->execute();
	}

	/**
	 * find all events from today with given categories
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findTodayEvents() {
		$today = DateTimeConverter::convert('today');
		$tomorrow = clone $today;
		$tomorrow->modify('+1 day');

		// create query and return results
		$query = $this->createQuery();
		return $query->statement('
			SELECT ###SELECT###
			FROM tx_events2_domain_model_event
			LEFT JOIN tx_events2_event_day_mm ON tx_events2_domain_model_event.uid=tx_events2_event_day_mm.uid_local
			LEFT JOIN tx_events2_domain_model_day ON tx_events2_event_day_mm.uid_foreign=tx_events2_domain_model_day.uid
			WHERE FIND_IN_SET(tx_events2_domain_model_event.pid, ?)
			AND tx_events2_domain_model_day.day >= ?
			AND tx_events2_domain_model_day.day < ?' .
			\t3lib_BEfunc::BEenableFields('tx_events2_domain_model_event') .
			\t3lib_BEfunc::deleteClause('tx_events2_domain_model_event') .
			\t3lib_BEfunc::BEenableFields('tx_events2_domain_model_day') .
			\t3lib_BEfunc::deleteClause('tx_events2_domain_model_day') . '
			###LIMIT###',
			array(
				implode(',', $query->getQuerySettings()->getStoragePageIds()),
				$today->format('U'),
				$tomorrow->format('U')
			)
		)->execute();
	}

	/**
	 * find all events from today with given categories
	 *
	 * @param string $categories comma separated list of categories
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findTodayEventsByCategories($categories) {
		// create OR-Query for categories
		foreach (GeneralUtility::trimExplode(',', $categories) as $category) {
			$categoryOrQuery[] = 'sys_category_record_mm.uid_local IN (\'' . (int) $category . '\')';
		}

		$today = DateTimeConverter::convert('today');
		$tomorrow = clone $today;
		$tomorrow->modify('+1 day');

		// create query and return results
		$query = $this->createQuery();
		return $query->statement('
			SELECT ###SELECT###
			FROM tx_events2_domain_model_event
			LEFT JOIN tx_events2_event_day_mm ON tx_events2_domain_model_event.uid=tx_events2_event_day_mm.uid_local
			LEFT JOIN tx_events2_domain_model_day ON tx_events2_event_day_mm.uid_foreign=tx_events2_domain_model_day.uid
			LEFT JOIN sys_category_record_mm ON tx_events2_domain_model_event.uid=sys_category_record_mm.uid_foreign
			AND sys_category_record_mm.tablenames = ?
			WHERE (' . implode(' OR ', $categoryOrQuery) . ')
			AND FIND_IN_SET(tx_events2_domain_model_event.pid, ?)
			AND tx_events2_domain_model_day.day >= ?
			AND tx_events2_domain_model_day.day < ?' .
			\t3lib_BEfunc::BEenableFields('tx_events2_domain_model_event') .
			\t3lib_BEfunc::deleteClause('tx_events2_domain_model_event') .
			\t3lib_BEfunc::BEenableFields('tx_events2_domain_model_day') .
			\t3lib_BEfunc::deleteClause('tx_events2_domain_model_day') . '
			###LIMIT###',
			array(
				'tx_events2_domain_model_event',
				implode(',', $query->getQuerySettings()->getStoragePageIds()),
				$today->format('U'),
				$tomorrow->format('U')
			)
		)->execute();
	}

	/**
	 * find events in range
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findRangeEvents() {
		$today = DateTimeConverter::convert('today');
		$in4months = clone $today;
		$in4months->modify('+4 weeks');

		// create query and get results
		$query = $this->createQuery();
		return $query->statement('
			SELECT ###SELECT###
			FROM tx_events2_domain_model_event
			LEFT JOIN tx_events2_event_day_mm ON tx_events2_domain_model_event.uid=tx_events2_event_day_mm.uid_local
			LEFT JOIN tx_events2_domain_model_day ON tx_events2_event_day_mm.uid_foreign=tx_events2_domain_model_day.uid
			WHERE FIND_IN_SET(tx_events2_domain_model_event.pid, ?)
			AND tx_events2_domain_model_day.day >= ?
			AND tx_events2_domain_model_day.day <= ?' .
			\t3lib_BEfunc::BEenableFields('tx_events2_domain_model_event') .
			\t3lib_BEfunc::deleteClause('tx_events2_domain_model_event') .
			\t3lib_BEfunc::BEenableFields('tx_events2_domain_model_day') .
			\t3lib_BEfunc::deleteClause('tx_events2_domain_model_day') . '
			###LIMIT###',
			array(
				implode(',', $query->getQuerySettings()->getStoragePageIds()),
				$today->format('U'),
				$in4months->format('U')
			)
		)->execute();
	}

	/**
	 * find culture events
	 *
	 * @param integer $ageGroup
	 * @param integer $suitabilityUser
	 * @param integer $suitabilityGroups
	 * @param integer $topic
	 * @param integer $category
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findCultureEvents($ageGroup, $suitabilityUser = 0, $suitabilityGroups = 0, $topic = 0, $category = 0) {
		$query = $this->createQuery();
		$today = DateTimeConverter::convert('today');
		$categories = array();
		$where = array('1=1');
		$placeHolder = array(
			implode(',', $query->getQuerySettings()->getStoragePageIds()),
			'tx_events2_domain_model_event',
			$today->format('U'),
			1
		);

		// add ageGroup. This is a must have
		$categories[] = $ageGroup;

		// add topic
		if ($topic) {
			$categories[] = $topic;
		}

		// add category
		if ($topic) {
			$categories[] = $category;
		}

		$placeHolder[] = implode(', ', $categories);

		// add suitable user
		if ($suitabilityUser) {
			$where[] = 'tx_events2_domain_model_event.suitability_user = ?';
			$placeHolder[] = 1;
		}

		// add suitable group
		if ($suitabilityGroups) {
			$where[] = 'tx_events2_domain_model_event.suitability_groups = ?';
			$placeHolder[] = 1;
		}

		// create query and return results
		return $query->statement('
			SELECT ###SELECT###
			FROM tx_events2_domain_model_event
			LEFT JOIN tx_events2_event_day_mm ON tx_events2_domain_model_event.uid=tx_events2_event_day_mm.uid_local
			LEFT JOIN tx_events2_domain_model_day ON tx_events2_event_day_mm.uid_foreign=tx_events2_domain_model_day.uid
			LEFT JOIN sys_category_record_mm ON tx_events2_domain_model_event.uid=sys_category_record_mm.uid_foreign
			WHERE FIND_IN_SET(tx_events2_domain_model_event.pid, ?)
			AND sys_category_record_mm.tablenames = ?
			AND tx_events2_domain_model_day.day >= ?
			AND tx_events2_domain_model_event.suitability_culture = ?
			AND FIND_IN_SET(sys_category_record_mm.uid_local, ?)
			AND ' . implode(' AND ', $where) .
			\t3lib_BEfunc::BEenableFields('tx_events2_domain_model_event') .
			\t3lib_BEfunc::deleteClause('tx_events2_domain_model_event') .
			\t3lib_BEfunc::BEenableFields('tx_events2_domain_model_day') .
			\t3lib_BEfunc::deleteClause('tx_events2_domain_model_day') . '
			###LIMIT###',
			$placeHolder
		)->execute();
	}

	/**
	 * search for events
	 *
	 * @param \JWeiland\Events2\Domain\Model\Search $search
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function searchEvents(\JWeiland\Events2\Domain\Model\Search $search) {
		// create query and get results
		$query = $this->createQuery();
		$addJoin = array();
		$addJoin[] = 'LEFT JOIN tx_events2_event_day_mm ON tx_events2_domain_model_event.uid=tx_events2_event_day_mm.uid_local';
		$addJoin[] = 'LEFT JOIN tx_events2_domain_model_day ON tx_events2_event_day_mm.uid_foreign=tx_events2_domain_model_day.uid';
		$addWhere = array();
		$addWhere[] = 'FIND_IN_SET(tx_events2_domain_model_event.pid, ?)';
		$placeHolders = array();
		$placeHolders[] = implode(',', $query->getQuerySettings()->getStoragePageIds());

		// add query for search string
		if ($search->getSearch()) {
			$addWhere[] = '(tx_events2_domain_model_event.title LIKE ? OR tx_events2_domain_model_event.teaser LIKE ?)';
			$placeHolders[] = '%' . $search->getSearch() . '%';
			$placeHolders[] = '%' . $search->getSearch() . '%';
		}

		// add query for categories
		if ($search->getMainCategory()) {
			$addJoin[] = 'LEFT JOIN sys_category_record_mm ON tx_events2_domain_model_event.uid=sys_category_record_mm.uid_foreign';
			$addWhere[] = 'sys_category_record_mm.uid_local = ?';
			if ($search->getSubCategory()) {
				$placeHolders[] = $search->getSubCategory()->getUid();
			} else $placeHolders[] = $search->getMainCategory()->getUid();
		}

		// add query for event begin
		if ($search->getEventBegin()) {
			$addWhere[] = 'tx_events2_domain_model_day.day >= ?';
			$placeHolders[] = $search->getEventBegin()->format('U');
		} else {
			$addWhere[] = 'tx_events2_domain_model_day.day >= ?';
			$today = DateTimeConverter::convert('today');
			$placeHolders[] = $today->format('U');
		}

		// add query for event end
		if ($search->getEventEnd()) {
			$addWhere[] = 'tx_events2_domain_model_day.day <= ?';
			$placeHolders[] = $search->getEventEnd()->format('U');
		}

		// add query for event location
		if ($search->getLocation()) {
			$addJoin[] = 'LEFT JOIN tx_events2_event_location_mm ON tx_events2_event_location_mm.event=tx_events2_domain_model_event.uid';
			$addWhere[] = 'tx_events2_event_location_mm.location = ?';
			$placeHolders[] = $search->getLocation()->getUid();
		}

		// add query for free entry
		if ($search->getFreeEntry()) {
			$addWhere[] = 'tx_events2_domain_model_event.free_entry = ?';
			$placeHolders[] = $search->getFreeEntry();
		}

		$statement = '
			SELECT ###SELECT###
			FROM tx_events2_domain_model_event
			' . implode(' ', $addJoin) . '
			WHERE ' . implode(' AND ', $addWhere) .
			\t3lib_BEfunc::BEenableFields('tx_events2_domain_model_event') .
			\t3lib_BEfunc::deleteClause('tx_events2_domain_model_event') .
			\t3lib_BEfunc::BEenableFields('tx_events2_domain_model_day') .
			\t3lib_BEfunc::deleteClause('tx_events2_domain_model_day') . '
			###LIMIT###
		';

		return $query->statement($statement, $placeHolders)->execute();
	}

	/**
	 * extract day from event record
	 * With this method we save one additional SQL-Query
	 *
	 * @param array $event
	 * @return \JWeiland\Events2\Domain\Model\Day
	 */
	public function getDayFromEvent(array $event) {
		$dayRecord = array(
			'uid' => $event['dayUid'],
			'pid' => $event['dayPid'],
			'day' => $event['dayDay'],
			'events' => $event['dayEvents']
		);
		list($day) = $this->dataMapper->map('\JWeiland\Events2\Domain\Model\Day', array($dayRecord));
		return $day;
	}

	/**
	 * create query for category statement
	 *
	 * @param string $type special type of query like "range", "latest" or "today"
	 * @param string $categories comma seperated list of categories
	 * @param boolean $mergeEvents
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function getEventsForCategories($type, $categories, $mergeEvents) {
		// create query
		$query = $this->createQuery();

		// create OR-Query for categories
		foreach (GeneralUtility::intExplode(',', $categories) as $category) {
			$orQueryForCategories[] = 'sys_category_record_mm.uid_local IN (\'' . (int) $category . '\')';
		}
		$additionalOrClauseForCategories = implode(' OR ', $orQueryForCategories);

		// prepare variables for statement
		switch ($type) {
			case 'today':
				$today = DateTimeConverter::convert('today');
				$tomorrow = clone $today;
				$tomorrow->modify('+1 day');
				$additionalWhereClause = ' AND tx_events2_domain_model_day.day >= ? AND tx_events2_domain_model_day.day < ? ';
				$replacements = array(
					'tx_events2_domain_model_event',
					implode(',', $query->getQuerySettings()->getStoragePageIds()),
					$today->format('U'),
					$tomorrow->format('U')
				);
				break;
			case 'range':
				$today = DateTimeConverter::convert('today');
				$in4months = clone $today;
				$in4months->modify('+4 weeks');
				$additionalWhereClause = ' AND tx_events2_domain_model_day.day >= ? AND tx_events2_domain_model_day.day <= ?';
				$replacements = array(
					'tx_events2_domain_model_event',
					implode(',', $query->getQuerySettings()->getStoragePageIds()),
					$today->format('U'),
					$in4months->format('U')
				);
				break;
			case 'list':
			case 'latest':
			default:
				$today = DateTimeConverter::convert('today');
				$additionalWhereClauseForMergedRecords = ' AND tx_events2_domain_model_day.day = (
					SELECT day
					FROM tx_events2_domain_model_day LEFT JOIN tx_events2_event_day_mm
					ON tx_events2_domain_model_day.uid=tx_events2_event_day_mm.uid_foreign
					WHERE tx_events2_event_day_mm.uid_local = tx_events2_domain_model_event.uid
					AND day >= ?
					ORDER BY tx_events2_domain_model_day.day ASC
					LIMIT 1
				) ';
				$additionalWhereClause = ' AND tx_events2_domain_model_day.day >= ? ';
				$replacements = array(
					'tx_events2_domain_model_event',
					implode(',', $query->getQuerySettings()->getStoragePageIds()),
					$today->format('U')
				);
				break;
		}

		if ($mergeEvents) {
			$replacements = array_merge($replacements, $replacements);
			return $query->statement(
				$this->getStatementForMergedCategoryQuery($additionalOrClauseForCategories, $additionalWhereClause, $additionalWhereClauseForMergedRecords),
				$replacements
			)->execute();
		} else {
			return $query->statement(
				$this->getStatementForCategoryQuery($additionalOrClauseForCategories, $additionalWhereClause),
				$replacements
			)->execute();
		}
	}

	/**
	 * create a statement for category query where events are NOT merged
	 *
	 * @param string $additionalOrClauseForCategories
	 * @param string $additionalWhereClause
	 * @return string
	 */
	protected function getStatementForCategoryQuery($additionalOrClauseForCategories, $additionalWhereClause) {
		return 'SELECT ###SELECT###
			FROM tx_events2_domain_model_event
			LEFT JOIN tx_events2_event_day_mm ON tx_events2_domain_model_event.uid=tx_events2_event_day_mm.uid_local
			LEFT JOIN tx_events2_domain_model_day ON tx_events2_event_day_mm.uid_foreign=tx_events2_domain_model_day.uid
			LEFT JOIN sys_category_record_mm ON tx_events2_domain_model_event.uid=sys_category_record_mm.uid_foreign
			WHERE (' . $additionalOrClauseForCategories . ')
			AND sys_category_record_mm.tablenames = ?
			AND FIND_IN_SET(tx_events2_domain_model_event.pid, ?) ' .
			$additionalWhereClause . $this->getWhereClauseForEnableFields() . '
			###LIMIT###';
	}

	/**
	 * create a statement for category query where events are NOT merged
	 *
	 * @param string $additionalOrClauseForCategories
	 * @param string $additionalWhereClause
	 * @param string $additionalWhereClauseForMergedRecords
	 * @return string
	 */
	protected function getStatementForMergedCategoryQuery($additionalOrClauseForCategories, $additionalWhereClause, $additionalWhereClauseForMergedRecords = '') {
		return '(
				SELECT ###SELECT###
				FROM tx_events2_domain_model_event
				LEFT JOIN tx_events2_event_day_mm ON tx_events2_domain_model_event.uid=tx_events2_event_day_mm.uid_local
				LEFT JOIN tx_events2_domain_model_day ON tx_events2_event_day_mm.uid_foreign=tx_events2_domain_model_day.uid
				LEFT JOIN sys_category_record_mm ON tx_events2_domain_model_event.uid=sys_category_record_mm.uid_foreign
				WHERE (' . $additionalOrClauseForCategories . ')
				AND sys_category_record_mm.tablenames = ?
				AND FIND_IN_SET(tx_events2_domain_model_event.pid, ?)
				' . $additionalWhereClauseForMergedRecords . $this->getWhereClauseForEnableFields() . '
				AND tx_events2_domain_model_event.event_end <> 0
			) UNION	(
				SELECT ###SELECT###
				FROM tx_events2_domain_model_event
				LEFT JOIN tx_events2_event_day_mm ON tx_events2_domain_model_event.uid=tx_events2_event_day_mm.uid_local
				LEFT JOIN tx_events2_domain_model_day ON tx_events2_event_day_mm.uid_foreign=tx_events2_domain_model_day.uid
				LEFT JOIN sys_category_record_mm ON tx_events2_domain_model_event.uid=sys_category_record_mm.uid_foreign
				WHERE (' . $additionalOrClauseForCategories . ')
				AND sys_category_record_mm.tablenames = ?
				AND FIND_IN_SET(tx_events2_domain_model_event.pid, ?)
				' . $additionalWhereClause . $this->getWhereClauseForEnableFields() . '
				AND tx_events2_domain_model_event.event_end = 0
			)
			###LIMIT###
		';
	}

	/**
	 * get where clause for enable fields in events and day tables
	 *
	 * @return string
	 */
	protected function getWhereClauseForEnableFields() {
		return ' ' .
			\t3lib_BEfunc::BEenableFields('tx_events2_domain_model_event') .
			\t3lib_BEfunc::deleteClause('tx_events2_domain_model_event') .
			\t3lib_BEfunc::BEenableFields('tx_events2_domain_model_day') .
			\t3lib_BEfunc::deleteClause('tx_events2_domain_model_day') . ' ';
	}

}