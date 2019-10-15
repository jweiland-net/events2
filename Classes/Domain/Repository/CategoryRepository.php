<?php
declare(strict_types = 1);
namespace JWeiland\Events2\Domain\Repository;

/*
 * This file is part of the events2 project.
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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Category Repository to find records for our search form
 */
class CategoryRepository extends \TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository
{
    /**
     * Get category objects from list of UIDs.
     *
     * @param string $categoryUids UIDs category
     * @return QueryResultInterface
     * @throws \Exception
     */
    public function getCategories($categoryUids): QueryResultInterface
    {
        $categoryUids = GeneralUtility::intExplode(',', $categoryUids);
        $query = $this->createQuery();

        return $query->matching($query->in('uid', $categoryUids))->execute();
    }

    /**
     * Get subcategories of given UID.
     *
     * @param string $category UID category
     * @return QueryResultInterface
     */
    public function getSubCategories($category): QueryResultInterface
    {
        $query = $this->createQuery();

        return $query->matching($query->equals('parent', $category))->execute();
    }

    /**
     * Get all categories given by comma separated list.
     *
     * @param string $categoryUids comma separated list of category uids
     * @param string $parent parent category UID. This value comes from TS, so it's a string
     * @return QueryResultInterface
     * @throws \Exception
     */
    public function getSelectedCategories($categoryUids, $parent = '0'): QueryResultInterface
    {
        // remove empty values
        // convert them to int
        // remove values with 0 (array_filter)
        // correct keys for unit tests (array_values)
        $selectedCategories = array_values(array_filter(GeneralUtility::intExplode(',', $categoryUids, true)));
        $query = $this->createQuery();
        // we have to disable language restrictions as TYPO3 BE saves category relations in default language only
        $query->getQuerySettings()->setRespectSysLanguage(false);

        $constraint = [];

        if (!empty($selectedCategories)) {
            $constraint[] = $query->in('uid', $selectedCategories);
        }

        $constraint[] = $query->equals('parent', (int)$parent);

        return $query->matching($query->logicalAnd($constraint))->execute();
    }
}
