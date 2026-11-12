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
use TYPO3\CMS\Core\Utility\MathUtility;
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
 * - "confirmActivation": Backend administrators or a valid "moderationToken"
 * - "activate": A valid "moderationToken" only
 * - "listMyEvents" and "perform": See ManagementAccessService::getDenialReason()
 */
#[AsEventListener('events2/restrictAccess')]
final readonly class RestrictAccessEventListener
{
    use IsValidEventListenerRequestTrait;

    private const ALLOWED_CONTROLLER_ACTIONS = [
        'Management' => [
            'listMyEvents',
            'perform',
            'confirmActivation',
            'activate',
        ],
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

        $request = $controllerActionEvent->getRequest();
        $denialReason = $this->getDenialReason($request, $controllerActionEvent->getActionName());
        if ($denialReason === '') {
            return;
        }

        $this->addFlashMessage($request, $denialReason);

        // Forward to errorAction and reset all Extbase arguments to be NOT processed
        $controllerActionEvent->setRequest($request->withControllerActionName('error'));
        $controllerActionEvent->setArguments(GeneralUtility::makeInstance(Arguments::class));
    }

    private function getDenialReason(Request $request, string $actionName): string
    {
        $eventArgument = $this->getScalarArgument($request, 'event');
        $eventUid = MathUtility::canBeInterpretedAsInteger($eventArgument) ? (int)$eventArgument : 0;
        $moderationToken = $this->getScalarArgument($request, 'moderationToken');

        $isAllowed = match ($actionName) {
            'confirmActivation' => $this->managementAccessService->canConfirmActivation($eventUid, $moderationToken),
            'activate' => $this->managementAccessService->canActivateEvent($eventUid, $moderationToken),
            default => null,
        };

        return match ($isAllowed) {
            true => '',
            false => 'invalidModerationToken',
            null => $this->managementAccessService->getDenialReason($request),
        };
    }

    /**
     * Only scalar values are accepted, so no property of the event can be
     * mapped by submitting an array with "__identity".
     */
    private function getScalarArgument(Request $request, string $argumentName): string
    {
        $value = $request->hasArgument($argumentName) ? $request->getArgument($argumentName) : '';

        return is_scalar($value) ? (string)$value : '';
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
