<?php

namespace JWeiland\Events2\ViewHelpers;

/*
 * This file is part of the TYPO3 CMS project.
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
