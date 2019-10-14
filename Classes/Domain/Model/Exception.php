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
 * This class contains all getter and setters for an Exception.
 */
class Exception extends AbstractEntity
{
    /**
     * @var string
     */
    protected $exceptionType = '';

    /**
     * @var \DateTime
     */
    protected $exceptionDate;

    /**
     * @var \JWeiland\Events2\Domain\Model\Time
     */
    protected $exceptionTime;

    /**
     * @var string
     */
    protected $exceptionDetails = '';

    public function getExceptionType(): string
    {
        return $this->exceptionType;
    }

    public function setExceptionType(string $exceptionType)
    {
        $this->exceptionType = $exceptionType;
    }

    public function getExceptionDate(): \DateTime
    {
        $this->exceptionDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        return clone $this->exceptionDate;
    }

    public function setExceptionDate(\DateTime $exceptionDate)
    {
        $this->exceptionDate = $exceptionDate;
    }

    /**
     * @return Time|null
     */
    public function getExceptionTime()
    {
        return $this->exceptionTime;
    }

    public function setExceptionTime(Time $exceptionTime = null)
    {
        $this->exceptionTime = $exceptionTime;
    }

    public function getExceptionDetails(): string
    {
        return $this->exceptionDetails;
    }

    public function setExceptionDetails(string $exceptionDetails)
    {
        $this->exceptionDetails = $exceptionDetails;
    }
}
