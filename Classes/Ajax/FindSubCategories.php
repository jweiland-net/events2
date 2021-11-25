<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Ajax;

use JWeiland\Events2\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/*
 * This is an Ajax call to find all SubCategories for tx_events2_search Plugin
 */
class FindSubCategories implements AjaxInterface
{
    protected CategoryRepository $categoryRepository;

    /**
     * Will be called by ObjectManager in AjaxController, so Extbase classes can be used.
     */
    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function processAjaxRequest(array $arguments): string
    {
        if (!array_key_exists('category', $arguments)) {
            return json_encode([], JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT);
        }

        return json_encode($this->reduceCategoryData(
            $this->categoryRepository->getSubCategories(
                (int)$arguments['category']
            )
        ), JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT);
    }

    /**
     * @return string[]
     */
    protected function reduceCategoryData(QueryResultInterface $categories): array
    {
        $response = [];
        foreach ($categories as $category) {
            $response[$category->getUid()] = $category->getTitle();
        }

        return $response;
    }
}
