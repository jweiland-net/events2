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
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Check, if there is an exceptions of type "remove" for specified date
 */
final class IsDateMarkedAsCanceledViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument(
            'event',
            Event::class,
            'Get the exceptions from event',
            true
        );
        $this->registerArgument(
            'date',
            \DateTimeImmutable::class,
            'Get the exceptions from event to this specific date',
            true
        );
    }

    /**
     * Check, if there is an exceptions of type "remove" for specified date
     */
    public function render(): bool
    {
        /** @var Event $event */
        $event = $this->arguments['event'];
        /** @var \DateTimeImmutable $date */
        $date = $this->arguments['date'];

        return (bool)$event->getExceptionsForDate($date, 'remove')->count();
    }
}
