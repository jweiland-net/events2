<?php

namespace JWeiland\Events2\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Stefan Froemken <projects@jweiland.net>, jweiland.net
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
use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Model\Event;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class GetAllowedCategoriesViewHelper extends AbstractViewHelper
{
    /**
     * Get only categories from event which are allowed by plugin configuration
     * This is the case, if an event has multiple assigned categories
     *
     * @param Event $event
     * @param string $pluginCategories Comma separated list of int plugin categories
     *
     * @return array
     */
    public function render(Event $event, $pluginCategories = '')
    {
        $allowedCategories = array();
        $pluginCategories = GeneralUtility::intExplode(',', trim($pluginCategories), true);
        foreach ($pluginCategories as $pluginCategory) {
            /** @var Category $eventCategory */
            foreach ($event->getCategories() as $eventCategory) {
                if ($pluginCategory === $eventCategory->getUid()) {
                    $allowedCategories[] = $eventCategory;
                }
            }
        }
        return $allowedCategories;
    }
}
