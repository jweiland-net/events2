<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Model;

use JWeiland\Events2\Domain\Factory\TimeFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/*
 * This class contains all getter and setters for a Day.
 */
class Day extends AbstractEntity
{
    protected ?\DateTimeImmutable $crdate = null;

    protected ?\DateTimeImmutable $tstamp = null;

    protected bool $hidden = false;

    protected ?\DateTimeImmutable $day = null;

    protected ?\DateTimeImmutable $dayTime = null;

    protected ?\DateTimeImmutable $sortDayTime = null;

    protected ?\DateTimeImmutable $sameDayTime = null;

    protected bool $isRemovedDate = false;

    protected ?Event $event = null;

    public function getCrdate(): ?\DateTimeImmutable
    {
        return $this->crdate;
    }

    public function setCrdate(?\DateTimeImmutable $crdate = null): void
    {
        $this->crdate = $crdate;
    }

    public function getTstamp(): ?\DateTimeImmutable
    {
        return $this->tstamp;
    }

    public function setTstamp(?\DateTimeImmutable $tstamp = null): void
    {
        $this->tstamp = $tstamp;
    }

    public function getHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    public function getDay(): \DateTimeImmutable
    {
        // Since PHP 7.4 we can not access timezone_type directly anymore.
        // If location is false, timezone_type is 1 or 2, but we need 3
        $day = $this->day;
        if ($this->day->getTimezone()->getLocation() === false) {
            $day = $this->day->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }

        return $day;
    }

    public function setDay(\DateTimeImmutable $day): void
    {
        $this->day = $day;
    }

    public function getDayTime(): \DateTimeImmutable
    {
        // Since PHP 7.4 we can not access timezone_type directly anymore.
        // If location is false, timezone_type is 1 or 2, but we need 3
        $dayTime = $this->dayTime;
        if ($this->dayTime->getTimezone()->getLocation() === false) {
            $dayTime = $this->dayTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }

        return $dayTime;
    }

    public function setDayTime(\DateTimeImmutable $dayTime): void
    {
        $this->dayTime = $dayTime;
    }

    public function getSortDayTime(): \DateTimeImmutable
    {
        // Since PHP 7.4 we can not access timezone_type directly anymore.
        // If location is false, timezone_type is 1 or 2, but we need 3
        $sortDayTime = $this->sortDayTime;
        if ($this->sortDayTime->getTimezone()->getLocation() === false) {
            $sortDayTime = $this->sortDayTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }

        return $sortDayTime;
    }

    public function setSortDayTime(\DateTimeImmutable $sortDayTime): void
    {
        $this->sortDayTime = $sortDayTime;
    }

    public function getSameDayTime(): \DateTimeImmutable
    {
        // Since PHP 7.4 we can not access timezone_type directly anymore.
        // If location is false, timezone_type is 1 or 2, but we need 3
        $sameDayTime = $this->sameDayTime;
        if ($this->sameDayTime->getTimezone()->getLocation() === false) {
            $sameDayTime = $this->sameDayTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }

        return $sameDayTime;
    }

    public function setSameDayTime(\DateTimeImmutable $sameDayTime): void
    {
        $this->sameDayTime = $sameDayTime;
    }

    public function getIsRemovedDate(): bool
    {
        return $this->isRemovedDate;
    }

    public function setIsRemovedDate(bool $isRemovedDate): void
    {
        $this->isRemovedDate = $isRemovedDate;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): void
    {
        $this->event = $event;
    }

    public function getDayAsTimestamp(): int
    {
        return (int)$this->getDay()->format('U');
    }

    public function getDayTimeAsTimestamp(): int
    {
        return (int)$this->getDayTime()->format('U');
    }

    public function getSortDayTimeAsTimestamp(): int
    {
        return (int)$this->getSortDayTime()->format('U');
    }

    public function getSameDayTimeAsTimestamp(): int
    {
        return (int)$this->getSameDayTime()->format('U');
    }

    public function getTimes(): \SplObjectStorage
    {
        $timeFactory = GeneralUtility::makeInstance(TimeFactory::class);

        return $timeFactory->getSortedTimesForDate(
            $this->getEvent(),
            $this->getDay()
        );
    }
}
