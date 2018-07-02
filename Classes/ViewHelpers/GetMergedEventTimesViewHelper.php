<?php

namespace JWeiland\Events2\ViewHelpers;

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
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Service\EventService;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class GetMergedEventTimesViewHelper extends AbstractViewHelper
{
    /**
     * @var \JWeiland\Events2\Service\EventService
     */
    protected $eventService;

    /**
     * inject Event Service.
     *
     * @param EventService $eventService
     */
    public function injectEventService(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * One event can have until 4 relations to time records.
     * This ViewHelpers helps you to find the times with highest priority and merge them into one collection.
     *
     * @param Day  $day
     * @param bool $directReturn If event has only ONE time record defined, you can set this value to TRUE to direct return this time record instead of a SplObjectStorage
     *
     * @return \SplObjectStorage|\JWeiland\Events2\Domain\Model\Time
     */
    public function render(Day $day, $directReturn = false)
    {
        $times = $this->eventService->getSortedTimesForDay($day->getEvent(), $day);
        if ($times->count() === 1 && $directReturn) {
            $times->rewind();
            /** @var Time $time */
            $time = $times->current();
            return $time;
        }

        return $times;
    }
}
