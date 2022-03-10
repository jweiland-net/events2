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
use JWeiland\Events2\Domain\Model\Search;
use JWeiland\Events2\Domain\Repository\CategoryRepository;
use JWeiland\Events2\Domain\Repository\LocationRepository;
use JWeiland\Events2\Service\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/*
 * This controller shows the search-form and search-results.
 */
class SearchController extends AbstractController
{
    protected CategoryRepository $categoryRepository;

    protected LocationRepository $locationRepository;

    public function injectCategoryRepository(CategoryRepository $categoryRepository): void
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function injectLocationRepository(LocationRepository $locationRepository): void
    {
        $this->locationRepository = $locationRepository;
    }

    public function initializeShowAction(): void
    {
        $this->preProcessControllerAction();
    }

    public function showAction(?Search $search = null): void
    {
        // Because of the checkbox in search form we have to create a new empty domain model
        if ($search === null) {
            $search = GeneralUtility::makeInstance(Search::class);
        }

        if (!$this->settings['mainCategories']) {
            $this->addFlashMessage('Dear Admin: You have forgotten to define some allowed categories in plugin configuration');
        }

        $allowedMainCategories = $this->categoryRepository->getSelectedCategories(
            (string)$this->settings['mainCategories'],
            (int)$this->settings['rootCategory']
        );

        if ($allowedMainCategories->count() === 0) {
            $this->addFlashMessage('Dear Admin: Please check if you have set rootCategory correctly as parent of your defined mainCategories.');
        }

        // Convert sub-properties to array
        $gettableSearchProperties = ObjectAccess::getGettableProperties($search);
        if ($search->getMainCategory() instanceof Category) {
            $gettableSearchProperties['mainCategory'] = ObjectAccess::getGettableProperties($search->getMainCategory());
        }

        if ($search->getSubCategory() instanceof Category) {
            $gettableSearchProperties['subCategory'] = ObjectAccess::getGettableProperties($search->getSubCategory());
        }

        $this->postProcessAndAssignFluidVariables([
            'search' => $search,
            'selectorData' => [
                'locations' => $this->locationRepository->findAll(),
                'categories' => [
                    'main' => $allowedMainCategories,
                    'sub' => []
                ]
            ],
            'jsVariables' => json_encode($this->getJsVariables([
                'siteId' => $GLOBALS['TSFE']->id,
                'search' => $gettableSearchProperties
            ]), JSON_THROW_ON_ERROR),
        ]);
    }
}
