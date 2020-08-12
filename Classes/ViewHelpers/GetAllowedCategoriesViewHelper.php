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
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/*
 * Reduce categories of an event to the allowed ones configured in Plugin FlexForm.
 */
class GetAllowedCategoriesViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize all arguments. You need to override this method and call
     * $this->registerArgument(...) inside this method, to register all your arguments.
     */
    public function initializeArguments()
    {
        $this->registerArgument('event', Event::class, 'The Event from where we should extract the categories', true);
        $this->registerArgument('pluginCategories', 'string', 'A comma separated list of categories. Normally we use the categories from plugin FlexForm', false, '');
    }

    /**
     * Get only categories from event which are allowed by plugin configuration
     * This is the case, if an event has multiple assigned categories
     *
     * @param array $arguments
     * @param \Closure $childClosure
     * @param RenderingContextInterface $renderingContext
     * @return array
     */
    public static function renderStatic(
        array $arguments,
        \Closure $childClosure,
        RenderingContextInterface $renderingContext
    ) {
        $allowedCategories = [];
        $pluginCategories = GeneralUtility::intExplode(',', trim($arguments['pluginCategories']), true);
        foreach ($pluginCategories as $pluginCategory) {
            /** @var Category $eventCategory */
            foreach ($arguments['event']->getCategories() as $eventCategory) {
                if ($pluginCategory === $eventCategory->getUid()) {
                    $allowedCategories[] = $eventCategory;
                }
            }
        }
        return $allowedCategories;
    }
}
