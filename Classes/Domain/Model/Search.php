<?php
declare(strict_types = 1);
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
     * @var string
     */
    protected $search = '';

    /**
     * @var \JWeiland\Events2\Domain\Model\Category
     */
    protected $mainCategory;

    /**
     * @var \JWeiland\Events2\Domain\Model\Category
     */
    protected $subCategory;

    /**
     * @var string
     */
    protected $eventBegin = '';

    /**
     * @var string
     */
    protected $eventEnd = '';

    /**
     * @var \JWeiland\Events2\Domain\Model\Location
     */
    protected $location;

    /**
     * @var bool
     */
    protected $freeEntry = false;

    public function getSearch(): string
    {
        return $this->search;
    }

    public function setSearch(string $search)
    {
        $this->search = htmlspecialchars($search);
    }

    /**
     * @return Category|null
     */
    public function getMainCategory()
    {
        return $this->mainCategory;
    }

    public function setMainCategory(Category $mainCategory = null)
    {
        $this->mainCategory = $mainCategory;
    }

    /**
     * @return Category|null
     */
    public function getSubCategory()
    {
        return $this->subCategory;
    }

    public function setSubCategory(Category $subCategory = null)
    {
        $this->subCategory = $subCategory;
    }

    /**
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

    public function setEventBegin(string $eventBegin)
    {
        $this->eventBegin = $eventBegin;
    }

    /**
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

    public function setEventEnd(string $eventEnd)
    {
        $this->eventEnd = $eventEnd;
    }

    /**
     * @return Location|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    public function setLocation(Location $location = null)
    {
        $this->location = $location;
    }

    public function getFreeEntry(): bool
    {
        return $this->freeEntry;
    }

    public function setFreeEntry(bool $freeEntry)
    {
        $this->freeEntry = $freeEntry;
    }
}
