<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\EventListener;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Domain\Repository\UserRepository;
use JWeiland\Events2\Event\PreProcessControllerActionEvent;
use JWeiland\Events2\Service\EventModerationService;
use JWeiland\Events2\Traits\IsValidEventListenerRequestTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Context\Context;
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
 * Restrict access to controller actions if the current logged-in user tries to access records of other users.
 * We will remove the event argument from the request, which will result in calling the errorAction of the ActionController.
 *
 * "confirmActivation" and "activate" are allowed for backend administrators
 * and with a valid "moderationToken" of the signed mail link only. Owners
 * of an event are not allowed to activate their own events, as that would
 * bypass the moderation.
 */
#[AsEventListener('events2/restrictAccess')]
final readonly class RestrictAccessEventListener
{
    use IsValidEventListenerRequestTrait;

    private const ALLOWED_CONTROLLER_ACTIONS = [
        'Management' => [
            'new',
            'create',
            'edit',
            'update',
            'delete',
            'confirmActivation',
            'activate',
            'perform',
            'listMyEvents',
        ],
    ];

    /**
     * These actions are allowed with a valid moderation token or for backend administrators only
     */
    private const MODERATION_TOKEN_CONTROLLER_ACTIONS = [
        'Management' => [
            'confirmActivation',
            'activate',
        ],
    ];

    public function __construct(
        private EventRepository $eventRepository,
        private UserRepository $userRepository,
        private EventModerationService $eventModerationService,
        private ExtensionService $extensionService,
        private FlashMessageService $flashMessageService,
        private Context $context,
    ) {}

    public function __invoke(PreProcessControllerActionEvent $controllerActionEvent): void
    {
        if (!$this->isValidRequest($controllerActionEvent) || $this->isAccessAllowed($controllerActionEvent)) {
            return;
        }

        // Redirect request to errorAction
        $controllerActionEvent->setRequest(
            $controllerActionEvent->getRequest()->withControllerActionName('error'),
        );

        // Reset any Extbase Arguments to be NOT processed
        $controllerActionEvent->setArguments(GeneralUtility::makeInstance(Arguments::class));
    }

    private function isAccessAllowed(PreProcessControllerActionEvent $controllerActionEvent): bool
    {
        if ($this->isBackendAdmin()) {
            return true;
        }

        if ($this->isModerationTokenAction($controllerActionEvent)) {
            return $this->hasValidModerationToken($controllerActionEvent->getRequest());
        }

        return $this->isFrontendUserAllowed($controllerActionEvent)
            && $this->isEventOfFrontendUser($controllerActionEvent->getRequest());
    }

    private function isModerationTokenAction(PreProcessControllerActionEvent $controllerActionEvent): bool
    {
        return in_array(
            $controllerActionEvent->getActionName(),
            self::MODERATION_TOKEN_CONTROLLER_ACTIONS[$controllerActionEvent->getControllerName()] ?? [],
            true,
        );
    }

    /**
     * Only scalar event UIDs are accepted here, so no property of the event
     * can be mapped by submitting an array with "__identity".
     */
    private function hasValidModerationToken(Request $request): bool
    {
        $eventArgument = $request->hasArgument('event') ? $request->getArgument('event') : null;
        $eventUid = is_scalar($eventArgument) && is_numeric($eventArgument) ? (int)$eventArgument : 0;

        if ($this->eventModerationService->getValidModerationToken($request, $eventUid) !== '') {
            return true;
        }

        $this->addFlashMessage($request, 'invalidModerationToken');

        return false;
    }

    private function isFrontendUserAllowed(PreProcessControllerActionEvent $controllerActionEvent): bool
    {
        $userGroup = (int)($controllerActionEvent->getSettings()['userGroup'] ?? 0);
        $userAspect = $this->context->getAspect('frontend.user');

        $labelKey = match (true) {
            $userGroup === 0 => 'userGroupNotConfigured',
            $userAspect->get('isLoggedIn') !== true => 'pluginNeedsLogin',
            !in_array($userGroup, $userAspect->get('groupIds'), true) => 'notAllowedToCreate',
            (int)$this->userRepository->getFieldFromUser('tx_events2_organizer') === 0 => 'missingOrganizerForCreate',
            default => '',
        };

        if ($labelKey === '') {
            return true;
        }

        $this->addFlashMessage($controllerActionEvent->getRequest(), $labelKey);

        return false;
    }

    private function isEventOfFrontendUser(Request $request): bool
    {
        $eventUid = $this->getEventUid($request);
        if ($eventUid <= 0) {
            return true;
        }

        $event = $this->eventRepository->findHiddenObject($eventUid);
        if (!$event instanceof Event || $event->getIsCurrentUserAllowedOrganizer()) {
            return true;
        }

        $this->addFlashMessage($request, 'unauthorizedOrganizerForEdit');

        return false;
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

    private function isBackendAdmin(): bool
    {
        return $this->context->getAspect('backend.user')->get('isAdmin') === true;
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
