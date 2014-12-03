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
use JWeiland\Events2\Domain\Model\Event;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;

/**
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AbstractController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * eventRepository
	 *
	 * @var \JWeiland\Events2\Domain\Repository\EventRepository
	 */
	protected $eventRepository;

	/**
	 * dayRepository
	 *
	 * @var \JWeiland\Events2\Domain\Repository\DayRepository
	 */
	protected $dayRepository;

	/**
	 * locationRepository
	 *
	 * @var \JWeiland\Events2\Domain\Repository\LocationRepository
	 */
	protected $locationRepository;

	/**
	 * categoryRepository
	 *
	 * @var \JWeiland\Events2\Domain\Repository\CategoryRepository
	 */
	protected $categoryRepository;

	/**
	 * userRepository
	 *
	 * @var \JWeiland\Events2\Domain\Repository\UserRepository
	 */
	protected $userRepository;

	/**
	 * inject event repository
	 *
	 * @param \JWeiland\Events2\Domain\Repository\EventRepository $eventRepository
	 * @return void
	 */
	public function injectEventRepository(\JWeiland\Events2\Domain\Repository\EventRepository $eventRepository) {
		$this->eventRepository = $eventRepository;
	}

	/**
	 * inject day repository
	 *
	 * @param \JWeiland\Events2\Domain\Repository\DayRepository $dayRepository
	 * @return void
	 */
	public function injectDayRepository(\JWeiland\Events2\Domain\Repository\DayRepository $dayRepository) {
		$this->dayRepository = $dayRepository;
	}

	/**
	 * inject location repository
	 *
	 * @param \JWeiland\Events2\Domain\Repository\LocationRepository $locationRepository
	 * @return void
	 */
	public function injectLocationRepository(\JWeiland\Events2\Domain\Repository\LocationRepository $locationRepository) {
		$this->locationRepository = $locationRepository;
	}

	/**
	 * inject category repository
	 *
	 * @param \JWeiland\Events2\Domain\Repository\CategoryRepository $categoryRepository
	 * @return void
	 */
	public function injectCategoryRepository(\JWeiland\Events2\Domain\Repository\CategoryRepository $categoryRepository) {
		$this->categoryRepository = $categoryRepository;
	}

	/**
	 * inject user repository
	 *
	 * @param \JWeiland\Events2\Domain\Repository\UserRepository $userRepository
	 * @return void
	 */
	public function injectUserRepository(\JWeiland\Events2\Domain\Repository\UserRepository $userRepository) {
		$this->userRepository = $userRepository;
	}

	/**
	 * preprocessing of all actions
	 *
	 * @return void
	 */
	public function initializeAction() {
		// if this value was not set, then it will be filled with 0
		// but that is not good, because UriBuilder accepts 0 as pid, so it's better to set it to NULL
		if (empty($this->settings['pidOfDetailPage'])) {
			$this->settings['pidOfDetailPage'] = NULL;
		}
		if (empty($this->settings['pidOfSearchPage'])) {
			$this->settings['pidOfSearchPage'] = NULL;
		}
		if (empty($this->settings['pidOfLocationPage'])) {
			$this->settings['pidOfLocationPage'] = NULL;
		}
		if (empty($this->settings['pidOfListPage'])) {
			$this->settings['pidOfListPage'] = NULL;
		}
	}

	/**
	 * add some global variables/objects to the views
	 *
	 * @return void
	 */
	public function initializeView() {
		$this->view->assign('eventsOnTopOfList', $this->eventRepository->findTopEvents($this->settings['mergeEvents']));
		$this->view->assign('siteUrl', GeneralUtility::getIndpEnv('TYPO3_SITE_URL')); // needed for ajax requests
	}

	/**
	 * files will be uploaded in typeConverter automatically
	 * But, if an error occurs we have to remove them
	 *
	 * @param string $argument
	 * @return void
	 */
	protected function deleteUploadedFilesOnValidationErrors($argument) {
		if ($this->getControllerContext()->getRequest()->hasArgument($argument)) {
			/** @var \JWeiland\Events2\Domain\Model\Event $event */
			$event = $this->getControllerContext()->getRequest()->getArgument($argument);
			if ($event instanceof Event) {
				$images = $event->getImages();
				if (count($images)) {
					/** @var \JWeiland\Events2\Domain\Model\FileReference $image */
					foreach ($images as $image) {
						$image->getOriginalResource()->delete();
					}
				}
			}
		}
	}

	/**
	 * remove videoLink if empty
	 * add special validation for videolink
	 * I can't add this validation to Linkmodel, as such a validation would be also valid for organizer link
	 *
	 * @param string $argument
	 * @return void
	 */
	protected function addValidationForVideoLink($argument) {
		if ($this->request->hasArgument($argument) && !empty($event['videoLink']['link'])) {
			// create a new RegExpValidator for property link
			/** @var \TYPO3\CMS\Extbase\Validation\Validator\RegularExpressionValidator $regExpValidator */
			$regExpValidator = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Validation\\Validator\\RegularExpressionValidator', array(
				'regularExpression' => '~^(|http:|https:)//(|www.)youtube(.*?)(v=|embed/)([a-zA-Z0-9_-]+)~i'
			));
			/** @var \TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator $genericObjectValidator */
			$genericObjectValidator = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Validation\\Validator\\GenericObjectValidator');
			$genericObjectValidator->addPropertyValidator('link', $regExpValidator);

			// modify current validator of event
			$event = $this->arguments->getArgument($argument);
			/** @var \TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator $eventValidator */
			$eventValidator = $event->getValidator();
			$validators = $eventValidator->getValidators();
			$validators->rewind();
			$eventValidator = $validators->current();
			$validators = $eventValidator->getValidators();
			$validators->rewind();
			/** @var \TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator $eventValidator */
			$eventValidator = $validators->current();
			$eventValidator->addPropertyValidator('videoLink', $genericObjectValidator);
		}
	}

	/**
	 * delete videoLink if empty
	 * Extbase can not set deleted=1 itself
	 *
	 * @param \JWeiland\Events2\Domain\Model\Event $event
	 * @return void
	 */
	protected function deleteVideoLinkIfEmpty(Event $event) {
		$linkText = $event->getVideoLink()->getLink();
		if (empty($linkText)) {
			/** @var \JWeiland\Events2\Domain\Repository\LinkRepository $linkRepository */
			$linkRepository = $this->objectManager->get('JWeiland\\Events2\\Domain\\Repository\\LinkRepository');
			$linkRepository->remove($event->getVideoLink());
			$event->setVideoLink(NULL);
		}
	}

	/**
	 * add organizer
	 *
	 * In a HTML-Template you can change the user uid if you want
	 * So it's better to add the organizer here in PHP
	 *
	 * @param $argument
	 * @return boolean
	 */
	protected function addOrganizer($argument) {
		if ($this->request->hasArgument($argument)) {
			$event = $this->request->getArgument($argument);
			if (!isset($event['organizer'])) {
				$organizerOfCurrentUser = (string)$this->userRepository->getFieldFromUser('tx_events2_organizer');
				if (MathUtility::canBeInterpretedAsInteger($organizerOfCurrentUser)) {
					$event['organizer'] = $organizerOfCurrentUser;
					// per default it is not allowed to add new Arguments manually. So we have to register them.
					// allow mapping of organizer
					$this->arguments->getArgument($argument)->getPropertyMappingConfiguration()->allowProperties('organizer');
					// allow creation
					$this->arguments->getArgument($argument)->getPropertyMappingConfiguration()->forProperty('organizer')->setTypeConverterOption(
						'TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter',
						PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
						TRUE
					)->allowProperties('organizer');
					$this->request->setArgument($argument, $event);
				} else {
					return FALSE;
				}
			} else {
				return TRUE;
			}
		} else {
			return FALSE;
		}
	}

}