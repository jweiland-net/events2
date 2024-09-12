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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Restrict access to controller actions, if current logged-in user tries to access records of other users.
 * We will remove the event argument from request which will result in calling the errorAction of the ActionController.
 */
class RestrictAccessEventListener extends AbstractControllerEventListener
{
    protected EventRepository $eventRepository;

    protected UserRepository $userRepository;

    protected ExtensionService $extensionService;

    protected FlashMessageService $flashMessageService;

    protected Request $request;

    protected array $allowedControllerActions = [
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
        FlashMessageService $flashMessageService
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
            $controllerActionEvent->getRequest()->withControllerActionName('error')
        );

        // Reset any Extbase Arguments to be NOT processed
        $controllerActionEvent->setArguments(GeneralUtility::makeInstance(Arguments::class));
    }

    protected function isAccessAllowed(PreProcessControllerActionEvent $controllerActionEvent): bool
    {
        try {
            if ($this->getContext()->getPropertyFromAspect('backend.user', 'isAdmin', false)) {
                return true;
            }
        } catch (AspectNotFoundException $e) {
        }

        if ((int)($controllerActionEvent->getSettings()['userGroup'] ?? 0) === 0) {
            $this->addFlashMessage(LocalizationUtility::translate('notAllowedToCreate', 'events2'));
            return false;
        }

        if ($this->userRepository->getFieldFromUser('tx_events2_organizer') === '') {
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

    protected function addFlashMessage($messageBody): void
    {
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            (string)$messageBody,
            '',
            AbstractMessage::ERROR,
            true
        );

        $this->getFlashMessageQueue()->enqueue($flashMessage);
    }

    protected function getFlashMessageQueue(string $identifier = null): FlashMessageQueue
    {
        if ($identifier === null) {
            $pluginNamespace = $this->extensionService->getPluginNamespace(
                $this->request->getControllerExtensionName(),
                $this->request->getPluginName()
            );
            $identifier = 'extbase.flashmessages.' . $pluginNamespace;
        }

        return $this->flashMessageService->getMessageQueueByIdentifier($identifier);
    }

    protected function getContext(): Context
    {
        return GeneralUtility::makeInstance(Context::class);
    }
}
