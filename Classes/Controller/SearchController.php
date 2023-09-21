<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

use JWeiland\Events2\Domain\Model\Search;
use JWeiland\Events2\Domain\Repository\CategoryRepository;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\LocationRepository;
use JWeiland\Events2\Utility\CacheUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/*
 * This controller shows the search-form and search-results.
 */
class SearchController extends AbstractController
{
    protected DayRepository $dayRepository;

    protected CategoryRepository $categoryRepository;

    protected LocationRepository $locationRepository;

    public function injectDayRepository(DayRepository $dayRepository): void
    {
        $this->dayRepository = $dayRepository;
    }

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

    public function showAction(?Search $search = null): ResponseInterface
    {
        // Because of the checkbox in search form we have to create a new empty domain model
        $search ??= GeneralUtility::makeInstance(Search::class);

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
                'locations' => $this->locationRepository->getLocationsForSearchSelector(),
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

        return $this->htmlResponse();
    }

    public function initializeListSearchResultsAction(): void
    {
        $this->preProcessControllerAction();
    }

    public function listSearchResultsAction(?Search $search = null): ResponseInterface
    {
        if ($search instanceof Search) {
            $days = $this->dayRepository->searchEvents($search);

            $this->postProcessAndAssignFluidVariables([
                'days' => $days
            ]);

            CacheUtility::addPageCacheTagsByQuery($days->getQuery());
        }

        return $this->htmlResponse();
    }
}
