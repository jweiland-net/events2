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
 * Use this event if you want to modify or extend the array for possible days in month of event calendar
 */
class ModifyDaysForMonthEvent
{
    protected array $days;

    public function __construct(array $days)
    {
        $this->days = $days;
    }

    /**
     * @return array[]
     */
    public function getDays(): array
    {
        return $this->days;
    }

    /**
     * @param $days array[]
     */
    public function setDays(array $days): void
    {
        $this->days = $days;
    }
}
