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

/**
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CultureController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * eventRepository
	 *
	 * @var \JWeiland\Events2\Domain\Repository\EventRepository
	 * @inject
	 */
	protected $eventRepository;

	/**
	 * locationRepository
	 *
	 * @var \JWeiland\Events2\Domain\Repository\LocationRepository
	 * @inject
	 */
	protected $locationRepository;

	/**
	 * categoryRepository
	 *
	 * @var \JWeiland\Events2\Domain\Repository\CategoryRepository
	 * @inject
	 */
	protected $categoryRepository;





	/**
	 * add variables with categories of a predefined parent category
	 * Needed by some selectboxes
	 *
	 * @return void
	 */
	public function initializeView() {
		$this->view->assign(
			'topics',
			$this->categoryRepository->getCategories($this->settings['topics'])
		);
		$this->view->assign(
			'categories',
			$this->categoryRepository->getCategories($this->settings['categories'])
		);
	}

	/**
	 * action list
	 *
	 * @return void
	 */
	public function listAction() {
		$events = $this->eventRepository->findCultureEvents($this->settings['ageGroup']);
		$this->view->assign('events', $events);
	}

	/**
	 * action search
	 *
	 * @param integer $suitabilityUser
	 * @param integer $suitabilityGroups
	 * @param integer $topic
	 * @param integer $category
	 * @return void
	 */
	public function searchAction($suitabilityUser = 0, $suitabilityGroups = 0, $topic = 0, $category = 0) {
		$events = $this->eventRepository->findCultureEvents($this->settings['ageGroup'], $suitabilityUser, $suitabilityGroups, $topic, $category);
		$this->view->assign('suitabilityUser', $suitabilityUser);
		$this->view->assign('suitabilityGroups', $suitabilityGroups);
		$this->view->assign('topic', $topic);
		$this->view->assign('category', $category);
		$this->view->assign('events', $events);
	}

}
