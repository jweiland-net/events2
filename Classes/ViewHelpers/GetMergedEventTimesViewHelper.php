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
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/*
 * Returns sorted time records for a given event and date
 */
class GetMergedEventTimesViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public function initializeArguments(): void
    {
        $this->registerArgument('event', Event::class, 'The event to get the times from', true);
        $this->registerArgument('date', \DateTime::class, 'The date to get the times from', true);
    }

    /**
     * One event can have until 4 relations to time records.
     * This ViewHelpers helps you to find the times with highest priority and merge them into one collection.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return \SplObjectStorage
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): \SplObjectStorage {
        $timeFactory = GeneralUtility::makeInstance(TimeFactory::class);
        return $timeFactory->getSortedTimesForDate(
            $arguments['event'],
            $arguments['date']
        );
    }
}
