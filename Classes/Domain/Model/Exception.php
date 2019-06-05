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
 * This class contains all getter and setters for an Exception.
 */
class Exception extends AbstractEntity
{
    /**
     * ExceptionType.
     *
     * @var string
     */
    protected $exceptionType = '';

    /**
     * ExceptionDate.
     *
     * @var \DateTime
     */
    protected $exceptionDate;

    /**
     * ExceptionTime.
     *
     * @var \JWeiland\Events2\Domain\Model\Time
     */
    protected $exceptionTime;

    /**
     * ExceptionDetails.
     *
     * @var string
     */
    protected $exceptionDetails = '';

    /**
     * Setter for exceptionType.
     *
     * @param string $exceptionType
     */
    public function setExceptionType($exceptionType)
    {
        $this->exceptionType = (string)$exceptionType;
    }

    /**
     * Getter for exceptionType.
     *
     * @return string
     */
    public function getExceptionType()
    {
        return $this->exceptionType;
    }

    /**
     * Setter for exceptionDate.
     *
     * @param \DateTime $exceptionDate
     */
    public function setExceptionDate(\DateTime $exceptionDate)
    {
        $this->exceptionDate = $exceptionDate;
    }

    /**
     * Getter for exceptionDate.
     */
    public function getExceptionDate(): \DateTime
    {
        $this->exceptionDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        return clone $this->exceptionDate;
    }

    /**
     * Getter for exceptionVisibleDate.
     *
     * @return string
     */
    public function getExceptionVisibleDate()
    {
        return $this->exceptionDate->format('d.m.Y');
    }

    /**
     * Returns the exceptionTime.
     *
     * @return \JWeiland\Events2\Domain\Model\Time|null
     */
    public function getExceptionTime()
    {
        return $this->exceptionTime;
    }

    /**
     * Sets the exceptionTime.
     *
     * @param Time $exceptionTime
     */
    public function setExceptionTime(Time $exceptionTime)
    {
        $this->exceptionTime = $exceptionTime;
    }

    /**
     * Setter for exceptionDetails.
     *
     * @param string $exceptionDetails
     */
    public function setExceptionDetails($exceptionDetails)
    {
        $this->exceptionDetails = (string)$exceptionDetails;
    }

    /**
     * Getter for exceptionDetails.
     *
     * @return string
     */
    public function getExceptionDetails()
    {
        return $this->exceptionDetails;
    }
}
