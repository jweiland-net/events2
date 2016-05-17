<?php
namespace JWeiland\Events2\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Stefan Froemken <projects@jweiland.net>, jweiland.net
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
use JWeiland\Events2\Domain\Model\Filter;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class EventRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = array(
        'eventBegin' => QueryInterface::ORDER_ASCENDING,
    );

    /**
     * @var \JWeiland\Events2\Utility\DateTimeUtility
     */
    protected $dateTimeUtility = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
     */
    protected $dataMapper = null;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager = null;

    /**
     * @var array
     */
    protected $settings = array();

    /**
     * inject DateTime Utility.
     *
     * @param \JWeiland\Events2\Utility\DateTimeUtility $dateTimeUtility
     */
    public function injectDateTimeUtility(\JWeiland\Events2\Utility\DateTimeUtility $dateTimeUtility)
    {
        $this->dateTimeUtility = $dateTimeUtility;
    }

    /**
     * inject DataMapper.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper
     */
    public function injectDataMapper(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    /**
     * inject Configuration Manager.
     *
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
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
     * @return void
     */
    public function setSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * find event by uid whether it is hidden or not.
     *
     * @param int $eventUid
     *
     * @return \JWeiland\Events2\Domain\Model\Event
     */
    public function findHiddenEntryByUid($eventUid)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->getQuerySettings()->setEnableFieldsToBeIgnored(array('disabled'));
        $query->getQuerySettings()->setRespectStoragePage(false);

        return $query->matching($query->equals('uid', (int) $eventUid))->execute()->getFirst();
    }

    /**
     * find all events which can be released
     * -> facebook must be checked
     * -> releaseDate can not be empty.
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findUnreleasedEvents()
    {
        $query = $this->createQuery();
        $constraint = array();
        $constraint[] = $query->equals('facebook', 1);
        $constraint[] = $query->equals('releaseDate', 0);

        return $query->matching($query->logicalAnd($constraint))->execute();
    }

    /**
     * find top events.
     *
     * @param bool $mergeEvents
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function findTopEvents($mergeEvents = false)
    {
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Query $query */
        $query = $this->createQuery();
        $today = $this->dateTimeUtility->convert('today');
        $statement = $this->createStatement()
            ->setQuery($query)
            ->setSelect('tx_events2_domain_model_event.*, tx_events2_domain_model_day.uid as dayUid, tx_events2_domain_model_day.pid as dayPid, tx_events2_domain_model_day.day as dayDay, tx_events2_domain_model_day.events as dayEvents');

        if ($mergeEvents) {
            $statement->setMergeEvents(true);
        }

        $statement
            ->addWhere('tx_events2_domain_model_event.top_of_list', '=', 1)
            ->addWhere('tx_events2_domain_model_day.day', '>=', $today)
            ->setGroupBy('tx_events2_domain_model_event.uid')
            ->setLimit('');

        $records = $query->statement($statement->getStatement())->execute(true);

        // As long as domain models will be cached by their UID, we have to create our own event storage
        /** @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage $eventStorage */
        $eventStorage = new ObjectStorage();
        /* @var \JWeiland\Events2\Domain\Model\Event $event */
        foreach ($records as $record) {
            list($event) = $this->dataMapper->map('JWeiland\\Events2\\Domain\\Model\\Event', array($record));
            $event->setDay($this->getDayFromEvent($record));
            $eventStorage->attach(clone $event);
        }

        return $eventStorage;
    }

    /**
     * find events.
     *
     * @param string $type
     * @param Filter $filter
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findEvents($type, Filter $filter)
    {
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Query $query */
        $query = $this->createQuery();
        $statement = $this->createStatement()->setQuery($query);

        if (!empty($this->settings['categories'])) {
            $statement->setCategoryRelation(true)->addWhereForCategories($this->settings['categories']);
        }
        if ($this->settings['mergeEvents']) {
            $statement->setMergeEvents(true);
        }

        // add filter for organizer
        if ($filter->getOrganizer()) {
            $statement->addWhere(
                'tx_events2_domain_model_event.organizer',
                '=',
                $filter->getOrganizer()
            );
        } elseif ($this->settings['preFilterByOrganizer']) {
            $statement->addWhere(
                'tx_events2_domain_model_event.organizer',
                '=',
                $this->settings['preFilterByOrganizer']
            );
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
                $weekStart->modify('this week'); // 'first day of' does not work for 'weeks'
                $weekEnd = $this->dateTimeUtility->convert('today');
                $weekEnd->modify('this week +6 days'); // 'last day of' does not work for 'weeks'
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
     * search for events.
     *
     * @param \JWeiland\Events2\Domain\Model\Search $search
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function searchEvents(\JWeiland\Events2\Domain\Model\Search $search)
    {
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Query $query */
        $query = $this->createQuery();
        $statement = $this->createStatement()->setQuery($query);

        // add query for search string
        if ($search->getSearch()) {
            $statement->addWhereForSearch($search->getSearch(), array('title', 'teaser'));
        }

        // add query for categories
        if ($search->getMainCategory()) {
            $statement->addJoinForCategoryTable();
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
     * find events of a specified user.
     *
     * @param int $organizer
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findMyEvents($organizer)
    {
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Query $query */
        $query = $this->createQuery();
        $statement = $this->createStatement()
            ->setQuery($query)
            ->setSelect('tx_events2_domain_model_event.*')
            ->setFeUsersRelation(true)
            ->addWhere('fe_users.uid', '=', (int) $organizer)
            ->setGroupBy('tx_events2_domain_model_event.uid')
            ->setOrderBy('tx_events2_domain_model_event.title')
            ->setLimit('');

        return $query->statement($statement->getStatement())->execute();
    }

    /**
     * extract day from event record
     * With this method we save one additional SQL-Query.
     *
     * @param array $event
     *
     * @return \JWeiland\Events2\Domain\Model\Day
     */
    public function getDayFromEvent(array $event)
    {
        $dayRecord = array(
            'uid' => $event['dayUid'],
            'pid' => $event['dayPid'],
            'day' => $event['dayDay'],
            'events' => $event['dayEvents'],
        );
        list($day) = $this->dataMapper->map('JWeiland\\Events2\\Domain\\Model\\Day', array($dayRecord));

        return $day;
    }

    /**
     * create a statement object.
     *
     * @return \JWeiland\Events2\Utility\StatementUtility
     */
    protected function createStatement()
    {
        return $this->objectManager->get('JWeiland\\Events2\\Utility\\StatementUtility');
    }
}
