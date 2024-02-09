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
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Check, if there is an exceptions of type "remove" for specified date
 */
class IsDateMarkedAsCanceledViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public function initializeArguments(): void
    {
        $this->registerArgument('event', Event::class, 'Get the exceptions from event', true);
        $this->registerArgument('date', \DateTimeImmutable::class, 'Get the exceptions from event to this specific date', true);
    }

    /**
     * Check, if there is an exceptions of type "remove" for specified date
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): bool {
        /** @var Event $event */
        $event = $arguments['event'];
        /** @var \DateTimeImmutable $date */
        $date = $arguments['date'];

        return (bool)$event->getExceptionsForDate($date, 'remove')->count();
    }
}
