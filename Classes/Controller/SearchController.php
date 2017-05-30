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
use JWeiland\Events2\Domain\Model\Search;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SearchController extends ActionController
{
    /**
     * eventRepository.
     *
     * @var \JWeiland\Events2\Domain\Repository\EventRepository
     * @inject
     */
    protected $eventRepository;

    /**
     * locationRepository.
     *
     * @var \JWeiland\Events2\Domain\Repository\LocationRepository
     * @inject
     */
    protected $locationRepository;

    /**
     * categoryRepository.
     *
     * @var \JWeiland\Events2\Domain\Repository\CategoryRepository
     * @inject
     */
    protected $categoryRepository;

    /**
     * PreProcessing for all actions.
     *
     * @return void
     */
    protected function initializeAction()
    {
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
     * PreProcessing of view for all actions.
     *
     * @param ViewInterface $view
     *
     * @throws \Exception
     */
    protected function initializeView(ViewInterface $view)
    {
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
     */
    public function initializeShowAction()
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
     * action show.
     *
     * @param Search $search
     */
    public function showAction(Search $search = null)
    {
        // Because of the checkbox we have to create a new empty domain model
        if ($search === null) {
            $search = $this->objectManager->get('JWeiland\\Events2\\Domain\\Model\\Search');
        }
        $this->view->assign('search', $search);
    }
}
