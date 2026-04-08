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
use JWeiland\Events2\Traits\IsValidEventListenerRequestTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
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
 */
#[AsEventListener('events2/restrictAccess')]
final class RestrictAccessEventListener
{
    use IsValidEventListenerRequestTrait;

    private EventRepository $eventRepository;

    private UserRepository $userRepository;

    private ExtensionService $extensionService;

    private FlashMessageService $flashMessageService;

    private Request $request;

    protected const ALLOWED_CONTROLLER_ACTIONS = [
        'Management' => [
            'new',
            'create',
            'edit',
            'update',
            'delete',
            'activate',
            'perform',
            'listMyEvents',
        ],
    ];

    public function __construct(
        EventRepository $eventRepository,
        UserRepository $userRepository,
        ExtensionService $extensionService,
        FlashMessageService $flashMessageService,
    ) {
        $this->eventRepository = $eventRepository;
        $this->userRepository = $userRepository;
        $this->extensionService = $extensionService;
        $this->flashMessageService = $flashMessageService;
    }

    public function __invoke(PreProcessControllerActionEvent $controllerActionEvent): void
    {
        if (!$this->isValidRequest($controllerActionEvent)) {
            return;
        }

        $this->request = $controllerActionEvent->getRequest();

        if ($this->isAccessAllowed($controllerActionEvent)) {
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
        try {
            if ($this->getContext()->getPropertyFromAspect('backend.user', 'isAdmin', false)) {
                return true;
            }
        } catch (AspectNotFoundException $e) {
        }

        if ((int)($controllerActionEvent->getSettings()['userGroup'] ?? 0) === 0) {
            $this->addFlashMessage(LocalizationUtility::translate('userGroupNotConfigured', 'events2'));
            return false;
        }

        $userAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');
        if (!$userAspect->isLoggedIn()) {
            $this->addFlashMessage(LocalizationUtility::translate('pluginNeedsLogin', 'events2'));
            return false;
        }

        if (!in_array((int)($controllerActionEvent->getSettings()['userGroup'] ?? 0), $userAspect->getGroupIds(), true)) {
            $this->addFlashMessage(LocalizationUtility::translate('notAllowedToCreate', 'events2'));
            return false;
        }

        if ((int)$this->userRepository->getFieldFromUser('tx_events2_organizer') === 0) {
            $this->addFlashMessage(LocalizationUtility::translate('missingOrganizerForCreate', 'events2'));
            return false;
        }

        if (
            $controllerActionEvent->getRequest()->hasArgument('event')
            && ($eventUid = (int)$controllerActionEvent->getRequest()->getArgument('event'))
            && ($event = $this->eventRepository->findHiddenObject($eventUid))
            && $event instanceof Event
            && $event->getIsCurrentUserAllowedOrganizer() === false
        ) {
            $this->addFlashMessage(LocalizationUtility::translate('unauthorizedOrganizerForEdit', 'events2'));
            return false;
        }

        return true;
    }

    private function addFlashMessage($messageBody): void
    {
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            (string)$messageBody,
            '',
            ContextualFeedbackSeverity::ERROR,
            true,
        );

        $this->getFlashMessageQueue()->enqueue($flashMessage);
    }

    private function getFlashMessageQueue(?string $identifier = null): FlashMessageQueue
    {
        if ($identifier === null) {
            $pluginNamespace = $this->extensionService->getPluginNamespace(
                $this->request->getControllerExtensionName(),
                $this->request->getPluginName(),
            );
            $identifier = 'extbase.flashmessages.' . $pluginNamespace;
        }

        return $this->flashMessageService->getMessageQueueByIdentifier($identifier);
    }

    private function getContext(): Context
    {
        return GeneralUtility::makeInstance(Context::class);
    }
}
