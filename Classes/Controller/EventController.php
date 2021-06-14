<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Repository\CategoryRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Domain\Repository\LocationRepository;
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Utility\CacheUtility;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/*
 * The EventController contains actions to search, create, activate, update and delete events.
 * For plain listing of events please visit DayController
 */
class EventController extends AbstractController
{
    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var LocationRepository
     */
    protected $locationRepository;

    /**
     * @var DayRelationService
     */
    protected $dayRelationService;

    /**
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var MailMessage
     */
    protected $mail;

    /**
     * @var ExtConf
     */
    protected $extConf;

    public function __construct(
        EventRepository $eventRepository,
        CategoryRepository $categoryRepository,
        LocationRepository $locationRepository,
        DayRelationService $dayRelationService,
        PersistenceManagerInterface $persistenceManager,
        MailMessage $mail,
        ExtConf $extConf
    ) {
        $this->eventRepository = $eventRepository;
        $this->categoryRepository = $categoryRepository;
        $this->locationRepository = $locationRepository;
        $this->dayRelationService = $dayRelationService;
        $this->persistenceManager = $persistenceManager;
        $this->mail = $mail;
        $this->extConf = $extConf;
    }

    public function initializeListMyEventsAction(): void
    {
        $this->preProcessControllerAction();
    }

    public function listMyEventsAction(): void
    {
        $events = $this->eventRepository->findMyEvents();
        $this->postProcessAndAssignFluidVariables([
            'events' => $events
        ]);
        CacheUtility::addPageCacheTagsByQuery($events->getQuery());
    }

    public function initializeNewAction(): void
    {
        $this->preProcessControllerAction();
    }

    public function newAction(): void
    {
        $categories = $this->categoryRepository->getCategories(
            $this->settings['selectableCategoriesForNewEvents']
        );

        if ($categories->count() === 0) {
            $this->addFlashMessage('Dear Admin: You have forgotten to define some allowed categories in plugin configuration');
        }

        $this->postProcessAndAssignFluidVariables([
            'event' => GeneralUtility::makeInstance(Event::class),
            'locations' => $this->locationRepository->findAll(),
            'selectableCategories' => $categories
        ]);
    }

    public function initializeCreateAction(): void
    {
        $this->preProcessControllerAction();
    }

    /**
     * @param Event $event
     */
    public function createAction(Event $event): void
    {
        $event->setHidden(true);
        $event->setEventType($event->getEventEnd() ? 'duration' : 'single');
        $this->eventRepository->add($event);
        $this->postProcessControllerAction($event);

        // persistAll must be called before createDayRelations as it creates its own queries based on the event UID
        $this->persistenceManager->persistAll();
        $this->dayRelationService->createDayRelations($event->getUid());

        $this->postProcessAndAssignFluidVariables([
            'event' => $event
        ]);

        $this->sendMail('create');
        $this->addFlashMessage(LocalizationUtility::translate('eventCreated', 'events2'));
        $this->redirect('list', 'Day');
    }

    public function initializeEditAction(): void
    {
        $this->preProcessControllerAction();
    }

    /**
     * @param Event $event
     */
    public function editAction(Event $event): void
    {
        $categories = $this->categoryRepository->getCategories(
            $this->settings['selectableCategoriesForNewEvents']
        );

        if (!$categories->count()) {
            $this->addFlashMessage('Dear Admin: You have forgotten to define some allowed categories in plugin configuration');
        }

        $this->postProcessAndAssignFluidVariables([
            'event' => $event,
            'locations' => $this->locationRepository->findAll(),
            'selectableCategories' => $categories
        ]);
    }

    public function initializeUpdateAction(): void
    {
        $this->preProcessControllerAction();
    }

    /**
     * @param Event $event
     */
    public function updateAction(Event $event): void
    {
        $isHidden = $event->getHidden();
        $event->setHidden(true);
        $this->postProcessControllerAction($event);
        $this->eventRepository->update($event);

        // persistAll must be called before createDayRelations as it creates its own queries based on the event UID
        $this->persistenceManager->persistAll();
        $this->dayRelationService->createDayRelations($event->getUid());

        $this->postProcessAndAssignFluidVariables([
            'event' => $event
        ]);

        // if editor edits this hidden record, mail should not be send
        if (!$isHidden) {
            $this->sendMail('update');
        }
        $this->addFlashMessage(LocalizationUtility::translate('eventUpdated', 'events2'));
        $this->redirect('listMyEvents', 'Event');
    }

    public function initializeDeleteAction(): void
    {
        $this->preProcessControllerAction();
    }

    /**
     * We work with event UID here to prevent calling event validators which will perform much better than
     * working with Event model here.
     * As this is not a form request, we can be sure that we will get a valid event from database.
     *
     * @param int $event
     */
    public function deleteAction(int $event): void
    {
        $eventObject = $this->eventRepository->findByIdentifier($event);
        if ($eventObject instanceof Event) {
            $this->eventRepository->remove($eventObject);
            $this->addFlashMessage(LocalizationUtility::translate('eventDeleted', 'events2'));
        }

        $this->redirect('list', 'Day');
    }

    public function initializeActivateAction(): void
    {
        $this->preProcessControllerAction();
    }

    /**
     * We work with event UID here to prevent calling event validators which will perform much better than
     * working with Event model here.
     * As this is not a form request, we can be sure that we will get a valid event from database.
     *
     * @param int $event
     */
    public function activateAction(int $event): void
    {
        $eventObject = $this->eventRepository->findHiddenObject($event);
        if ($eventObject instanceof Event) {
            $eventObject->setHidden(false);
            $this->eventRepository->update($eventObject);

            $this->postProcessAndAssignFluidVariables([
                'event' => $eventObject
            ]);

            $this->sendMail('activate');
        }

        $this->redirect('list', 'Day');
    }

    protected function sendMail(string $subjectKey): bool
    {
        $this->mail->setFrom($this->extConf->getEmailFromAddress(), $this->extConf->getEmailFromName());
        $this->mail->setTo($this->extConf->getEmailToAddress(), $this->extConf->getEmailToName());
        $this->mail->setSubject(
            LocalizationUtility::translate(
                'email.subject.' . $subjectKey, 'events2'
            )
        );

        $this->mail->html($this->view->render());

        return $this->mail->send();
    }
}
