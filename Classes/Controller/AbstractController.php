<?php
namespace JWeiland\Events2\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Stefan Froemken <sfroemken@jweiland.net>, jweiland.net
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

/**
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AbstractController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * eventRepository
	 *
	 * @var \JWeiland\Events2\Domain\Repository\EventRepository
	 * @inject
	 */
	protected $eventRepository;

	/**
	 * dayRepository
	 *
	 * @var \JWeiland\Events2\Domain\Repository\DayRepository
	 * @inject
	 */
	protected $dayRepository;

	/**
	 * locationRepository
	 *
	 * @var \JWeiland\Events2\Domain\Repository\LocationRepository
	 * @inject
	 */
	protected $locationRepository;





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
		$this->view->assign('eventsOnTopOfList', $this->eventRepository->findTopEvents());
	}

	/**
	 * Converts a date like 17.01.2013 to an array with additional informations for time
	 * DateTimeConverter of extbase can read this array and converts date into DateTime-Object with time set to midnight
	 *
	 * @param string $nameOfArgument
	 * @param string $nameOfDateValue
	 */
	protected function convertDateToArrayInRequest($nameOfArgument, $nameOfDateValue) {
		if ($this->request->hasArgument($nameOfArgument)) {
			$argument = $this->request->getArgument($nameOfArgument);
			$date = array();
			$date['date'] = $argument[$nameOfDateValue];
			$date['hour'] = 0;
			$date['minute'] = 0;
			$date['second'] = 0;
			$argument[$nameOfDateValue] = $date;
			$this->request->setArgument($nameOfArgument, $argument);
		}
	}

	/**
	 * Converts an array of date informations back to a string
	 * This is only needed if websiteuser has entered wrong data in form and the form has to be displayed again.
	 * Only in that case our generated array with convertDateToArrayInRequest has to be converted back to string
	 *
	 * @param string $nameOfArgument
	 * @param string $nameOfDateValue
	 */
	protected function convertDateToStringInRequest($nameOfArgument, $nameOfDateValue) {
		if ($this->request->getOriginalRequest()) {
			$argument = $this->request->getOriginalRequest()->getArgument($nameOfArgument);
			$argument[$nameOfDateValue] = $argument[$nameOfDateValue]['date'];
			$this->request->getOriginalRequest()->setArgument($nameOfArgument, $argument);
		}
	}

	/**
	 * A special action which is called if the originally intended action could
	 * not be called, for example if the arguments were not valid.
	 *
	 * The default implementation sets a flash message, request errors and forwards back
	 * to the originating action. This is suitable for most actions dealing with form input.
	 *
	 * We clear the page cache by default on an error as well, as we need to make sure the
	 * data is re-evaluated when the user changes something.
	 *
	 * @return string
	 */
	protected function errorAction() {
		$this->clearCacheOnError();
		/** @var \TYPO3\CMS\Extbase\Mvc\Controller\Argument $argument */
		$preparedArguments = array();
		foreach ($this->arguments as $argument) {
			$preparedArguments[$argument->getName()] = $argument->getValue();
		}
		$errorFlashMessage = $this->getErrorFlashMessage();
		if ($errorFlashMessage !== FALSE) {
			$errorFlashMessageObject = new \TYPO3\CMS\Core\Messaging\FlashMessage(
				$errorFlashMessage,
				'',
				\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
			);
			$this->controllerContext->getFlashMessageQueue()->enqueue($errorFlashMessageObject);
		}
		$referringRequest = $this->request->getReferringRequest();
		if ($referringRequest !== NULL) {
			$originalRequest = clone $this->request;
			$this->request->setOriginalRequest($originalRequest);
			$this->request->setOriginalRequestMappingResults($this->arguments->getValidationResults());
			$this->forward($referringRequest->getControllerActionName(), $referringRequest->getControllerName(), $referringRequest->getControllerExtensionName(), $preparedArguments);
		}
		$message = 'An error occurred while trying to call ' . get_class($this) . '->' . $this->actionMethodName . '().' . PHP_EOL;
		return $message;
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