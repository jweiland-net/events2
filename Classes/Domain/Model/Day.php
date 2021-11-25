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
    /**
     * @var \DateTime|\DateTimeImmutable|null
     */
    protected ?\DateTimeInterface $crdate;

    /**
     * @var \DateTime|\DateTimeImmutable|null
     */
    protected ?\DateTimeInterface $tstamp;

    protected bool $hidden = false;

    protected int $cruserId = 0;

    /**
     * @var \DateTime|\DateTimeImmutable|null
     */
    protected ?\DateTimeInterface $day;

    /**
     * @var \DateTime|\DateTimeImmutable|null
     */
    protected ?\DateTimeInterface $dayTime;

    /**
     * @var \DateTime|\DateTimeImmutable|null
     */
    protected ?\DateTimeInterface $sortDayTime;

    /**
     * @var \DateTime|\DateTimeImmutable|null
     */
    protected ?\DateTimeInterface $sameDayTime;

    protected ?Event $event;

    public function getCrdate(): ?\DateTimeInterface
    {
        return $this->crdate;
    }

    public function setCrdate(?\DateTimeInterface $crdate = null): void
    {
        $this->crdate = $crdate;
    }

    public function getTstamp(): ?\DateTimeInterface
    {
        return $this->tstamp;
    }

    public function setTstamp(?\DateTimeInterface $tstamp = null): void
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

    public function getCruserId(): int
    {
        return $this->cruserId;
    }

    public function setCruserId(int $cruserId): void
    {
        $this->cruserId = $cruserId;
    }

    public function getDay(): \DateTimeInterface
    {
        // Since PHP 7.4 we can not access timezone_type directly anymore.
        // If location is false, timezone_type is 1 or 2, but we need 3
        if ($this->day->getTimezone()->getLocation() === false) {
            $this->day->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }
        return clone $this->day;
    }

    public function setDay(\DateTimeInterface $day): void
    {
        $this->day = $day;
    }

    public function getDayTime(): \DateTimeInterface
    {
        // Since PHP 7.4 we can not access timezone_type directly anymore.
        // If location is false, timezone_type is 1 or 2, but we need 3
        if ($this->dayTime->getTimezone()->getLocation() === false) {
            $this->dayTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }
        return clone $this->dayTime;
    }

    public function setDayTime(\DateTimeInterface $dayTime): void
    {
        $this->dayTime = $dayTime;
    }

    public function getSortDayTime(): \DateTimeInterface
    {
        // Since PHP 7.4 we can not access timezone_type directly anymore.
        // If location is false, timezone_type is 1 or 2, but we need 3
        if ($this->sortDayTime->getTimezone()->getLocation() === false) {
            $this->sortDayTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }
        return clone $this->sortDayTime;
    }

    public function setSortDayTime(\DateTimeInterface $sortDayTime): void
    {
        $this->sortDayTime = $sortDayTime;
    }

    public function getSameDayTime(): \DateTimeInterface
    {
        // Since PHP 7.4 we can not access timezone_type directly anymore.
        // If location is false, timezone_type is 1 or 2, but we need 3
        if ($this->sameDayTime->getTimezone()->getLocation() === false) {
            $this->sameDayTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }
        return clone $this->sameDayTime;
    }

    public function setSameDayTime(\DateTimeInterface $sameDayTime): void
    {
        $this->sameDayTime = $sameDayTime;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event = null): void
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
