<?php

namespace JWeiland\Events2\Domain\Repository;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Model\Search;
use JWeiland\Events2\Persistence\Typo362\Generic\Query;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DayRepository extends Repository
{
    /**
     * @var DateTimeUtility
     */
    protected $dateTimeUtility;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @var array
     */
    protected $defaultOrderings = [
        'event.topOfList' => QueryInterface::ORDER_DESCENDING,
        'sortDayTime' => QueryInterface::ORDER_ASCENDING,
        'dayTime' => QueryInterface::ORDER_ASCENDING
    ];

    /**
     * inject DateTime Utility.
     *
     * @param DateTimeUtility $dateTimeUtility
     */
    public function injectDateTimeUtility(DateTimeUtility $dateTimeUtility)
    {
        $this->dateTimeUtility = $dateTimeUtility;
    }

    /**
     * Returns the settings
     *
     * @return array $settings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Sets the settings
     *
     * @param array $settings
     *
     * @return void
     */
    public function setSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * find events.
     *
     * @param string $type
     * @param Filter $filter
     * @param int $limit
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     *
     * @throws \Exception
     */
    public function findEvents($type, Filter $filter, $limit = 0)
    {
        /** @var Query $query */
        $query = $this->createQuery();
        $this->addGroupingToQuery($query);
        $constraint = [];

        // add categories
        if (!empty($this->settings['categories'])) {
            $constraint[] = $query->in('event.categories.uid', GeneralUtility::intExplode(',', $this->settings['categories'], true));
        }

        // add storage PIDs. But not for sys_category
        // @link: https://forge.typo3.org/issues/83296
        $query->getQuerySettings()->setRespectStoragePage(false);
        $constraints[] = $query->in('pid', $query->getQuerySettings()->getStoragePageIds());
        $constraints[] = $query->in('event.pid', $query->getQuerySettings()->getStoragePageIds());

        // add filter for organizer
        if ($filter->getOrganizer()) {
            $constraint[] = $query->equals('event.organizer', $filter->getOrganizer());
        } elseif ($this->settings['preFilterByOrganizer']) {
            $constraint[] = $query->equals('event.organizer', $this->settings['preFilterByOrganizer']);
        }

        switch ($type) {
            case 'today':
                $today = $this->dateTimeUtility->convert('today');
                $tomorrow = $this->dateTimeUtility->convert('today');
                $tomorrow->modify('+1 day');
                $constraint[] = $query->greaterThanOrEqual('day', $today);
                $constraint[] = $query->lessThan('day', $tomorrow);
                break;
            case 'range':
                $today = $this->dateTimeUtility->convert('today');
                $in4months = $this->dateTimeUtility->convert('today');
                $in4months->modify('+4 weeks');
                $constraint[] = $query->greaterThanOrEqual('day', $today);
                $constraint[] = $query->lessThanOrEqual('day', $in4months);
                break;
            case 'thisWeek':
                $weekStart = $this->dateTimeUtility->convert('today');
                $weekStart->modify('this week'); // 'first day of' does not work for 'weeks'
                $weekEnd = $this->dateTimeUtility->convert('today');
                $weekEnd->modify('this week +6 days'); // 'last day of' does not work for 'weeks'
                $constraint[] = $query->greaterThanOrEqual('day', $weekStart);
                $constraint[] = $query->lessThanOrEqual('day', $weekEnd);
                break;
            case 'latest':
            case 'list':
            default:
                $today = $this->dateTimeUtility->convert('today');
                $constraint[] = $query->greaterThanOrEqual('day', $today);
        }

        if (!empty($limit)) {
            $query->setLimit((int)$limit);
        }

        /** @var QueryResult $result */
        $result = $query->matching($query->logicalAnd($constraint))->execute();

        return $result;
    }

    /**
     * Special method for latest view
     * It groups day records by event and keeps the event with earliest date in array
     *
     * That way we prevent the problem with SQL, where GROUP BY was executed before ORDER BY and we get a
     * random value for day, day_time and sort_day_time
     *
     * @param QueryResultInterface $queryResult
     * @param int $maxRecords
     *
     * @return Day[]
     *
     * @throws \Exception
     */
    public function groupDaysByEventAndSort(QueryResultInterface $queryResult, $maxRecords)
    {
        $days = [];
        $reset = true;
        $limit = 15;
        $offset = 0;
        $query = $queryResult->getQuery();

        do {
            $records = $query
                ->setLimit($limit)
                ->setOffset($offset)
                ->execute();

            if (!$records->count()) {
                break;
            }

            /** @var Day $record */
            foreach ($records as $record) {
                // add new record to day array as long as maxRecords has not been reached
                if (count($days) < (int)$maxRecords && !array_key_exists($record->getEvent()->getUid(), $days)) {
                    $days[$record->getEvent()->getUid()] = $record;
                }

                // replace older with earlier record, if event exists in day array
                if (array_key_exists($record->getEvent()->getUid(), $days)) {
                    /** @var Day $day */
                    $day = $days[$record->getEvent()->getUid()];
                    if ($record->getSortDayTime() < $day->getSortDayTime()) {
                        $days[$record->getEvent()->getUid()] = $record;
                    }
                }
                // we can not break out of this foreach/do loop as we have to find ALL related day records
                // maybe there are some more days with an earlier date
            }

            $offset += 15;
            if (count($days) === (int)$maxRecords && $reset) {
                // as some customers may have thousands of day records we reduce the records to the just known events,
                // if count() matches $maxRecords and resets the offset
                $query->matching($query->logicalAnd([
                    $query->getConstraint(),
                    $query->in('event.uid', array_keys($days))
                ]));
                $offset = 0;
                $reset = false;
            }
        } while (1 == 1);

        return $this->sortDays($days);
    }

    /**
     * This method belongs to groupDaysByEventAndSort
     * and is only valid for latest view
     *
     * @param Day[] $records
     * @param string $sortBy
     *
     * @return Day[]
     *
     * @throws \Exception
     */
    protected function sortDays($records, $sortBy = 'day')
    {
        $dates = [];

        $getter = 'get' . ucfirst($sortBy);
        if (!method_exists('JWeiland\\Events2\\Domain\\Model\\Day', $getter)) {
            throw new \Exception('Method "' . $getter . '" does not exists in Day', 1499429014);
        }

        foreach ($records as $key => $record) {
            $dates[$key] = $record->{$getter}()->format('U');
        }
        array_multisort($dates, SORT_ASC, SORT_NUMERIC, $records);

        return $records;
    }

    /**
     * search for events.
     *
     * @param Search $search
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     *
     * @throws \Exception
     */
    public function searchEvents(Search $search)
    {
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Query $query */
        $query = $this->createQuery();
        $constraint = [];

        // add query for search string
        if ($search->getSearch()) {
            $orConstraint = [];
            $orConstraint[] = $query->like('event.title', '%' . $search->getSearch() . '%');
            $orConstraint[] = $query->like('event.teaser', '%' . $search->getSearch() . '%');
            $constraint[] = $query->logicalOr($orConstraint);
        }

        // add query for categories
        if ($search->getMainCategory()) {
            if ($search->getSubCategory()) {
                $constraint[] = $query->contains('event.categories', $search->getSubCategory()->getUid());
            } else {
                $constraint[] = $query->contains('event.categories', $search->getMainCategory()->getUid());
            }
        } else {
            // visitor has not selected any category. Search within allowed categories in plugin configuration
            $constraint[] = $query->in('event.categories.uid', GeneralUtility::trimExplode(',', $this->settings['categories']));
        }

        // add storage PIDs. But not for sys_category
        // @link: https://forge.typo3.org/issues/83296
        $query->getQuerySettings()->setRespectStoragePage(false);
        $constraints[] = $query->in('pid', $query->getQuerySettings()->getStoragePageIds());
        $constraints[] = $query->in('event.pid', $query->getQuerySettings()->getStoragePageIds());

        // add query for event begin
        if ($search->getEventBegin()) {
            $constraint[] = $query->greaterThanOrEqual('day', $search->getEventBegin());
        } else {
            $today = $this->dateTimeUtility->convert('today');
            $constraint[] = $query->greaterThanOrEqual('day', $today);
        }

        // add query for event end
        if ($search->getEventEnd()) {
            $constraint[] = $query->lessThanOrEqual('day', $search->getEventEnd());
        }

        // add query for event location
        if ($search->getLocation()) {
            $constraint[] = $query->equals('event.location', $search->getLocation()->getUid());
        }

        // add query for free entry
        if ($search->getFreeEntry()) {
            $constraint[] = $query->equals('event.freeEntry', $search->getFreeEntry());
        }

        if (count($constraint)) {
            return $query->matching($query->logicalAnd($constraint))->execute();
        } else {
            return $query->execute();
        }
    }

    /**
     * Find day by UID
     *
     * @param int $day
     *
     * @return Day
     */
    public function findByDay($day)
    {
        /** @var \JWeiland\Events2\Persistence\Typo376\Generic\Query $query */
        $query = $this->createQuery();
        $this->addGroupingToQuery($query);
        $query->matching($query->equals('uid', (int)$day));
        /** @var Day $day */
        $day = $query->execute()->getFirst();
        return $day;
    }

    /**
     * Find days/events by timestamp
     *
     * @param int $timestamp
     *
     * @return QueryResult
     *
     * @throws \Exception
     */
    public function findByTimestamp($timestamp)
    {
        $constraint = [];
        $query = $this->createQuery();
        $this->addGroupingToQuery($query);
        if (!empty($this->settings['categories'])) {
            $constraint[] = $query->in('event.categories.uid', GeneralUtility::intExplode(',', $this->settings['categories']));
        }
        $constraint[] = $query->equals('day', $timestamp);

        /** @var QueryResult $result */
        $result = $query->matching($query->logicalAnd($constraint))->execute();

        return $result;
    }

    /**
     * Find a day by event and timestamp
     * If timestamp is empty, we try to find next possible day in future
     *
     * @param int $eventUid
     * @param int $timestamp
     *
     * @return Day|null
     *
     * @throws \Exception
     */
    public function findOneByTimestamp($eventUid, $timestamp = 0)
    {
        $query = $this->createQuery();

        $constraints = [];
        $constraints[] = $query->equals('event', (int)$eventUid);

        if (empty($timestamp)) {
            $today = new \DateTime('now');
            $constraints[] = $query->greaterThanOrEqual('dayTime', $today);

            $query->setOrderings([
                'dayTime' => QueryInterface::ORDER_ASCENDING
            ]);
        } else {
            $constraints[] = $query->equals('dayTime', $timestamp);
        }

        /** @var Day $day */
        $day = $query->matching($query->logicalAnd($constraints))->execute()->getFirst();

        return $day;
    }

    /**
     * Add special grouping
     *
     * @param QueryInterface $query
     *
     * @return void
     */
    protected function addGroupingToQuery(QueryInterface $query)
    {
        /** @var Query $query */
        $query->setGroupings(['event', 'sortDayTime']);
    }
}
