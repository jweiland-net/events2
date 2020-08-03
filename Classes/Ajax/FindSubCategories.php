<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Ajax;

use JWeiland\Events2\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/*
 * This is an Ajax call to find all sub-categories for event-search Plugin
 */
class FindSubCategories extends AbstractAjaxRequest
{
    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    public function injectCategoryRepository(CategoryRepository $categoryRepository): void
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function processAjaxRequest(array $arguments): string
    {
        $categories = $this->categoryRepository->getSubCategories((int)$arguments['category']);

        return json_encode($this->reduceCategoryData($categories), JSON_FORCE_OBJECT);
    }

    protected function reduceCategoryData(QueryResultInterface $categories): array
    {
        $response = [];
        foreach ($categories as $category) {
            $response[$category->getUid()] = $category->getTitle();
        }

        return $response;
    }
}
