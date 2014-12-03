<?php
namespace JWeiland\Events2\Domain\Repository;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class EventRepository extends Repository {

	/**
	 * @var array
	 */
	protected $defaultOrderings = array(
		'eventBegin' => QueryInterface::ORDER_ASCENDING
	);

	/**
	 * @var \JWeiland\Events2\Utility\DateTimeUtility
	 */
	protected $dateTimeUtility;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
	 */
	protected $dataMapper;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseConnection;

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
	 * inject DataMapper
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper
	 * @return void
	 */
	public function injectDataMapper(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
	}

	/**
	 * inject Configuration Manager
	 *
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * set TYPO3_DB
	 * Needed for quoteStr only
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->databaseConnection = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * find all events which can be released
	 * -> facebook must be checked
	 * -> releaseDate can not be empty
	 * 
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
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
	 * @param bool $mergeEvents
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	public function findTopEvents($mergeEvents = FALSE) {
		/** @var \TYPO3\CMS\Extbase\Persistence\Generic\Query $query */
		$query = $this->createQuery();
		$today = $this->dateTimeUtility->convert('today');
		$statement = $this->createStatement()
			->setQuery($query)
			->setSelect('tx_events2_domain_model_event.*, tx_events2_domain_model_day.uid as dayUid, tx_events2_domain_model_day.pid as dayPid, tx_events2_domain_model_day.day as dayDay, tx_events2_domain_model_day.events as dayEvents');

		if ($mergeEvents) {
			$statement->setMergeEvents(TRUE);
		}

		$statement
			->addWhere('tx_events2_domain_model_event.top_of_list', '=', 1)
			->addWhere('tx_events2_domain_model_day.day', '>=', $today)
			->setGroupBy('tx_events2_domain_model_event.uid');

		$records = $query->statement($statement->getStatement())->execute(TRUE);

