<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/*
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

    /**
     * @var array
     */
    protected $storagePids = [];

    /**
     * @var int
     */
    protected $limit = 0;

    public function getSearch(): string
    {
        return $this->search;
    }

    public function setSearch(string $search): void
    {
        $this->search = htmlspecialchars($search);
    }

    public function getMainCategory(): ?Category
    {
        return $this->mainCategory;
    }

    public function setMainCategory(?Category $mainCategory = null): void
    {
        $this->mainCategory = $mainCategory;
    }

    /**
     * @return Category|null
     */
    public function getSubCategory(): ?Category
    {
        return $this->subCategory;
    }

    public function setSubCategory(?Category $subCategory = null): void
    {
        $this->subCategory = $subCategory;
    }

    public function getEventBegin(): ?\DateTime
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

    public function setEventBegin(string $eventBegin): void
    {
        $this->eventBegin = $eventBegin;
    }

    public function getEventEnd(): ?\DateTime
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

    public function setEventEnd(string $eventEnd): void
    {
        $this->eventEnd = $eventEnd;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(Location $location = null): void
    {
        $this->location = $location;
    }

    public function getFreeEntry(): bool
    {
        return $this->freeEntry;
    }

    public function setFreeEntry(bool $freeEntry): void
    {
        $this->freeEntry = $freeEntry;
    }

    public function getStoragePids(): array
    {
        return $this->storagePids;
    }

    public function setStoragePids(array $storagePids): void
    {
        $this->storagePids = $storagePids;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }
}
