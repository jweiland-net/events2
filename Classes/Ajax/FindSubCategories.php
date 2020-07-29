<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Ajax;

use JWeiland\Events2\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/*
 * This is an Ajax call to find all sub-categories for event-search Plugin
 */
class FindSubCategories extends AbstractAjaxRequest
{
    /**
     * @var \JWeiland\Events2\Domain\Repository\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * inject category repository.
     *
     * @param CategoryRepository $categoryRepository
     */
    public function injectCategoryRepository(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * process ajax request.
     *
     * @param array $arguments Arguments to process
     *
     * @return string
     */
    public function processAjaxRequest(array $arguments)
    {
        $parentCategory = (int)$arguments['category'];
        $categories = $this->categoryRepository->getSubCategories($parentCategory);

        return json_encode($this->reduceCategoryData($categories), JSON_FORCE_OBJECT);
    }

    /**
     * We don't want to add a huge JSON String with all properties through AJAX-Process
     * It is easier and smaller to pass only needed values like UID and Label.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $categories
     *
     * @return array
     */
    protected function reduceCategoryData(QueryResultInterface $categories)
    {
        $response = [];
        /** @var \TYPO3\CMS\Extbase\Domain\Model\Category $category */
        foreach ($categories as $category) {
            $response[$category->getUid()] = $category->getTitle();
        }

        return $response;
    }
}
