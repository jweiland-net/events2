<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Model;

/**
 * This class was used by DateTimeGenerator and represents a DateTime object and the information, if this date is
 * marked as removed or not.
 */
class DateTimeEntry
{
    protected \DateTimeImmutable $date;

    protected bool $isRemovedDate = false;

    public function __construct(\DateTimeImmutable $date, bool $isRemovedDate)
    {
        $this->date = $date;
        $this->isRemovedDate = $isRemovedDate;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function isRemovedDate(): bool
    {
        return $this->isRemovedDate;
    }
}
