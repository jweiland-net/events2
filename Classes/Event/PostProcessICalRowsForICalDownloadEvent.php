<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Event;

use JWeiland\Events2\Domain\Model\Day;

/*
 * Use this event, if you want to modify the event before adding it to the iCal download
 */
class PostProcessICalRowsForICalDownloadEvent
{
    protected array $event = [];

    protected Day $day;

    public function __construct(
        array $event,
        Day $day
    ) {
        $this->event = $event;
        $this->day = $day;
    }

    public function getEvent(): array
    {
        return $this->event;
    }

    public function setEvent(array $event): void
    {
        $this->event = $event;
    }

    public function getDay(): Day
    {
        return $this->day;
    }
}
