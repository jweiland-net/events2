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
use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Model\Search;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
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
    protected $settings = array();
    
    /**
     * @var array
     */
    protected $defaultOrderings = array(
        'event.topOfList' => QueryInterface::ORDER_DESCENDING,
        'day' => QueryInterface::ORDER_ASCENDING,
    );
    
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
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findEvents($type, Filter $filter)
    {
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Query $query */
        $query = $this->createQuery();
        $constraint = array();
        
        if (!empty($this->settings['categories'])) {
            $orConstraint = array();
            foreach (GeneralUtility::intExplode(',', $this->settings['categories']) as $category) {
                $orConstraint[] = $query->contains('event.categories', $category);
            }
            $constraint[] = $query->logicalOr($orConstraint);
        }
        
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
            case 'list':
            case 'latest':
            default:
                $today = $this->dateTimeUtility->convert('today');
            $constraint[] = $query->greaterThanOrEqual('day', $today);
        }
        
        /** @var QueryResult $result */
        $result = $query->matching($query->logicalAnd($constraint))->execute();
        
        return $result;
    }
    
    /**
     * search for events.
     *
     * @param Search $search
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function searchEvents(Search $search)
    {
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Query $query */
        $query = $this->createQuery();
        $constraint = array();
        
        // add query for search string
        if ($search->getSearch()) {
            $orConstraint = array();
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
        }
        
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
     * Find days/events by timestamp
     *
     * @param int $timestamp
     *
     * @return QueryResult
     */
    public function findByTimestamp($timestamp)
    {
        $constraint = array();
        $query = $this->createQuery();
        if (!empty($this->settings['categories'])) {
            $orConstraint = array();
            foreach (GeneralUtility::intExplode(',', $this->settings['categories']) as $category) {
                $orConstraint[] = $query->contains('event.categories', $category);
            }
            $constraint[] = $query->logicalOr($orConstraint);
        }
        $constraint[] = $query->equals('day', $timestamp);
        /** @var QueryResult $result */
        $result = $query->matching($query->logicalAnd($constraint))->execute();
        
        return $result;
    }
}
