<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

use JWeiland\Events2\Domain\Model\Search;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SearchController extends AbstractController
{
    /**
     * PreProcessing for all actions.
     *
     * @return void
     * @throws \Exception
     */
    public function initializeAction()
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
     */
    protected function initializeView(ViewInterface $view)
    {
        if (!$this->settings['mainCategories']) {
            $this->addFlashMessage('Dear Admin: You have forgotten to define some allowed categories in plugin configuration');
        }

        $allowedMainCategories = $this->categoryRepository->getSelectedCategories(
            $this->settings['mainCategories'],
            $this->settings['rootCategory']
        );

        if (!$allowedMainCategories->count()) {
            $this->addFlashMessage('Dear Admin: Please check if you have set rootCategory correctly as parent of your defined mainCategories.');
        }

        $selectorData = [];
        $selectorData['categories']['main'] = $allowedMainCategories;
        $selectorData['categories']['sub'] = [];
        $selectorData['locations'] = $this->locationRepository->findAll();

        $view->assign('selectorData', $selectorData);
        parent::initializeView($view);
    }

    /**
     * we have a self-build form.
     * That's why we have to manually allow some form-elements.
     *
     * @return void
     * @throws \Exception
     */
    public function initializeShowAction()
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
     * action show.
     *
     * @param Search $search
     * @return void
     */
    public function showAction(Search $search = null)
    {
        // Because of the checkbox we have to create a new empty domain model
        if ($search === null) {
            $search = $this->objectManager->get(Search::class);
        }

        $gettableSearchProperties = ObjectAccess::getGettableProperties($search);
        if ($search->getMainCategory() instanceof Category) {
            $gettableSearchProperties['mainCategory'] = ObjectAccess::getGettableProperties($search->getMainCategory());
        }
        if ($search->getSubCategory() instanceof Category) {
            $gettableSearchProperties['subCategory'] = ObjectAccess::getGettableProperties($search->getSubCategory());
        }

        $this->view->assign('search', $search);
        $this->view->assign('jsVariables', json_encode(
            $this->getJsVariables([
                'siteId' => $GLOBALS['TSFE']->id,
                'search' => $gettableSearchProperties
            ])
        ));
    }
}
