<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\EventListener;

use JWeiland\Events2\Event\PreProcessControllerActionEvent;
use JWeiland\Events2\Service\ManagementAccessService;
use JWeiland\Events2\Traits\IsValidEventListenerRequestTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Restrict access to the actions of the ManagementController. The rules
 * are defined in ManagementAccessService. If access is denied, a localized
 * flash message is added and the request is forwarded to the errorAction
 * without any Extbase argument.
 *
 * - "confirmActivation" and "activate": Backend administrators or a valid
 *   "moderationToken" of the signed mail link.
 * - "listMyEvents": Frontend users of the user group with an organizer or
 *   backend administrators.
 * - "perform" with event: The owner of the event or backend administrators.
 * - "perform" without event creates a new event: Frontend users of the user
 *   group with an organizer only.
 */
#[AsEventListener('events2/restrictAccess')]
final readonly class RestrictAccessEventListener
{
    use IsValidEventListenerRequestTrait;

    private const ALLOWED_CONTROLLER_ACTIONS = [
        'Management' => [
            'confirmActivation',
            'activate',
            'perform',
            'listMyEvents',
        ],
    ];

    /**
     * These actions are allowed with a valid moderation token or for backend administrators only
     */
    private const MODERATION_TOKEN_ACTIONS = [
        'confirmActivation',
        'activate',
    ];

    public function __construct(
        private ManagementAccessService $managementAccessService,
        private ExtensionService $extensionService,
        private FlashMessageService $flashMessageService,
    ) {}

    public function __invoke(PreProcessControllerActionEvent $controllerActionEvent): void
    {
        if (!$this->isValidRequest($controllerActionEvent)) {
            return;
        }

        $denialReason = $this->getDenialReason($controllerActionEvent);
        if ($denialReason === '') {
            return;
        }

        $this->addFlashMessage($controllerActionEvent->getRequest(), $denialReason);

        // Redirect request to errorAction
        $controllerActionEvent->setRequest(
            $controllerActionEvent->getRequest()->withControllerActionName('error'),
        );

        // Reset any Extbase Arguments to be NOT processed
        $controllerActionEvent->setArguments(GeneralUtility::makeInstance(Arguments::class));
    }

    private function getDenialReason(PreProcessControllerActionEvent $controllerActionEvent): string
    {
        $request = $controllerActionEvent->getRequest();
        $actionName = $controllerActionEvent->getActionName();
        $userGroup = (int)($controllerActionEvent->getSettings()['userGroup'] ?? 0);

        return match (true) {
            in_array($actionName, self::MODERATION_TOKEN_ACTIONS, true) => $this->getDenialReasonForActivation($request),
            $actionName === 'listMyEvents' => $this->managementAccessService->getDenialReasonForListing($userGroup),
            $this->getEventUid($request) > 0 => $this->managementAccessService->getDenialReasonForEdit(
                $userGroup,
                $this->getEventUid($request),
            ),
            default => $this->managementAccessService->getDenialReasonForCreate($userGroup),
        };
    }

    /**
     * Only scalar event UIDs are accepted here, so no property of the event
     * can be mapped by submitting an array with "__identity".
     */
    private function getDenialReasonForActivation(Request $request): string
    {
        $eventArgument = $request->hasArgument('event') ? $request->getArgument('event') : null;
        $eventUid = is_scalar($eventArgument) && is_numeric($eventArgument) ? (int)$eventArgument : 0;
        $moderationToken = $request->hasArgument('moderationToken') ? $request->getArgument('moderationToken') : '';

        return $this->managementAccessService->canActivateEvent(
            $eventUid,
            is_string($moderationToken) ? $moderationToken : '',
        ) ? '' : 'invalidModerationToken';
    }

    /**
     * Extbase accepts an object identity as scalar value or as "__identity" of an array
     */
    private function getEventUid(Request $request): int
    {
        $eventArgument = $request->hasArgument('event') ? $request->getArgument('event') : 0;
        if (is_array($eventArgument)) {
            $eventArgument = $eventArgument['__identity'] ?? 0;
        }

        return is_scalar($eventArgument) ? (int)$eventArgument : 0;
    }

    private function addFlashMessage(Request $request, string $labelKey): void
    {
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            (string)LocalizationUtility::translate($labelKey, 'events2'),
            '',
            ContextualFeedbackSeverity::ERROR,
            true,
        );

        $this->getFlashMessageQueue($request)->enqueue($flashMessage);
    }

    private function getFlashMessageQueue(Request $request): FlashMessageQueue
    {
        $pluginNamespace = $this->extensionService->getPluginNamespace(
            $request->getControllerExtensionName(),
            $request->getPluginName(),
        );

        return $this->flashMessageService->getMessageQueueByIdentifier('extbase.flashmessages.' . $pluginNamespace);
    }
}
