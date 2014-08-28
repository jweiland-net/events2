<?php
namespace JWeiland\Events2\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Stefan Froemken <projects@jweiland.net>, jweiland.net
 *  
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use JWeiland\Events2\Domain\Model\Day;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class EventController extends AbstractController {

	/**
	 * action list
	 *
	 * @return void
	 */
	public function listAction() {
		if (empty($this->settings['categories'])) {
			// find all next events incl. recurring events
			$events = $this->eventRepository->findNextEvents();
		} else {
			// if category is equal to mergeEventsOnCategory, merge duplicated events to ONE event
			$events = $this->eventRepository->getEventsForCategories('list', $this->settings['categories'], $this->settings['mergeEvents']);
		}
		$this->view->assign('events', $events);
	}

	/**
	 * action list latest
	 *
	 * @return void
	 */
	public function listLatestAction() {
		if (empty($this->settings['categories'])) {
			$events = $this->eventRepository->findNextEvents();
		} else {
			$events = $this->eventRepository->getEventsForCategories('latest', $this->settings['categories'], $this->settings['mergeEvents']);
		}
		$this->view->assign('events', $events);
	}

	/**
	 * action list today
	 *
	 * @return void
	 */
	public function listTodayAction() {
		if (empty($this->settings['categories'])) {
			$events = $this->eventRepository->findTodayEvents();
		} else {
			$events = $this->eventRepository->getEventsForCategories('today', $this->settings['categories'], $this->settings['mergeEvents']);
		}
		$this->view->assign('events', $events);
	}

	/**
	 * action list range
	 *
	 * @return void
	 */
	public function listRangeAction() {
		if (empty($this->settings['categories'])) {
			$events = $this->eventRepository->findRangeEvents();
		} else {
			$events = $this->eventRepository->getEventsForCategories('range', $this->settings['categories'], $this->settings['mergeEvents']);
		}
		$this->view->assign('events', $events);
	}

	/**
	 * we have a self-build form based on method GET.
	 * That's why we have to manually allow some form-elements.
	 *
	 * @return void
	 */
	public function initializeListSearchResultsAction() {
		$this->arguments->getArgument('search')->getPropertyMappingConfiguration()->setTypeConverterOptions(
			'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\PersistentObjectConverter',
			array(
				PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE
			)
		);
		$this->arguments->getArgument('search')->getPropertyMappingConfiguration()->allowAllProperties();
	}

	/**
	 * action list search results
	 *
	 * @param \JWeiland\Events2\Domain\Model\Search $search
	 * @return void
	 */
	public function listSearchResultsAction(\JWeiland\Events2\Domain\Model\Search $search) {
		$events = $this->eventRepository->searchEvents($search);

		$this->view->assign('events', $events);
	}

	/**
	 * action list my events
	 *
	 * @return void
	 */
	public function listMyEventsAction() {
		$events = $this->eventRepository->findMyEvents($GLOBALS['TSFE']->fe_user->user['uid']);
		$this->view->assign('events', $events);
	}

	/**
	 * action show
	 *
	 * Hint: I call showAction with int instead of DomainModel
	 * to prevent that recursive validators will be called
	 *
	 * @param integer $event
	 * @param integer $day
	 * @throws \Exception
	 * @return void
	 */
	public function showAction($event, $day = 0) {
		/** @var \JWeiland\Events2\Domain\Model\Event $eventObject */
		$eventObject = $this->eventRepository->findByIdentifier($event);
		if (!empty($day)) {
			$dayObject = $this->dayRepository->findByIdentifier($day);
		} else {
			$dayObject = NULL;
		}

		// if a day was given by $_GET add it to the given event
		if ($dayObject instanceof Day) {
			$eventObject->setDay($dayObject);
		} else {
			// try to find next possible day
			$dayObject = $this->dayRepository->getNextDayForEvent($eventObject);
			if ($dayObject instanceof Day) {
				$eventObject->setDay($dayObject);
			} else {
				// try to find the last day of this event
				$dayObject = $this->dayRepository->getLastDayForEvent($eventObject);
				if ($dayObject instanceof Day) {
					$eventObject->setDay($dayObject);
				} else {
					throw new \Exception('There is no day object for this event defined in url.', 1377600007);
				}
			}
		}
		$this->view->assign('event', $eventObject);
	}

	/**
	 * action new
	 *
	 * @return void
	 */
	public function newAction() {
		$this->deleteUploadedFilesOnValidationErrors('event');
		$event = $this->objectManager->get('JWeiland\\Events2\\Domain\\Model\\Event');
		$this->view->assign('event', $event);
		$this->view->assign('locations', $this->locationRepository->findAll());
		$this->view->assign('selectableCategories', $this->categoryRepository->getCategories($this->settings['selectableCategoriesForNewEvents']));
	}

	/**
	 * initialize create action
	 * We need this to create a DateTime-Object with time of midnight
	 *
	 * @return void
	 */
	public function initializeCreateAction() {
		$this->addValidationForVideoLink('event');
		$this->addOrganizer('event');
		$this->arguments->getArgument('event')->getPropertyMappingConfiguration()->forProperty('eventBegin')->setTypeConverterOption('TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\DateTimeConverter', DateTimeConverter::CONFIGURATION_DATE_FORMAT, 'd.m.Y');
		$this->arguments->getArgument('event')->getPropertyMappingConfiguration()->forProperty('eventEnd')->setTypeConverterOption('TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\DateTimeConverter', DateTimeConverter::CONFIGURATION_DATE_FORMAT, 'd.m.Y');

		/** @var \JWeiland\Events2\Property\TypeConverter\UploadMultipleFilesConverter $multipleFilesTypeConverter */
		$multipleFilesTypeConverter = $this->objectManager->get('JWeiland\\Events2\\Property\\TypeConverter\\UploadMultipleFilesConverter');
		$this->arguments->getArgument('event')->getPropertyMappingConfiguration()->forProperty('images')->setTypeConverter($multipleFilesTypeConverter)->setTypeConverterOption('JWeiland\\Events2\\Property\\TypeConverter\\UploadMultipleFilesConverter', 'TABLENAME', 'tx_events2_domain_model_event');
	}

	/**
	 * action create
	 *
	 * @param \JWeiland\Events2\Domain\Model\Event $event
	 * @return void
	 */
	public function createAction(\JWeiland\Events2\Domain\Model\Event $event) {
		// all user created records have to be hidden
		$event->setHidden(TRUE);
		$this->eventRepository->add($event);
		$this->addFlashMessage(LocalizationUtility::translate('eventCreated', 'events2'));
		$this->redirect('list');
	}

	/**
	 * action edit
	 *
	 * @param integer $event
	 * @return void
	 */
	public function editAction($event) {
		$eventObject = $this->eventRepository->findByIdentifier($event);
		$this->view->assign('event', $eventObject);
		$this->view->assign('locations', $this->locationRepository->findAll());
		$this->view->assign('selectableCategories', $this->categoryRepository->getCategories($this->settings['selectableCategoriesForNewEvents']));
	}

	/**
	 * initialize update action
	 * We need this to create a DateTime-Object with time of midnight
	 *
	 * @return void
	 */
	public function initializeUpdateAction() {
		$this->addValidationForVideoLink('event');
		$this->arguments->getArgument('event')->getPropertyMappingConfiguration()->forProperty('eventBegin')->setTypeConverterOption('TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\DateTimeConverter', DateTimeConverter::CONFIGURATION_DATE_FORMAT, 'd.m.Y');
		$this->arguments->getArgument('event')->getPropertyMappingConfiguration()->forProperty('eventEnd')->setTypeConverterOption('TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\DateTimeConverter', DateTimeConverter::CONFIGURATION_DATE_FORMAT, 'd.m.Y');
		$argument = $this->request->getArgument('event');
		/** @var \JWeiland\Events2\Domain\Model\Event $event */
		$event = $this->eventRepository->findByIdentifier($argument['__identity']);
		/** @var \JWeiland\Events2\Property\TypeConverter\UploadMultipleFilesConverter $multipleFilesTypeConverter */
		$multipleFilesTypeConverter = $this->objectManager->get('JWeiland\\Events2\\Property\\TypeConverter\\UploadMultipleFilesConverter');
		$this->arguments->getArgument('event')
			->getPropertyMappingConfiguration()
			->forProperty('images')
			->setTypeConverter($multipleFilesTypeConverter)
			->setTypeConverterOptions('JWeiland\\Events2\\Property\\TypeConverter\\UploadMultipleFilesConverter', array(
				'TABLENAME' => 'tx_events2_domain_model_event',
				'IMAGES' => $event->getImages()
			));
	}

	/**
	 * action update
	 *
	 * @param \JWeiland\Events2\Domain\Model\Event $event
	 * @return void
	 */
	public function updateAction(\JWeiland\Events2\Domain\Model\Event $event) {
		// all user modified records have to be hidden
		$event->setHidden(TRUE);
		$this->eventRepository->update($event);
		$this->addFlashMessage(LocalizationUtility::translate('eventUpdated', 'events2'));
		$this->redirect('listMyEvents');
	}

	/**
	 * action delete
	 *
	 * @param integer $event
	 * @return void
	 */
	public function deleteAction($event) {
		$eventObject = $this->eventRepository->findByIdentifier($event);
		$this->eventRepository->remove($eventObject);
		$this->addFlashMessage(LocalizationUtility::translate('eventDeleted', 'events2'));
		$this->redirect('list');
	}

}