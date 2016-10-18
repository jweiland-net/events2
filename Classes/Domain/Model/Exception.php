<?php

namespace JWeiland\Events2\Domain\Model;
    
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
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
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
    protected $exceptionDate = null;

    /**
     * ExceptionTime.
     *
     * @var \JWeiland\Events2\Domain\Model\Time
     */
    protected $exceptionTime = null;

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
     *
     * @return \DateTime
     */
    public function getExceptionDate()
    {
        return $this->exceptionDate;
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
     * @return \JWeiland\Events2\Domain\Model\Time $time
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
