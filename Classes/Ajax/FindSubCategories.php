<?php

namespace JWeiland\Events2\Ajax;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Stefan Froemken <projects@jweiland.net>, jweiland.net
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
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FindSubCategories extends \JWeiland\Events2\Ajax\AbstractAjaxRequest
{
    /**
     * @var \JWeiland\Events2\Domain\Repository\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * inject category repository.
     *
     * @param \JWeiland\Events2\Domain\Repository\CategoryRepository $categoryRepository
     */
    public function injectCategoryRepository(\JWeiland\Events2\Domain\Repository\CategoryRepository $categoryRepository)
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
        $parentCategory = (int) $arguments['category'];
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
        $response = array();
        /** @var \TYPO3\CMS\Extbase\Domain\Model\Category $category */
        foreach ($categories as $category) {
            $response[$category->getUid()] = $category->getTitle();
        }

        return $response;
    }
}
