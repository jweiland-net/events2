<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\ViewHelpers;

use JWeiland\Events2\Service\ManagementAccessService;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Returns the "moderationToken" for the activation link of an event.
 * It is needed in templates without a controller, which could assign the
 * token, e.g. the mail template of the EmailToReceiver finisher of EXT:form.
 *
 * Example:
 * <f:variable name="moderationToken" value="{e2:moderationToken(event: event)}"/>
 */
final class ModerationTokenViewHelper extends AbstractViewHelper
{
    public function __construct(
        private readonly ManagementAccessService $managementAccessService,
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument(
            'event',
            'mixed',
            'The event object or the UID of the event',
            true,
        );
    }

    public function render(): string
    {
        return $this->managementAccessService->generateModerationToken(
            $this->getEventUid($this->arguments['event']),
        );
    }

    private function getEventUid(mixed $event): int
    {
        if ($event instanceof AbstractDomainObject) {
            return (int)$event->getUid();
        }

        return is_numeric($event) ? (int)$event : 0;
    }
}
