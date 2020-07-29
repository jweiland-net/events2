<?php

declare(strict_types = 1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/*
 * This class contains all getter and setters for a Day.
 */
class Day extends AbstractEntity
{
    /**
     * @var \DateTime
     */
    protected $crdate;

    /**
     * @var \DateTime
     */
    protected $tstamp;

    /**
     * @var bool
     */
    protected $hidden = false;

    /**
     * @var int
     */
    protected $cruserId = 0;

    /**
     * @var \DateTime
     */
    protected $day;

    /**
     * @var \DateTime
     */
    protected $dayTime;

    /**
     * @var \DateTime
     */
    protected $sortDayTime;

    /**
     * @var \DateTime
     */
    protected $sameDayTime;

    /**
     * @var \JWeiland\Events2\Domain\Model\Event
     */
    protected $event;

    /**
     * @return \DateTime|null
     */
    public function getCrdate()
    {
        return $this->crdate;
    }

    public function setCrdate(\DateTime $crdate = null)
    {
        $this->crdate = $crdate;
    }

    /**
     * @return \DateTime|null
     */
    public function getTstamp()
    {
        return $this->tstamp;
    }

    public function setTstamp(\DateTime $tstamp = null)
    {
        $this->tstamp = $tstamp;
    }

    public function getHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden)
    {
        $this->hidden = $hidden;
    }

    public function getCruserId(): int
    {
        return $this->cruserId;
    }

    public function setCruserId(int $cruserId)
    {
        $this->cruserId = $cruserId;
    }

    public function getDay(): \DateTime
    {
        if ($this->day->timezone_type !== 3) {
            $this->day->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }
        return clone $this->day;
    }

    public function setDay(\DateTime $day)
    {
        $this->day = $day;
    }

    public function getDayTime(): \DateTime
    {
        if ($this->dayTime->timezone_type !== 3) {
            $this->dayTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }
        return clone $this->dayTime;
    }

    public function setDayTime(\DateTime $dayTime)
    {
        $this->dayTime = $dayTime;
    }

    public function getSortDayTime(): \DateTime
    {
        if ($this->sortDayTime->timezone_type !== 3) {
            $this->sortDayTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }
        return clone $this->sortDayTime;
    }

    public function setSortDayTime(\DateTime $sortDayTime)
    {
        $this->sortDayTime = $sortDayTime;
    }

    public function getSameDayTime(): \DateTime
    {
        if ($this->sameDayTime->timezone_type !== 3) {
            $this->sameDayTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }
        return clone $this->sameDayTime;
    }

    public function setSameDayTime(\DateTime $sameDayTime)
    {
        $this->sameDayTime = $sameDayTime;
    }

    /**
     * @return Event|null
     */
    public function getEvent()
    {
        return $this->event;
    }

    public function setEvent(Event $event = null)
    {
        $this->event = $event;
    }
}
