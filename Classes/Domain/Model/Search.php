<?php

namespace JWeiland\Events2\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Stefan Froemken <projects@jweiland.net>, jweiland.net
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

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Search extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
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
        $this->search = htmlspecialchars((string) $search);
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
     * @param \JWeiland\Events2\Domain\Model\Category $mainCategory
     */
    public function setMainCategory(\JWeiland\Events2\Domain\Model\Category $mainCategory = null)
    {
        $this->mainCategory = $mainCategory;
    }

    /**
     * Returns the subCategory.
     *
     * @return \JWeiland\Events2\Domain\Model\Category $subCategory
     */
    public function getSubCategory()
    {
        return $this->subCategory;
    }

    /**
     * Sets the subCategory.
     *
     * @param \JWeiland\Events2\Domain\Model\Category $subCategory
     */
    public function setSubCategory(\JWeiland\Events2\Domain\Model\Category $subCategory = null)
    {
        $this->subCategory = $subCategory;
    }

    /**
     * Returns the eventBegin.
     *
     * @return \DateTime $eventBegin
     */
    public function getEventBegin()
    {
        if (empty($this->eventBegin)) {
            return;
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
     * @return \DateTime $eventEnd
     */
    public function getEventEnd()
    {
        if (empty($this->eventEnd)) {
            return;
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
     * @param \JWeiland\Events2\Domain\Model\Location $location
     */
    public function setLocation(\JWeiland\Events2\Domain\Model\Location $location = null)
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
        $this->freeEntry = (bool) $freeEntry;
    }
}
