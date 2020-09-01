<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\ViewHelpers;

use JWeiland\Events2\Domain\Factory\TimeFactory;
use JWeiland\Events2\Domain\Model\Event;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/*
 * Returns sorted time records for a given event and date
 */
class GetMergedEventTimesViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize all arguments. You need to override this method and call
     * $this->registerArgument(...) inside this method, to register all your arguments.
     */
    public function initializeArguments()
    {
        $this->registerArgument('event', Event::class, 'The event to get the times from', true);
        $this->registerArgument('date', \DateTime::class, 'The date to get the times from', true);
    }

    /**
     * One event can have until 4 relations to time records.
     * This ViewHelpers helps you to find the times with highest priority and merge them into one collection.
     *
     * @param array $arguments
     * @param \Closure $childClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $childClosure,
        RenderingContextInterface $renderingContext
    ) {
        $timeFactory = GeneralUtility::makeInstance(TimeFactory::class);
        return $timeFactory->getSortedTimesForDate(
            $arguments['event'],
            $arguments['date']
        );
    }
}
