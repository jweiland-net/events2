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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;

/**
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SearchController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

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
	 * preprocessing for all actions
	 *
	 * @return void
	 */
	protected function initializeAction() {
		// register foreign argument for search action
		// so tx_events2_events was also available here in tx_events2_search context
		$foreignPluginContext = GeneralUtility::_POST('tx_events2_events');
		if (isset($foreignPluginContext['search'])) {
			$search = $foreignPluginContext['search'];
			if (is_array($search) && count($search)) {
				$this->request->setArgument('search', $search);
			}
		}
	}

	/**
	 * preprocessing of view for all actions
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function initializeView() {
		if (empty($this->settings['rootCategory'])) {
			throw new \Exception('You have forgotten to set a root category in constants editor', 1369388423);
		}

		$allowedMainCategories = $this->categoryRepository->getSelectedCategories($this->settings['mainCategories'], $this->settings['rootCategory']);

		$data = array();
		$data['categories']['main'] = $allowedMainCategories;
		$data['categories']['sub'] = array();
		$data['locations'] = $this->locationRepository->findAll();
		$data['siteUrl'] = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
		$data['siteId'] = $GLOBALS['TSFE']->id;

		$this->view->assign('data', $data);
	}

	/**
	 * we have a self-build form.
	 * That's why we have to manually allow some form-elements.
	 *
	 * @return void
	 */
	public function initializeShowAction() {
		$this->arguments->getArgument('search')->getPropertyMappingConfiguration()->setTypeConverterOptions(
			'TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\PersistentObjectConverter',
			array(
				PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE
			)
		);
		$this->arguments->getArgument('search')->getPropertyMappingConfiguration()->allowAllProperties();
	}

	/**
	 * action show
	 *
	 * @param \JWeiland\Events2\Domain\Model\Search $search
	 * @return void
	 */
	public function showAction(\JWeiland\Events2\Domain\Model\Search $search = NULL) {
		// Because of the checkbox we have to create a new empty domain model
		if ($search === NULL) {
			$search = $this->objectManager->get('JWeiland\\Events2\\Domain\\Model\\Search');
		}
		$this->view->assign('search', $search);
	}

}