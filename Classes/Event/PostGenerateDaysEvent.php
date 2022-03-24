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
 * Use this event, if you want to add further modifications to the generated days
 */
class PostGenerateDaysEvent
{
    protected array $eventRecord;

    public function __construct(array $eventRecord)
    {
        $this->eventRecord = $eventRecord;
    }

    public function getEventRecord(): array
    {
        return $this->eventRecord;
    }
}
