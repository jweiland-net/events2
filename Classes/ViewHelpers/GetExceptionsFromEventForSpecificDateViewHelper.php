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
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Get exceptions from an event to a specific date
 */
final class GetExceptionsFromEventForSpecificDateViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public function initializeArguments(): void
    {
        $this->registerArgument('event', Event::class, 'Get the exceptions from event', true);
        $this->registerArgument('date', \DateTimeImmutable::class, 'Get the exceptions from event to this specific date', true);
        $this->registerArgument('type', 'string', 'Get exceptions of specified type. remove, add, time or info. You can combine them with comma', false, '');
    }

    /**
     * Get exception from an event to a specific date
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext,
    ): ObjectStorage {
        /** @var Event $event */
        $event = $arguments['event'];
        /** @var \DateTimeImmutable $date */
        $date = $arguments['date'];
        $type = (string)$arguments['type'];

        return $event->getExceptionsForDate($date, $type);
    }
}
