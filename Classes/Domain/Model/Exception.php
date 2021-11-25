<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Model;

use JWeiland\Events2\Domain\Traits\Typo3PropertiesTrait;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/*
 * This class contains all getter and setters for an Exception.
 */
class Exception extends AbstractEntity
{
    use Typo3PropertiesTrait;

    protected string $exceptionType = '';

    protected \DateTimeInterface $exceptionDate;

    protected ?Time $exceptionTime;

    protected string $exceptionDetails = '';

    public function getExceptionType(): string
    {
        return $this->exceptionType;
    }

    public function setExceptionType(string $exceptionType): void
    {
        $this->exceptionType = $exceptionType;
    }

    public function getExceptionDate(): \DateTimeInterface
    {
        $this->exceptionDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        return clone $this->exceptionDate;
    }

    /**
     * @param \DateTime|\DateTimeImmutable $exceptionDate
     */
    public function setExceptionDate(\DateTimeInterface $exceptionDate): void
    {
        $this->exceptionDate = $exceptionDate;
    }

    public function getExceptionTime(): ?Time
    {
        return $this->exceptionTime;
    }

    public function setExceptionTime(?Time $exceptionTime): void
    {
        $this->exceptionTime = $exceptionTime;
    }

    public function getExceptionDetails(): string
    {
        return $this->exceptionDetails;
    }

    public function setExceptionDetails(string $exceptionDetails): void
    {
        $this->exceptionDetails = $exceptionDetails;
    }
}
