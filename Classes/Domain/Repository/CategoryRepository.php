<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

/*
 * Category Repository to find records for our search form
 */
class CategoryRepository extends \TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository
{
    /**
     * @var EnvironmentService
     */
    protected $environmentService;

    public function __construct(
        ObjectManagerInterface $objectManager,
        EnvironmentService $environmentService
    ) {
        parent::__construct($objectManager);

        $this->environmentService = $environmentService;
    }

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
     * @param string $categoryUids comma separated list of category uids
     * @param int $parent parent category UID
     * @return QueryResultInterface
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

    /**
     * A very fast method to just add the related category records to event record.
     * Currently used to re-generate day records at CLI and task.
     *
     * @param array $eventRecord
     * @param bool $ignoreEnableFields If true hidden/start/end will not be included.
     * @return void
     */
    public function addCategories(array &$eventRecord, bool $ignoreEnableFields = false): void
    {
        if (
            $eventRecord === []
            || !array_key_exists('uid', $eventRecord)
            || empty($eventRecord['uid'])
            || !MathUtility::canBeInterpretedAsInteger($eventRecord['uid'])
        ) {
            return;
        }

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
        if ($this->environmentService->isEnvironmentInFrontendMode()) {
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        }

        if ($ignoreEnableFields) {
            $queryBuilder->getRestrictions()->removeByType(StartTimeRestriction::class);
            $queryBuilder->getRestrictions()->removeByType(EndTimeRestriction::class);
            $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        }

        $statement = $queryBuilder
            ->select('*')
            ->from('sys_category', 'c')
            ->leftJoin(
                'c',
                'sys_category_record_mm',
                'ce_mm',
                $queryBuilder->expr()->eq(
                    'c.uid',
                    $queryBuilder->quoteIdentifier('ce_mm.uid_local')
                )
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'ce_mm.uid_foreign',
                    $queryBuilder->createNamedParameter((int)$eventRecord['uid'])
                )
            )
            ->execute();

        $eventRecord['categories'] = [];
        while ($categoryRecord = $statement->fetch()) {
            $eventRecord['categories'][] = $categoryRecord;
        }
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
