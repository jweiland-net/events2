<?php
namespace JWeiland\Events2\Tca;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CreateUpdateDays {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var \JWeiland\Events2\Tca\DayGenerator
	 */
	protected $dayGenerator;

	protected $eventRecord = array();





	/**
	 * initializes this object
	 */
	public function init() {
		$this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->dayGenerator = $this->objectManager->get('JWeiland\\Events2\\Tca\\DayGenerator');
	}

	/**
	 * @param string $status "new" od something else to update the record
	 * @param string $table The table name
	 * @param int $uid The UID of the new or updated record. Can be prepended with NEW if record is new. Use: $this->substNEWwithIDs to convert
	 * @param array $fieldArray The fields of the current record
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
	 * @return void
	 */
	public function processDatamap_afterDatabaseOperations($status, $table, $uid, array $fieldArray, \TYPO3\CMS\Core\DataHandling\DataHandler $pObj) {
		// TODO: add something to hide/delete related records

		// process this hook only on expected table
		if ($table !== 'tx_events2_domain_model_event') {
			return;
		}

		$this->init();

		if ($status === 'new') {
			$uid = current($pObj->substNEWwithIDs);
		}

		$this->eventRecord = $this->getFullEventRecord($table, $uid);

		$this->dayGenerator->initialize($this->eventRecord);
		$days = $this->dayGenerator->getDayStorage();

		// delete entries with cureent event uid from mm-table
		$this->deleteAllRelatedRecords($this->eventRecord['uid']);

		foreach ($days as $day) {
			$this->addDay($day);
		}

		// add days amount to event
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_events2_domain_model_event',
			'uid = ' . $this->eventRecord['uid'],
			array(
				'days' => count($days)
			)
		);
	}

	/**
	 * set event record
	 * this method was useful if this class was called from outside
	 *
	 * @param array $eventRecord
	 * @return void
	 */
	public function setEventRecord(array $eventRecord) {
		$this->eventRecord = $eventRecord;
	}

	/**
	 * add day to db
	 * Also MM-Tables will be filled
	 *
	 * @param \DateTime $day
	 * @return int UID of inserted day
	 */
	public function addDay(\DateTime $day) {
		// add day
		$dayUid = $this->addDayRecord($day);

		// add relation in mm-table
		$this->addRelation($this->eventRecord['uid'], $dayUid, $day);

		// add amount of events to day record
		$amount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
			'*',
			'tx_events2_event_day_mm',
			'uid_foreign = ' . $dayUid
		);
		if ($amount) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'tx_events2_domain_model_day',
				'uid = ' . $dayUid,
				array(
					'events' => $amount
				)
			);
		}
		return $dayUid;
	}

	/**
	 * get full event record
	 * While updating a record only the changed fields will be in $fieldArray
	 *
	 * @param string $table
	 * @param int $uid
	 * @return array
	 */
	public function getFullEventRecord($table, $uid) {
		$event = BackendUtility::getRecord($table, $uid);
		if ($event['exceptions']) {
			$event['exceptions'] = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
				'tx_events2_domain_model_exception',
				'event = ' . (int) $uid .
				BackendUtility::BEenableFields('tx_events2_domain_model_exception') .
				BackendUtility::deleteClause('tx_events2_domain_model_exception')
			);
		}
		return $event;
	}

	/**
	 * add day record if not already exists
	 *
	 * @param \DateTime $day
	 * @return int The affected row uid
	 */
	public function addDayRecord(\DateTime $day) {
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'uid',
			'tx_events2_domain_model_day',
			'day=' . $day->format('U') .
				BackendUtility::BEenableFields('tx_events2_domain_model_day') .
				BackendUtility::deleteClause('tx_events2_domain_model_day')
		);

		if ($row === NULL) {
			// TODO: Errorhandling
			return 0;
		} elseif ($row === FALSE) {
			// insert
			$fieldsArray = array();
			$fieldsArray['day'] = $day->format('U');
			$fieldsArray['tstamp'] = time();
			$fieldsArray['pid'] = $this->eventRecord['pid'];
			$fieldsArray['crdate'] = time();
			$fieldsArray['cruser_id'] = $GLOBALS['BE_USER']->user['uid'];
			$fieldsArray['sys_language_uid'] = $this->eventRecord['sys_language_uid'];

			$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_events2_domain_model_day', $fieldsArray);
			return $GLOBALS['TYPO3_DB']->sql_insert_id();
		} else {
			return $row['uid'];
		}
	}

	/**
	 * add relation to day record in mm table
	 *
	 * @param int $eventUid
	 * @param int $dayUid
	 * @param \DateTime $day
	 * @return void
	 */
	public function addRelation($eventUid, $dayUid, \DateTime $day) {
		// we don't need a SELECT query here, because we have deleted all related records just before
		// create field array to insert
		$fieldsArray = array();
		$fieldsArray['uid_local'] = $eventUid;
		$fieldsArray['uid_foreign'] = $dayUid;
		$fieldsArray['sorting'] = $day->format('U');

		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_events2_event_day_mm', $fieldsArray);
	}

	/**
	 * delete all related records from mm-table
	 *
	 * @param $eventUid
	 */
	public function deleteAllRelatedRecords($eventUid) {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'tx_events2_event_day_mm',
			'uid_local=' . $eventUid
		);
	}

}