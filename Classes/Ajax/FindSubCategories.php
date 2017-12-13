<?php

namespace JWeiland\Events2\Ajax;

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
use JWeiland\Events2\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
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
