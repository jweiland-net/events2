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
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Category Repository to find records for our search form
 */
class CategoryRepository extends Repository
{
    protected $defaultOrderings = [
        'title' => QueryInterface::ORDER_ASCENDING
    ];

    /**
     * This is a copy of deprecated CategoryRepository of TYPO3 Extbase v11
     */
    public function initializeObject(): void
    {
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    public function getCategories(string $categoryUids): QueryResultInterface
    {
        $categoryUids = $categoryUids === '' ? '0' : $categoryUids;

        $query = $this->createQuery();

        return $query
            ->matching(
                $query->in(
                    'uid',
                    GeneralUtility::intExplode(',', $categoryUids, true)
                )
            )
            ->execute();
    }

    public function getSubCategories(int $category): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->setOrderings([
            'title' => QueryInterface::ORDER_ASCENDING
        ]);

        return $query->matching($query->equals('parent', $category))->execute();
    }

    /**
     * Instead of getCategories this method will only select categories of a given parent category
     *
     * @throws \Exception
     */
    public function getSelectedCategories(string $categoryUids, int $parent = 0): QueryResultInterface
    {
        // remove empty values
        // convert them to int
        // remove values with 0 (array_filter)
        $selectedCategories = array_filter(
            GeneralUtility::intExplode(',', $categoryUids, true)
        );

        $query = $this->createQuery();
        // we have to disable language restrictions as TYPO3 BE saves category relations in default language only
        $query->getQuerySettings()->setRespectSysLanguage(false);

        $constraint = [];
        if ($selectedCategories !== []) {
            $constraint[] = $query->in('uid', $selectedCategories);
            $constraint[] = $query->equals('parent', $parent);
        } else {
            // Create constraint which will return a QueryResult with no categories
            $constraint[] = $query->equals('uid', 0);
        }

        return $query->matching($query->logicalAnd(...$constraint))->execute();
    }
}
