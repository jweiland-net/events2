<?php
namespace JWeiland\Events2\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Stefan Froemken <projects@jweiland.net>, jweiland.net
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
 * the pagebrowser is my own implementation and needs SQL-Statements
 * with markers to work
 * This object helps me to generate such statements
 *
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class StatementUtility {

	/**
	 * @var \TYPO3\CMS\Frontend\Page\PageRepository
	 */
	protected $pageRepository;

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseConnection;

	/**
	 * Query from foreign object to retrieve storage pids
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 */
	protected $query = NULL;

	/**
	 * a collection of involved tables to create enableFields where clause
	 *
	 * @var array
	 */
	protected $tables = array();

	/**
	 * select statement
	 * for now it is hardcoded
	 *
	 * @var string
	 */
	protected $select = '###SELECT###';

	/**
	 * in this object we only need records from event table
	 *
	 * @var string
	 */
	protected $from = 'tx_events2_domain_model_event';

	/**
	 * a collection of joins to other tables
	 *
	 * @var array
	 */
	protected $join = array();

	/**
	 * a collection of AND connected WHERE clauses
	 *
	 * @var array
	 */
	protected $where = array();

	/**
	 * a collection of AND connected WHERE clauses to merge events
	 *
	 * @var array
	 */
	protected $whereToMergeEvents = array();

	/**
	 * group by
	 *
	 * @var string
	 */
	protected $groupBy = '';

	/**
	 * order by
	 *
	 * @var string
	 */
	protected $orderBy = '';

	/**
	 * my pagebrowser replaces this marker itself
	 * so for now it is hardcoded
	 *
	 * @var string
	 */
	protected $limit = '###LIMIT###';

	/**
	 * indicator to check, if statement should have a relation to day table
	 *
	 * @var bool
	 */
	protected $hasDayRelation = TRUE;

	/**
	 * indicator to check, if statement should have a relation to category table
	 *
	 * @var bool
	 */
	protected $hasCategoryRelation = FALSE;

	/**
	 * indicator to check, if statement should have a relation to fe_users table
	 *
	 * @var bool
	 */
	protected $hasFeUsersRelation = FALSE;

	/**
	 * indicator to check, if events should be merged
	 *
	 * @var bool
	 */
	protected $hasToMergeEvents = FALSE;

	/**
	 * inject page repository
	 *
	 * @param \TYPO3\CMS\Frontend\Page\PageRepository $pageRepository
	 * @return void
	 */
	public function injectPageRepository(\TYPO3\CMS\Frontend\Page\PageRepository $pageRepository) {
		$this->pageRepository = $pageRepository;
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
	 * Getter for query
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 */
	public function getQuery() {
		return $this->query;
	}

	/**
	 * Setter for query
	 * this is needed to retrieve storage pids
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
	 * @return \JWeiland\Events2\Utility\StatementUtility
	 */
	public function setQuery(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query) {
		$this->query = $query;
		return $this;
	}

	/**
	 * Returns the select
	 *
	 * @return string $select
	 */
	public function getSelect() {
		return $this->select;
	}

	/**
	 * Sets the select
	 *
	 * @param string $select
	 * @return \JWeiland\Events2\Utility\StatementUtility
	 */
	public function setSelect($select) {
		$this->select = (string)$select;
		return $this;
	}

	/**
	 * Returns the from
	 *
	 * @return string $from
	 */
	public function getFrom() {
		return $this->from;
	}

	/**
	 * Sets the from
	 *
	 * @param string $from
	 * @return void
	 */
	public function setFrom($from) {
		$this->from = $from;
	}

	/**
	 * Getter for JOIN
	 *
	 * @return string
	 */
	public function getJoins() {
		if ($this->hasDayRelation()) {
			$this->addJoinForDayTable();
		}
		if ($this->hasCategoryRelation()) {
			$this->addJoinForCategoryTable();
		}
		if ($this->hasFeUsersRelation()) {
			$this->addJoinForFeUsersTable();
		}
		return implode(' LEFT JOIN ', $this->join);
	}

	/**
	 * add join for day table
	 *
	 * @return void
	 */
	public function addJoinForDayTable() {
		$this->join[] = 'tx_events2_event_day_mm ON tx_events2_domain_model_event.uid=tx_events2_event_day_mm.uid_local';
		$this->join[] = 'tx_events2_domain_model_day ON tx_events2_event_day_mm.uid_foreign=tx_events2_domain_model_day.uid';
		$this->tables[] = 'tx_events2_domain_model_day';
	}

	/**
	 * add join for category table
	 *
	 * @return void
	 */
	public function addJoinForCategoryTable() {
		$this->join[] = 'sys_category_record_mm ON tx_events2_domain_model_event.uid=sys_category_record_mm.uid_foreign';
		$this->join[] = 'sys_category ON sys_category_record_mm.uid_local=sys_category.uid';
		$this->where[] = 'sys_category_record_mm.tablenames = \'' . $this->getFrom() . '\'';
		$this->tables[] = 'sys_category';
	}

	/**
	 * add join for fe_users table
	 *
	 * @return void
	 */
	public function addJoinForFeUsersTable() {
		$this->join[] = 'fe_users ON fe_users.tx_events2_organizer=tx_events2_domain_model_event.organizer';
		$this->tables[] = 'fe_users';
	}

	/**
	 * Getter for where condition
	 *
	 * @return string
	 */
	public function getWhere() {
		$this->addWhereForStoragePids();
		$this->addWhereForSysLanguage();
		if ($this->hasToMergeEvents()) {
			$this->addWhereToMergeEvents();
		}
		return implode(' AND ', $this->where);
	}

	/**
	 * add where clause for storage pids
	 *
	 * @return void
	 */
	protected function addWhereForStoragePids() {
		$this->where[] = 'FIND_IN_SET(tx_events2_domain_model_event.pid, ' . $this->getStoragePids() . ')';
	}

	/**
	 * add where clause for language
	 *
	 * @return void
	 */
	protected function addWhereForSysLanguage() {
		$this->where[] = 'tx_events2_domain_model_event.sys_language_uid = ' . $GLOBALS['TSFE']->sys_language_uid;
	}

	/**
	 * add where clause for categories
	 *
	 * @param string $categories
	 * @return void
	 */
	public function addWhereForCategories($categories) {
		$categories = $this->databaseConnection->quoteStr($categories, $this->getFrom());
		$this->where[] = 'sys_category_record_mm.uid_local IN (' . (string)$categories . ')';
	}

	/**
	 * add where clause for search
	 *
	 * @param string $searchWord
	 * @param array $fields
	 * @return void
	 */
	public function addWhereForSearch($searchWord, $fields) {
		$where = array();
		$searchWord = $this->databaseConnection->escapeStrForLike(
			$this->databaseConnection->quoteStr($searchWord, 'tx_events2_domain_model_event'),
			'tx_events2_domain_model_event'
		);
		foreach ($fields as $field) {
			$fieldName = 'tx_events2_domain_model_event.' . $field;
			$where[] = $fieldName . ' LIKE \'%' . $searchWord . '%\'';
		}
		$this->where[] = '(' . implode(' OR ', $where) . ')';
	}

	/**
	 * add where clause to merge events
	 *
	 * @return void
	 */
	public function addWhereToMergeEvents() {
		$this->where[] = 'tx_events2_domain_model_day.day = (SELECT day
			FROM tx_events2_domain_model_day LEFT JOIN tx_events2_event_day_mm
			ON tx_events2_domain_model_day.uid=tx_events2_event_day_mm.uid_foreign
			WHERE tx_events2_event_day_mm.uid_local = tx_events2_domain_model_event.uid
			AND ' . implode(' AND ', $this->whereToMergeEvents) . '
			ORDER BY tx_events2_domain_model_day.day ASC
			LIMIT 1)
		';
	}

	/**
	 * getWhere clause for enableFields
	 *
	 * @return string
	 */
	public function getWhereWhereForEnableFields() {
		$where = '';
		$this->tables[] = 'tx_events2_domain_model_event';
		foreach ($this->tables as $table) {
			$where .= $this->pageRepository->enableFields($table);
		}
		return $where;
	}

	/**
	 * add a where clause to collection
	 *
	 * @param string $property
	 * @param string $operator
	 * @param mixed $value
	 * @return \JWeiland\Events2\Utility\StatementUtility
	 */
	public function addWhere($property, $operator, $value) {
		if (MathUtility::canBeInterpretedAsInteger($value)) {
			$parsedValue = (int)$value;
		} elseif ($value instanceof \DateTime) {
			/** @var \DateTime $value */
			$parsedValue = (int)$value->format('U');
		} else {
			$parsedValue = $this->databaseConnection->fullQuoteStr($value, $this->getFrom());
		}
		if ($this->hasToMergeEvents() && $value instanceof \DateTime) {
			$this->whereToMergeEvents[] = 'day' . ' ' . $operator . ' ' . $parsedValue;
		} else {
			$this->where[] = $property . ' ' . $operator . ' ' . $parsedValue;
		}
		return $this;
	}

	/**
	 * Returns the limit
	 *
	 * @return string $limit
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * Sets the limit
	 *
	 * @param string $limit
	 * @return \JWeiland\Events2\Utility\StatementUtility
	 */
	public function setLimit($limit) {
		$this->limit = (string)$limit;
		return $this;
	}

	/**
	 * Returns the groupBy
	 *
	 * @return string $groupBy
	 */
	public function getGroupBy() {
		return $this->groupBy;
	}

	/**
	 * Sets the groupBy
	 *
	 * @param string $groupBy
	 * @return \JWeiland\Events2\Utility\StatementUtility
	 */
	public function setGroupBy($groupBy) {
		$this->groupBy = $groupBy;
		return $this;
	}

	/**
	 * Returns the orderBy
	 *
	 * @return string $orderBy
	 */
	public function getOrderBy() {
		return $this->orderBy;
	}

	/**
	 * Sets the orderBy
	 *
	 * @param string $orderBy
	 * @return \JWeiland\Events2\Utility\StatementUtility
	 */
	public function setOrderBy($orderBy) {
		$this->orderBy = $orderBy;
		return $this;
	}

	/**
	 * Returns the dayRelation
	 *
	 * @return bool $hasDayRelation
	 */
	public function hasDayRelation() {
		return $this->hasDayRelation;
	}

	/**
	 * Sets the dayRelation indicator
	 *
	 * @param bool $dayRelation
	 * @return \JWeiland\Events2\Utility\StatementUtility
	 */
	public function setDayRelation($dayRelation) {
		$this->hasDayRelation = $dayRelation;
		return $this;
	}

	/**
	 * Returns the categoryRelation
	 *
	 * @return bool $hasCategoryRelation
	 */
	public function hasCategoryRelation() {
		return $this->hasCategoryRelation;
	}

	/**
	 * Sets the categoryRelation indicator
	 *
	 * @param bool $categoryRelation
	 * @return \JWeiland\Events2\Utility\StatementUtility
	 */
	public function setCategoryRelation($categoryRelation) {
		$this->hasCategoryRelation = $categoryRelation;
		return $this;
	}

	/**
	 * Returns the feUsersRelation
	 *
	 * @return bool $hasFeUsersRelation
	 */
	public function hasFeUsersRelation() {
		return $this->hasFeUsersRelation;
	}

	/**
	 * Sets the feUsersRelation indicator
	 *
	 * @param bool $feUsersRelation
	 * @return \JWeiland\Events2\Utility\StatementUtility
	 */
	public function setFeUsersRelation($feUsersRelation) {
		$this->hasFeUsersRelation = $feUsersRelation;
		return $this;
	}

	/**
	 * Returns the merge events indicator
	 *
	 * @return bool $hasToMergeEvents
	 */
	public function hasToMergeEvents() {
		return $this->hasToMergeEvents;
	}

	/**
	 * Sets the hasToMergeEvents indicator
	 *
	 * @param bool $mergeEvents
	 * @return \JWeiland\Events2\Utility\StatementUtility
	 */
	public function setMergeEvents($mergeEvents) {
		$this->hasToMergeEvents = $mergeEvents;
		return $this;
	}

	/**
	 * get StoragePids
	 *
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
	 * @return string
	 */
	protected function getStoragePids() {
		return $this->databaseConnection->fullQuoteStr(
			implode(',', $this->getQuery()->getQuerySettings()->getStoragePageIds()),
			$this->getFrom()
		);
	}

	/**
	 * this method collects all needed data from the properties and creates our
	 * needed SELECT Statement which was needed by my pagebrowser
	 *
	 * @return string
	 */
	public function getStatement() {
		$statement = array();
		$statement[] = 'SELECT ' . $this->getSelect();
		$statement[] = 'FROM ' . $this->getFrom();
		$statement[] = 'LEFT JOIN ' . $this->getJoins();
		$statement[] = 'WHERE ' . $this->getWhere() . $this->getWhereWhereForEnableFields();
		if ($groupBy = $this->getGroupBy()) {
			$statement[] = 'GROUP BY ' . $groupBy;
		}
		if ($orderBy = $this->getOrderBy()) {
			$statement[] = 'ORDER BY ' . $orderBy;
		}
		if ($limit = $this->getLimit()) {
			$statement[] = $limit;
		}

		return implode(' ', $statement);
	}

}