		// As long as domain models will be cached by their UID, we have to create our own event storage
		/** @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage $eventStorage */
		$eventStorage = new ObjectStorage();
		/** @var \JWeiland\Events2\Domain\Model\Event $event */
		foreach ($records as $record) {
			list($event) = $this->dataMapper->map('JWeiland\\Events2\\Domain\\Model\\Event', array($record));
			$event->setDay($this->getDayFromEvent($record));
			$eventStorage->attach(clone $event);
		}
		return $eventStorage;
	}

	/**
	 * find events
	 *
	 * @param string $type
	 * @param string $categories
	 * @param bool $mergeEvents
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findEvents($type, $categories = '', $mergeEvents = FALSE) {
		/** @var \TYPO3\CMS\Extbase\Persistence\Generic\Query $query */
		$query = $this->createQuery();
		$statement = $this->createStatement()->setQuery($query);

		if (!empty($categories)) {
			$statement->setCategoryRelation(TRUE)->addWhereForCategories($categories);
		}
		if ($mergeEvents) {
			$statement->setMergeEvents(TRUE);
		}

		switch ($type) {
			case 'today':
				$today = $this->dateTimeUtility->convert('today');
				$tomorrow = $this->dateTimeUtility->convert('today');
				$tomorrow->modify('+1 day');
				$statement
					->addWhere('tx_events2_domain_model_day.day', '>=', $today)
					->addWhere('tx_events2_domain_model_day.day', '<', $tomorrow);
				break;
			case 'range':
				$today = $this->dateTimeUtility->convert('today');
				$in4months = $this->dateTimeUtility->convert('today');
				$in4months->modify('+4 weeks');
				$statement
					->addWhere('tx_events2_domain_model_day.day', '>=', $today)
					->addWhere('tx_events2_domain_model_day.day', '<=', $in4months);
				break;
			case 'thisWeek':
				$weekStart = $this->dateTimeUtility->convert('today');
				$weekStart->modify('first day of this week');
				$weekEnd = $this->dateTimeUtility->convert('today');
				$weekEnd->modify('last day of this week');
				$statement
					->addWhere('tx_events2_domain_model_day.day', '>=', $weekStart)
					->addWhere('tx_events2_domain_model_day.day', '<=', $weekEnd);
				break;
			case 'list':
			case 'latest':
			default:
				$today = $this->dateTimeUtility->convert('today');
				$statement->addWhere('tx_events2_domain_model_day.day', '>=', $today);
		}
		return $query->statement($statement->getStatement())->execute();
	}

	/**
	 * find culture events
	 *
	 * @param int $ageGroup
	 * @param int $suitabilityUser
	 * @param int $suitabilityGroups
	 * @param int $topic
	 * @param int $category
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findCultureEvents($ageGroup, $suitabilityUser = 0, $suitabilityGroups = 0, $topic = 0, $category = 0) {
		/** @var \TYPO3\CMS\Extbase\Persistence\Generic\Query $query */
		$query = $this->createQuery();
		$statement = $this->createStatement()->setQuery($query);
		$today = $this->dateTimeUtility->convert('today');

		$categories = array();
		$categories[] = $ageGroup;
		if ($topic) $categories[] = $topic;
		if ($category) $categories[] = $category;

		$statement
			->addWhere('tx_events2_domain_model_day.day', '>=', $today)
			->addWhere('tx_events2_domain_model_event.suitability_culture', '=', 1)
			->addWhereForCategories(implode(',', $categories));

		// add suitable user
		if ($suitabilityUser) {
			$statement->addWhere('tx_events2_domain_model_event.suitability_user', '=', '1');
		}
		// add suitable group
		if ($suitabilityGroups) {
			$statement->addWhere('tx_events2_domain_model_event.suitability_groups', '=', '1');
		}

		return $query->statement($statement->getStatement())->execute();
	}

	/**
	 * search for events
	 *
	 * @param \JWeiland\Events2\Domain\Model\Search $search
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function searchEvents(\JWeiland\Events2\Domain\Model\Search $search) {
		/** @var \TYPO3\CMS\Extbase\Persistence\Generic\Query $query */
		$query = $this->createQuery();
		$statement = $this->createStatement()->setQuery($query);

		// add query for search string
		if ($search->getSearch()) {
			$statement->addWhereForSearch($search->getSearch(), array('title', 'teaser'));
		}

		// add query for categories
		if ($search->getMainCategory()) {
			if ($search->getSubCategory()) {
				$statement->addWhereForCategories($search->getSubCategory()->getUid());
			} else {
				$statement->addWhereForCategories($search->getMainCategory()->getUid());
			}
		}

		// add query for event begin
		if ($search->getEventBegin()) {
			$statement->addWhere('tx_events2_domain_model_day.day', '>=', $search->getEventBegin()->format('U'));
		} else {
			$today = $this->dateTimeUtility->convert('today');
			$statement->addWhere('tx_events2_domain_model_day.day', '>=', $today->format('U'));
		}

		// add query for event end
		if ($search->getEventEnd()) {
			$statement->addWhere('tx_events2_domain_model_day.day', '<=', $search->getEventEnd()->format('U'));
		}

		// add query for event location
		if ($search->getLocation()) {
			$statement->addWhere('tx_events2_domain_model_event.location', '=', $search->getLocation()->getUid());
		}

		// add query for free entry
		if ($search->getFreeEntry()) {
			$statement->addWhere('tx_events2_domain_model_event.free_entry', '=', $search->getFreeEntry());
		}

		return $query->statement($statement->getStatement())->execute();
	}

	/**
	 * find events of a specified user
	 *
	 * @param int $organizer
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findMyEvents($organizer) {
		/** @var \TYPO3\CMS\Extbase\Persistence\Generic\Query $query */
		$query = $this->createQuery();
		$statement = $this->createStatement()
			->setQuery($query)
			->setSelect('tx_events2_domain_model_event.*')
			->setFeUsersRelation(TRUE)
			->addWhere('fe_users.uid', '=', (int)$organizer)
			->setOrderBy('tx_events2_domain_model_event.title')
			->setLimit('');

		return $query->statement($statement->getStatement())->execute();
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
		list($day) = $this->dataMapper->map('JWeiland\\Events2\\Domain\\Model\\Day', array($dayRecord));
		return $day;
	}

	/**
	 * create a statement object
	 *
	 * @return \JWeiland\Events2\Utility\StatementUtility
	 */
	protected function createStatement() {
		return $this->objectManager->get('JWeiland\\Events2\\Utility\\StatementUtility');
	}

}