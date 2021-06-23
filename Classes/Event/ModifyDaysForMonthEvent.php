<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Event;

/*
 * Use this event, if you want to modify or extend the array for possible days in month of event calendar
 */
class ModifyDaysForMonthEvent
{
    /**
     * @var array
     */
    protected $days;

    public function __construct(array $days)
    {
        $this->days = $days;
    }

    public function getDays(): array
    {
        return $this->days;
    }

    public function setDays(array $days): void
    {
        $this->days = $days;
    }
}
