<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/*
 * Category Repository to find records for our search form
 */
class CategoryRepository extends \TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository
{
    protected $defaultOrderings = [
        'title' => QueryInterface::ORDER_ASCENDING
    ];

    public function getCategories($categoryUids): QueryResultInterface
    {
        $categoryUids = GeneralUtility::intExplode(',', $categoryUids);
        $query = $this->createQuery();

        return $query->matching($query->in('uid', $categoryUids))->execute();
    }

    public function getSubCategories(int $category): QueryResultInterface
    {
        $query = $this->createQuery();

        return $query->matching($query->equals('parent', $category))->execute();
    }

    /**
     * Get all categories given by comma separated list.
     *
     * @throws \Exception
     */
    public function getSelectedCategories(string $categoryUids, int $parent = 0): QueryResultInterface
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

        $constraint[] = $query->equals('parent', $parent);

        return $query->matching($query->logicalAnd($constraint))->execute();
    }
}
