<?php

namespace JWeiland\Events2\ViewHelpers;

/***************************************************************
     *  Copyright notice
     *
     *  (c) 2015 Stefan Froemken <projects@jweiland.net>, jweiland.net
     *
     *  All rights reserved
     *
     *  This script is part of the TYPO3 project. The TYPO3 project is
     *  free software; you can redistribute it and/or modify
     *  it under the terms of the GNU General Public License as published by
     *  the Free Software Foundation; either version 3 of the License, or
     *  (at your option) any later version.
     *
     *  The GNU General Public License can be found at
     *  http://www.gnu.org/copyleft/gpl.html.
     *
     *  This script is distributed in the hope that it will be useful,
     *  but WITHOUT ANY WARRANTY; without even the implied warranty of
     *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     *  GNU General Public License for more details.
     *
     *  This copyright notice MUST APPEAR in all copies of the script!
     ***************************************************************/
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class GetMergedEventTimesViewHelper extends AbstractViewHelper
{
    /**
     * @var \JWeiland\Events2\Utility\EventUtility
     */
    protected $eventUtility = null;

    /**
     * inject Event Utility.
     *
     * @param \JWeiland\Events2\Utility\EventUtility $eventUtility
     */
    public function injectEventUtility(\JWeiland\Events2\Utility\EventUtility $eventUtility)
    {
        $this->eventUtility = $eventUtility;
    }

    /**
     * One event can have until 4 relations to time records.
     * This ViewHelpers helps you to find the times with highest priority and merge them into one collection.
     *
     * @param \JWeiland\Events2\Domain\Model\Event $event
     * @param bool                                 $directReturn If event has only ONE time record defined, you can set this value to TRUE to direct return this time record instead of a SplObjectStorage
     *
     * @return \SplObjectStorage|\JWeiland\Events2\Domain\Model\Time
     */
    public function render(\JWeiland\Events2\Domain\Model\Event $event, $directReturn = false)
    {
        $times = $this->eventUtility->getTimesForDay($event, $event->getDay());
        if ($times->count() === 1 && $directReturn) {
            $times->rewind();

            return $times->current();
        }

        return $times;
    }
}
