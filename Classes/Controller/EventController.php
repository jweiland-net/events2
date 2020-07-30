<?php

declare(strict_types = 1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Search;
use JWeiland\Events2\Property\TypeConverter\UploadMultipleFilesConverter;
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Utility\CacheUtility;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/*
 * The EventController contains actions to search, create, activate, update and delete events.
 * For plain listing of events please visit DayController
 */
class EventController extends AbstractController
{
    /**
     * We have a self-build form based on method GET.
     * That's why we have to manually allow some form-elements.
     */
    public function initializeListSearchResultsAction()
    {
        $this->arguments->getArgument('search')->getPropertyMappingConfiguration()->setTypeConverterOptions(
            PersistentObjectConverter::class,
            [
                PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true,
            ]
        );
        $this->arguments->getArgument('search')->getPropertyMappingConfiguration()->allowAllProperties();
    }

    /**
     * @param Search $search
     */
    public function listSearchResultsAction(Search $search)
    {
        $days = $this->dayRepository->searchEvents($search);
        $this->view->assign('days', $days);
        CacheUtility::addPageCacheTagsByQuery($days->getQuery());
    }

    public function listMyEventsAction()
    {
        $events = $this->eventRepository->findMyEvents();
        $this->view->assign('events', $events);
        CacheUtility::addPageCacheTagsByQuery($events->getQuery());
    }

    public function newAction()
    {
        $this->deleteUploadedFilesOnValidationErrors('event');
        $event = $this->objectManager->get(Event::class);
        $categories = $this->categoryRepository->getCategories(
            $this->settings['selectableCategoriesForNewEvents']
        );

        if (!$categories->count()) {
            $this->addFlashMessage('Dear Admin: You have forgotten to define some allowed categories in plugin configuration');
        }

        $this->view->assign('event', $event);
        $this->view->assign('locations', $this->locationRepository->findAll());
        $this->view->assign('selectableCategories', $categories);
    }

    /**
     * Initialize create action.
     * We need this to create a DateTime-Object with time of midnight.
     */
    public function initializeCreateAction()
    {
        $this->addValidationForVideoLink();
        $this->addOrganizer('event');
        $this->applyLocationAsMandatoryIfNeeded();
        $this->arguments->getArgument('event')
            ->getPropertyMappingConfiguration()
            ->forProperty('eventBegin')
            ->setTypeConverterOption(
                DateTimeConverter::class,
                DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                'd.m.Y'
            );
        $this->arguments->getArgument('event')
            ->getPropertyMappingConfiguration()
            ->forProperty('eventEnd')
            ->setTypeConverterOption(
                DateTimeConverter::class,
                DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                'd.m.Y'
            );

        /** @var UploadMultipleFilesConverter $multipleFilesTypeConverter */
        $multipleFilesTypeConverter = $this->objectManager->get(
            UploadMultipleFilesConverter::class
        );
        $this->arguments->getArgument('event')
            ->getPropertyMappingConfiguration()
            ->forProperty('images')
            ->setTypeConverter($multipleFilesTypeConverter);
    }

    /**
     * @param Event $event
     */
    public function createAction(Event $event)
    {
        $event->setHidden(true);
        $event->setEventType($event->getEventEnd() ? 'duration' : 'single');
        $this->deleteVideoLinkIfEmpty($event);
        $this->eventRepository->add($event);
        $this->persistenceManager->persistAll();
        $this->addDayRelations($event);
        $this->sendMail('create', $event);
        $this->addFlashMessage(LocalizationUtility::translate('eventCreated', 'events2'));
        $this->redirect('list', 'Day');
    }

    /**
     * @param int $event
     */
    public function editAction(int $event)
    {
        $eventObject = $this->eventRepository->findHiddenEntry($event);
        $categories = $this->categoryRepository->getCategories(
            $this->settings['selectableCategoriesForNewEvents']
        );

        if (!$categories->count()) {
            $this->addFlashMessage('Dear Admin: You have forgotten to define some allowed categories in plugin configuration');
        }

        $this->view->assign('event', $eventObject);
        $this->view->assign('locations', $this->locationRepository->findAll());
        $this->view->assign('selectableCategories', $categories);
    }

    /**
     * Initialize update action
     * We need this to create a DateTime-Object with time of midnight.
     */
    public function initializeUpdateAction()
    {
        $this->registerEventFromRequest();
        $this->addValidationForVideoLink();
        $this->applyLocationAsMandatoryIfNeeded();
        $this->arguments->getArgument('event')
            ->getPropertyMappingConfiguration()
            ->forProperty('eventBegin')
            ->setTypeConverterOption(
                DateTimeConverter::class,
                DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                'd.m.Y'
            );
        $this->arguments->getArgument('event')
            ->getPropertyMappingConfiguration()
            ->forProperty('eventEnd')
            ->setTypeConverterOption(
                DateTimeConverter::class,
                DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                'd.m.Y'
            );
        $argument = $this->request->getArgument('event');
        /** @var Event $event */
        $event = $this->eventRepository->findByIdentifier($argument['__identity']);
        /** @var UploadMultipleFilesConverter $multipleFilesTypeConverter */
        $multipleFilesTypeConverter = $this->objectManager->get(UploadMultipleFilesConverter::class);
        $this->arguments->getArgument('event')
            ->getPropertyMappingConfiguration()
            ->forProperty('images')
            ->setTypeConverter($multipleFilesTypeConverter)
            ->setTypeConverterOptions(
                UploadMultipleFilesConverter::class,
                [
                    'IMAGES' => $event->getImages()
                ]
            );
    }

    /**
     * @param Event $event
     */
    public function updateAction(Event $event)
    {
        $isHidden = $event->getHidden();
        $event->setHidden(true);
        $this->deleteVideoLinkIfEmpty($event);
        $this->eventRepository->update($event);
        $this->persistenceManager->persistAll();
        $this->addDayRelations($event);

        // if editor edits this hidden record, mail should not be send
        if (!$isHidden) {
            $this->sendMail('update', $event);
        }
        $this->addFlashMessage(LocalizationUtility::translate('eventUpdated', 'events2'));
        $this->redirect('listMyEvents', 'Event');
    }

    /**
     * @param int $event
     */
    public function deleteAction(int $event)
    {
        $eventObject = $this->eventRepository->findByIdentifier($event);
        $this->eventRepository->remove($eventObject);
        $this->addFlashMessage(LocalizationUtility::translate('eventDeleted', 'events2'));
        $this->redirect('list', 'Day');
    }

    /**
     * @param int $event
     */
    public function activateAction(int $event)
    {
        $eventObject = $this->eventRepository->findHiddenEntry($event);
        $eventObject->setHidden(false);
        $this->eventRepository->update($eventObject);

        // send mail
        $this->view->assign('event', $eventObject);

        $this->mail->setFrom($this->extConf->getEmailFromAddress(), $this->extConf->getEmailFromName());
        $this->mail->setTo($this->extConf->getEmailToAddress(), $this->extConf->getEmailToName());
        $this->mail->setSubject(LocalizationUtility::translate('email.subject.activate', 'events2'));
        if (version_compare(TYPO3_branch, '10.0', '>=')) {
            $this->mail->html($this->view->render());
        } else {
            $this->mail->setBody($this->view->render(), 'text/html');
        }
        $this->mail->send();

        $this->redirect('list', 'Day');
    }

    /**
     * Add day relations to event record.
     *
     * @param Event $event
     */
    protected function addDayRelations(Event $event)
    {
        /** @var DayRelationService $dayRelations */
        $dayRelations = $this->objectManager->get(DayRelationService::class);
        $dayRelations->createDayRelations($event->getUid());
    }

    /**
     * Send email on new/update.
     *
     * @param string $subjectKey
     * @param Event $event
     * @return bool True, if mail was sent
     */
    public function sendMail(string $subjectKey, Event $event): bool
    {
        $this->view->assign('event', $event);

        $this->mail->setFrom($this->extConf->getEmailFromAddress(), $this->extConf->getEmailFromName());
        $this->mail->setTo($this->extConf->getEmailToAddress(), $this->extConf->getEmailToName());
        $this->mail->setSubject(LocalizationUtility::translate('email.subject.' . $subjectKey, 'events2'));
        if (version_compare(TYPO3_branch, '10.0', '>=')) {
            $this->mail->html($this->view->render());
        } else {
            $this->mail->setBody($this->view->render(), 'text/html');
        }

        return (bool)$this->mail->send();
    }
}
