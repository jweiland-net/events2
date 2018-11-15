<?php

namespace JWeiland\Events2\Domain\Model;

/*
 * This file is part of the events2 project.
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
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * This class will not be persisted. It's a helper class to manage search requests.
 */
class Search extends AbstractEntity
{
    /**
     * Searchword.
     *
     * @var string
     */
    protected $search = '';

    /**
     * mainCategory.
     *
     * @var \JWeiland\Events2\Domain\Model\Category
     */
    protected $mainCategory;

    /**
     * subCategory.
     *
     * @var \JWeiland\Events2\Domain\Model\Category
     */
    protected $subCategory;

    /**
     * eventBegin.
     *
     * @var string
     */
    protected $eventBegin;

    /**
     * eventEnd.
     *
     * @var string
     */
    protected $eventEnd;

    /**
     * location.
     *
     * @var \JWeiland\Events2\Domain\Model\Location
     */
    protected $location;

    /**
     * freeEntry.
     *
     * @var bool
     */
    protected $freeEntry = false;

    /**
     * Returns the search.
     *
     * @return string $search
     */
    public function getSearch()
    {
        return $this->search;
    }

    /**
     * Sets the search.
     *
     * @param string $search
     */
    public function setSearch($search)
    {
        $this->search = htmlspecialchars((string)$search);
    }

    /**
     * Returns the mainCategory.
     *
     * @return \JWeiland\Events2\Domain\Model\Category $mainCategory
     */
    public function getMainCategory()
    {
        return $this->mainCategory;
    }

    /**
     * Sets the mainCategory.
     *
     * @param Category $mainCategory
     */
    public function setMainCategory(Category $mainCategory = null)
    {
        $this->mainCategory = $mainCategory;
    }

    /**
     * Returns the subCategory.
     *
     * @return Category $subCategory
     */
    public function getSubCategory()
    {
        return $this->subCategory;
    }

    /**
     * Sets the subCategory.
     *
     * @param Category $subCategory
     */
    public function setSubCategory(Category $subCategory = null)
    {
        $this->subCategory = $subCategory;
    }

    /**
     * Returns the eventBegin.
     *
     * @return \DateTime|null $eventBegin
     */
    public function getEventBegin()
    {
        if (empty($this->eventBegin)) {
            return null;
        }
        try {
            $date = new \DateTime($this->eventBegin);
            $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        } catch (\Exception $exception) {
            $date = null;
        }

        return $date;
    }

    /**
     * Sets the eventBegin.
     *
     * @param string $eventBegin
     */
    public function setEventBegin($eventBegin)
    {
        $this->eventBegin = $eventBegin;
    }

    /**
     * Returns the eventEnd.
     *
     * @return \DateTime|null $eventEnd
     */
    public function getEventEnd()
    {
        if (empty($this->eventEnd)) {
            return null;
        }
        try {
            $date = new \DateTime($this->eventEnd);
            $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        } catch (\Exception $exception) {
            $date = null;
        }

        return $date;
    }

    /**
     * Sets the eventEnd.
     *
     * @param string $eventEnd
     */
    public function setEventEnd($eventEnd)
    {
        $this->eventEnd = $eventEnd;
    }

    /**
     * Returns the location.
     *
     * @return \JWeiland\Events2\Domain\Model\Location $location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Sets the location.
     *
     * @param Location $location
     */
    public function setLocation(Location $location = null)
    {
        $this->location = $location;
    }

    /**
     * Returns the freeEntry.
     *
     * @return bool $freeEntry
     */
    public function getFreeEntry()
    {
        return $this->freeEntry;
    }

    /**
     * Sets the freeEntry.
     *
     * @param bool $freeEntry
     */
    public function setFreeEntry($freeEntry)
    {
        $this->freeEntry = (bool)$freeEntry;
    }
}
