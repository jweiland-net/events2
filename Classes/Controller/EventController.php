<?php

namespace JWeiland\Events2\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Search;
use JWeiland\Events2\Service\DayRelationService;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class EventController extends AbstractController
{
    /**
     * we have a self-build form based on method GET.
     * That's why we have to manually allow some form-elements.
     *
     * @return void
     */
    public function initializeListSearchResultsAction()
    {
        $this->arguments->getArgument('search')->getPropertyMappingConfiguration()->setTypeConverterOptions(
            'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\PersistentObjectConverter',
            array(
                PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true,
            )
        );
        $this->arguments->getArgument('search')->getPropertyMappingConfiguration()->allowAllProperties();
    }

    /**
     * action list search results.
     *
     * @param Search $search
     *
     * @return void
     */
    public function listSearchResultsAction(Search $search)
    {
        $days = $this->dayRepository->searchEvents($search);

        $this->view->assign('days', $days);
    }

    /**
     * action list my events.
     *
     * @return void
     */
    public function listMyEventsAction()
    {
        $events = $this->eventRepository->findMyEvents();
        $this->view->assign('events', $events);
    }

    /**
     * action new.
     *
     * @return void
     */
    public function newAction()
    {
        $this->deleteUploadedFilesOnValidationErrors('event');
        $event = $this->objectManager->get('JWeiland\\Events2\\Domain\\Model\\Event');
        $this->view->assign('event', $event);
        $this->view->assign('locations', $this->locationRepository->findAll());
        $this->view->assign(
            'selectableCategories',
            $this->categoryRepository->getCategories(
                $this->settings['selectableCategoriesForNewEvents']
            )
        );
    }

    /**
     * initialize create action
     * We need this to create a DateTime-Object with time of midnight.
     *
     * @return void
     */
    public function initializeCreateAction()
    {
        $this->addValidationForVideoLink();
        $this->addOrganizer('event');
        $this->arguments->getArgument('event')
            ->getPropertyMappingConfiguration()
            ->forProperty('eventBegin')
            ->setTypeConverterOption(
                'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\DateTimeConverter',
                DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                'd.m.Y'
            );
        $this->arguments->getArgument('event')
            ->getPropertyMappingConfiguration()
            ->forProperty('eventEnd')
            ->setTypeConverterOption(
                'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\DateTimeConverter',
                DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                'd.m.Y'
            );

        /** @var \JWeiland\Events2\Property\TypeConverter\UploadMultipleFilesConverter $multipleFilesTypeConverter */
        $multipleFilesTypeConverter = $this->objectManager->get(
            'JWeiland\\Events2\\Property\\TypeConverter\\UploadMultipleFilesConverter'
        );
        $this->arguments->getArgument('event')
            ->getPropertyMappingConfiguration()
            ->forProperty('images')
            ->setTypeConverter($multipleFilesTypeConverter);
    }

    /**
     * action create.
     *
     * @param Event $event
     *
     * @return void
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
     * action edit.
     *
     * @param int $event
     *
     * @return void
     */
    public function editAction($event)
    {
        $eventObject = $this->eventRepository->findHiddenEntryByUid((int)$event);
        $this->view->assign('event', $eventObject);
        $this->view->assign('locations', $this->locationRepository->findAll());
        $this->view->assign(
            'selectableCategories',
            $this->categoryRepository->getCategories(
                $this->settings['selectableCategoriesForNewEvents']
            )
        );
    }

    /**
     * initialize update action
     * We need this to create a DateTime-Object with time of midnight.
     *
     * @return void
     */
    public function initializeUpdateAction()
    {
        $this->registerEventFromRequest();
        $this->addValidationForVideoLink();
        $this->arguments->getArgument('event')
            ->getPropertyMappingConfiguration()
            ->forProperty('eventBegin')
            ->setTypeConverterOption(
                'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\DateTimeConverter',
                DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                'd.m.Y'
            );
        $this->arguments->getArgument('event')
            ->getPropertyMappingConfiguration()
            ->forProperty('eventEnd')
            ->setTypeConverterOption(
                'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\DateTimeConverter',
                DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                'd.m.Y'
            );
        $argument = $this->request->getArgument('event');
        /** @var \JWeiland\Events2\Domain\Model\Event $event */
        $event = $this->eventRepository->findByIdentifier($argument['__identity']);
        /** @var \JWeiland\Events2\Property\TypeConverter\UploadMultipleFilesConverter $multipleFilesTypeConverter */
        $multipleFilesTypeConverter = $this->objectManager->get(
            'JWeiland\\Events2\\Property\\TypeConverter\\UploadMultipleFilesConverter'
        );
        $this->arguments->getArgument('event')
            ->getPropertyMappingConfiguration()
            ->forProperty('images')
            ->setTypeConverter($multipleFilesTypeConverter)
            ->setTypeConverterOptions('JWeiland\\Events2\\Property\\TypeConverter\\UploadMultipleFilesConverter',
                array(
                    'IMAGES' => $event->getImages()
                )
            );
    }

    /**
     * action update.
     *
     * @param Event $event
     *
     * @return void
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
     * action delete.
     *
     * @param int $event
     *
     * @return void
     */
    public function deleteAction($event)
    {
        $eventObject = $this->eventRepository->findByIdentifier($event);
        $this->eventRepository->remove($eventObject);
        $this->addFlashMessage(LocalizationUtility::translate('eventDeleted', 'events2'));
        $this->redirect('list', 'Day');
    }

    /**
     * action activate.
     *
     * @param int $event
     *
     * @return void
     */
    public function activateAction($event)
    {
        /** @var \JWeiland\Events2\Domain\Model\Event $eventObject */
        $eventObject = $this->eventRepository->findHiddenEntryByUid((int)$event);
        $eventObject->setHidden(false);
        $this->eventRepository->update($eventObject);

        // send mail
        $this->view->assign('event', $eventObject);

        $this->mail->setFrom($this->extConf->getEmailFromAddress(), $this->extConf->getEmailFromName());
        $this->mail->setTo($this->extConf->getEmailToAddress(), $this->extConf->getEmailToName());
        $this->mail->setSubject(LocalizationUtility::translate('email.subject.activate', 'events2'));
        $this->mail->setBody($this->view->render(), 'text/html');
        $this->mail->send();

        $this->redirect('list', 'Day');
    }

    /**
     * add relations to day records.
     *
     * @param Event $event
     *
     * @return void
     */
    protected function addDayRelations(Event $event)
    {
        /** @var DataHandler $dataHandler */
        $dataHandler = $this->objectManager->get('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
        $dataHandler->start(array(), array()); // keep it empty, everything will be done by a dataHandler hook

        /** @var DayRelationService $dayRelations */
        $dayRelations = $this->objectManager->get('JWeiland\\Events2\\Service\\DayRelationService');
        $dayRelations->createDayRelations($event->getUid(), $dataHandler);

        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();
    }

    /**
     * send email on new/update.
     *
     * @param string $subjectKey
     * @param Event $event
     *
     * @return int The amount of email receivers
     */
    public function sendMail($subjectKey, Event $event)
    {
        /* @var \JWeiland\Events2\Domain\Model\Day $day */
        $this->view->assign('event', $event);

        $this->mail->setFrom($this->extConf->getEmailFromAddress(), $this->extConf->getEmailFromName());
        $this->mail->setTo($this->extConf->getEmailToAddress(), $this->extConf->getEmailToName());
        $this->mail->setSubject(LocalizationUtility::translate('email.subject.' . $subjectKey, 'events2'));
        $this->mail->setBody($this->view->render(), 'text/html');

        return $this->mail->send();
    }
}
