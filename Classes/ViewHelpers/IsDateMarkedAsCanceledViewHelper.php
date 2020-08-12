<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\ViewHelpers;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Service\EventService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/*
 * Check, if there is an exceptions of type "remove" for specified date
 */
class IsDateMarkedAsCanceledViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize all arguments. You need to override this method and call
     * $this->registerArgument(...) inside this method, to register all your arguments.
     */
    public function initializeArguments()
    {
        $this->registerArgument('event', Event::class, 'Get the exceptions from event', true);
        $this->registerArgument('date', \DateTime::class, 'Get the exceptions from event to this specific date', true);
    }

    /**
     * Check, if there is an exceptions of type "remove" for specified date
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
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $eventService = $objectManager->get(EventService::class);

        return (bool)$eventService->getExceptionsForDate(
            $arguments['event'],
            $arguments['date'],
            'remove'
        )->count();
    }
}
