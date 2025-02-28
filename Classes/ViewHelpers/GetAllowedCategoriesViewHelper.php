<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\ViewHelpers;

use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Model\Event;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Reduce categories of an event to the allowed ones configured in Plugin FlexForm.
 */
final class GetAllowedCategoriesViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument(
            'event',
            Event::class,
            'The Event from where we should extract the categories',
            true,
        );
        $this->registerArgument(
            'pluginCategories',
            'string',
            'A comma separated list of categories. Normally we use the categories from plugin FlexForm',
            false,
            '',
        );
    }

    /**
     * Get only categories from event which are allowed by plugin configuration
     * This is the case, if an event has multiple assigned categories
     *
     * @return Category[]
     */
    public function render(): array
    {
        $allowedCategories = [];
        $pluginCategories = GeneralUtility::intExplode(
            ',',
            trim($this->arguments['pluginCategories']),
            true,
        );

        foreach ($pluginCategories as $pluginCategory) {
            /** @var Category $eventCategory */
            foreach ($this->arguments['event']->getCategories() as $eventCategory) {
                if ($pluginCategory === $eventCategory->getUid()) {
                    $allowedCategories[] = $eventCategory;
                }
            }
        }

        return $allowedCategories;
    }
}
