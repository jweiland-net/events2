<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Validator\EventValidator;
use JWeiland\Events2\Traits\InjectCacheServiceTrait;
use JWeiland\Events2\Traits\InjectCategoryRepositoryTrait;
use JWeiland\Events2\Traits\InjectDayRelationServiceTrait;
use JWeiland\Events2\Traits\InjectEventRepositoryTrait;
use JWeiland\Events2\Traits\InjectLocationRepositoryTrait;
use JWeiland\Events2\Traits\InjectMailMessageTrait;
use JWeiland\Events2\Traits\InjectPersistenceManagerTrait;
use JWeiland\Events2\Traits\InjectUserRepositoryTrait;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * The EventController contains actions to search, create, activate, update and delete events.
 * For plain listing of events please visit DayController
 */
class ManagementController extends AbstractController
{
    use InjectCacheServiceTrait;
    use InjectCategoryRepositoryTrait;
    use InjectDayRelationServiceTrait;
    use InjectEventRepositoryTrait;
    use InjectLocationRepositoryTrait;
    use InjectMailMessageTrait;
    use InjectPersistenceManagerTrait;
    use InjectUserRepositoryTrait;

    public function initializeListMyEventsAction(): void
    {
        $this->preProcessControllerAction();
    }

    public function listMyEventsAction(): ResponseInterface
    {
        $events = $this->eventRepository->findMyEvents();
        $this->postProcessAndAssignFluidVariables([
            'events' => $events,
        ]);
        $this->cacheService->addPageCacheTagsByQuery($events->getQuery());

        return $this->htmlResponse();
    }

    public function initializeNewAction(): void
    {
        $this->preProcessControllerAction();
    }

    public function newAction(): ResponseInterface
    {
        if (isset($this->settings['selectableCategoriesForNewEvents'])) {
            trigger_error(
                'settings.selectableCategoriesForNewEvents is deprecated. Please of settings.new.selectableCategoriesForNewEvents instead.',
                E_USER_DEPRECATED
            );
            $selectableCategories = $this->settings['new']['selectableCategoriesForNewEvents'] ?? $this->settings['selectableCategoriesForNewEvents'];
        } else {
            $selectableCategories = $this->settings['new']['selectableCategoriesForNewEvents'];
        }

        $categories = $this->categoryRepository->getCategories($selectableCategories);

        $this->postProcessAndAssignFluidVariables([
            'event' => GeneralUtility::makeInstance(Event::class),
            'locations' => $this->locationRepository->findAll(),
            'selectableCategories' => $categories,
        ]);

        return $this->htmlResponse();
    }

    public function initializeCreateAction(): void
    {
        $this->preProcessControllerAction();
    }

    #[Extbase\Validate(['param' => 'event', 'validator' => EventValidator::class])]
    public function createAction(Event $event): ResponseInterface
    {
        $event->setHidden(true);
        $event->setEventType($event->getEventEnd() !== null ? 'duration' : 'single');
        $this->eventRepository->add($event);
        $this->postProcessControllerAction($event);

        // persistAll must be called before createDayRelations as it creates its own queries based on the event UID
        $this->persistenceManager->persistAll();
        $this->dayRelationService->createDayRelations($event->getUid());

        $this->postProcessAndAssignFluidVariables([
            'event' => $event,
        ]);

        $this->sendMail('create');

        $this->addFlashMessage(LocalizationUtility::translate('eventCreated', 'events2'));

        return $this->redirect('list', 'Day');
    }

    public function initializeEditAction(): void
    {
        $this->preProcessControllerAction();
    }

    #[Extbase\IgnoreValidation(['value' => 'event'])]
    public function editAction(Event $event): ResponseInterface
    {
        $categories = $this->categoryRepository->getCategories(
            $this->settings['selectableCategoriesForNewEvents'],
        );

        if ($categories->count() === 0) {
            $this->addFlashMessage('Dear Admin: You have forgotten to define some allowed categories in plugin configuration');
        }

        $this->postProcessAndAssignFluidVariables([
            'event' => $event,
            'locations' => $this->locationRepository->findAll(),
            'selectableCategories' => $categories,
        ]);

        return $this->htmlResponse();
    }

    public function initializeUpdateAction(): void
    {
        $this->preProcessControllerAction();
    }

    #[Extbase\Validate(['param' => 'event', 'validator' => EventValidator::class])]
    public function updateAction(Event $event): ResponseInterface
    {
        $isHidden = $event->getHidden();
        $event->setHidden(true);
        $this->postProcessControllerAction($event);
        $this->eventRepository->update($event);

        // persistAll must be called before createDayRelations as it creates its own queries based on the event UID
        $this->persistenceManager->persistAll();
        $this->dayRelationService->createDayRelations($event->getUid());

        $this->postProcessAndAssignFluidVariables([
            'event' => $event,
        ]);

        // if editor edits this hidden record, mail should not be sent
        if (!$isHidden) {
            $this->sendMail('update');
        }

        $this->addFlashMessage(LocalizationUtility::translate('eventUpdated', 'events2'));

        return $this->redirect('listMyEvents', 'Management');
    }

    public function initializePerformAction(): void
    {
        $this->preProcessControllerAction();
    }

    public function performAction(?Event $event = null): ResponseInterface
    {
        return $this->htmlResponse();
    }

    public function initializeDeleteAction(): void
    {
        $this->preProcessControllerAction();
    }

    /**
     * We work with event UID here to prevent calling event validators which will perform much better than
     * working with Event model here.
     * As this is not a form request, we can be sure that we will get a valid event from database.
     */
    public function deleteAction(int $event): ResponseInterface
    {
        $eventObject = $this->eventRepository->findByIdentifier($event);
        if ($eventObject instanceof Event) {
            $this->eventRepository->remove($eventObject);
            $this->addFlashMessage(LocalizationUtility::translate('eventDeleted', 'events2'));
        }

        return $this->redirect('list', 'Day');
    }

    public function initializeActivateAction(): void
    {
        $this->preProcessControllerAction();
    }

    /**
     * We work with event UID here to prevent calling event validators which will perform much better than
     * working with Event model here.
     * As this is not a form request, we can be sure that we will get a valid event from database.
     */
    public function activateAction(int $event): ResponseInterface
    {
        $eventObject = $this->eventRepository->findHiddenObject($event);
        if ($eventObject instanceof Event) {
            $eventObject->setHidden(false);
            $this->eventRepository->update($eventObject);

            $this->postProcessAndAssignFluidVariables([
                'event' => $eventObject,
            ]);

            $this->addFlashMessage(LocalizationUtility::translate(
                'email.subject.activate',
                'events2',
            ));

            $this->sendMail('activate');
        }

        return $this->redirect('list', 'Day');
    }

    protected function sendMail(string $subjectKey): bool
    {
        $this->mailMessage->setFrom($this->extConf->getEmailFromAddress(), $this->extConf->getEmailFromName());
        $this->mailMessage->setTo($this->extConf->getEmailToAddress(), $this->extConf->getEmailToName());
        $this->mailMessage->setSubject(
            LocalizationUtility::translate(
                'email.subject.' . $subjectKey,
                'events2',
            ),
        );

        $this->mailMessage->html($this->view->render());

        return $this->mailMessage->send();
    }
}
