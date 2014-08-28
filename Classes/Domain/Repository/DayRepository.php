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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DayRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 * @inject
	 */
	protected $configurationManager;

	/**
	 * find all days
	 *
	 * @param integer $limit
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findAll($limit = 15) {
		// get storagePid
		$frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		$storagePid = $frameworkConfiguration['persistence']['storagePid'];

		$today = $this->createDateTime('today');

		// create query and return results
		$query = $this->createQuery();
		return $query->statement('
			SELECT DISTINCT tx_events2_domain_model_day.*
			FROM tx_events2_domain_model_day
			LEFT JOIN tx_events2_event_day_mm ON tx_events2_domain_model_day.uid=tx_events2_event_day_mm.uid_foreign
			LEFT JOIN tx_events2_domain_model_event ON tx_events2_event_day_mm.uid_local=tx_events2_domain_model_event.uid
			WHERE FIND_IN_SET(tx_events2_domain_model_event.pid, ?)
			AND tx_events2_domain_model_day.day > ?' .
			\TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tx_events2_domain_model_event') .
			\TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_events2_domain_model_event') .
			\TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tx_events2_domain_model_day') .
			\TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_events2_domain_model_day') . '
			ORDER BY tx_events2_domain_model_day.day ASC
			LIMIT ' . $limit,
			array(
				$storagePid,
				$today->format('U')
			)
		)->execute();
	}

	/**
	 * find all days with given categories
	 *
	 * @param string $categories comma seperated list of categories
	 * @param integer $limit
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findAllByCategories($categories, $limit = 15) {
		// get storagePid
		$frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		$storagePid = $frameworkConfiguration['persistence']['storagePid'];

		// create OR-Query for categories
		foreach (GeneralUtility::trimExplode(',', $categories) as $category) {
			$categoryOrQuery[] = 'sys_category_record_mm.uid_local IN (\'' . (int) $category . '\')';
		}

		$today = $this->createDateTime('today');

		// create query and return results
		$query = $this->createQuery();
		return $query->statement('
			SELECT DISTINCT tx_events2_domain_model_day.*
			FROM tx_events2_domain_model_day
			LEFT JOIN tx_events2_event_day_mm ON tx_events2_domain_model_day.uid=tx_events2_event_day_mm.uid_foreign
			LEFT JOIN tx_events2_domain_model_event ON tx_events2_event_day_mm.uid_local=tx_events2_domain_model_event.uid
			LEFT JOIN sys_category_record_mm ON tx_events2_domain_model_event.uid=sys_category_record_mm.uid_foreign
			WHERE (' . implode(' OR ', $categoryOrQuery) . ')
			AND sys_category_record_mm.tablenames = ?
			AND FIND_IN_SET(tx_events2_domain_model_event.pid, ?)
			AND tx_events2_domain_model_day.day > ?' .
			\TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tx_events2_domain_model_event') .
			\TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_events2_domain_model_event') .
			\TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tx_events2_domain_model_day') .
			\TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_events2_domain_model_day') . '
			ORDER BY tx_events2_domain_model_day.day ASC
			LIMIT ' . $limit,
			array(
				'tx_events2_domain_model_event',
				$storagePid,
				$today->format('U')
			)
		)->execute();
	}

	/**
	 * Creates a DateTime from an unix timestamp or date/datetime value.
	 * If the input is empty, NULL is returned.
	 *
	 * @param integer|string $value Unix timestamp or date/datetime value
	 * @return \DateTime
	 */
	public function createDateTime($value) {
		if (empty($value)) return NULL;
		if (MathUtility::canBeInterpretedAsInteger($value)) {
			$day = new \DateTime(date('c', $value));
			return $day->modify('midnight');
		} else {
			// native date/datetime values are stored in UTC
			$utcTimeZone = new \DateTimeZone('UTC');
			$utcDateTime = new \DateTime($value, $utcTimeZone);
			$currentTimeZone = new \DateTimeZone(date_default_timezone_get());
			return $utcDateTime->setTimezone($currentTimeZone);
		}
	}

	/**
	 * If no day was given for an event
	 * we have to try to find the next day for this event
	 *
	 * @param \JWeiland\Events2\Domain\Model\Event $event
	 * @return \JWeiland\Events2\Domain\Model\Day
	 */
	public function getNextDayForEvent(\JWeiland\Events2\Domain\Model\Event $event) {
		$query = $this->createQuery();
		$today = new \DateTime('today');

		$constraints = array();
		$constraints[] = $query->equals('events.uid', $event);
		$constraints[] = $query->greaterThanOrEqual('day', $today);

		return $query->matching($query->logicalAnd($constraints))->execute()->getFirst();
	}

	/**
	 * If no day was given for an event
	 * we have to try to find the last day for this event
	 *
	 * @param \JWeiland\Events2\Domain\Model\Event $event
	 * @return \JWeiland\Events2\Domain\Model\Day
	 */
	public function getLastDayForEvent(\JWeiland\Events2\Domain\Model\Event $event) {
		$query = $this->createQuery();
		$query->setOrderings(array(
			'day' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
		));

		return $query->matching($query->equals('events.uid', $event))->execute()->getFirst();
	}

}