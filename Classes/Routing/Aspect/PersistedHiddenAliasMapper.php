<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Routing\Aspect;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendGroupRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Routing\Aspect\PersistedAliasMapper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This mapper is nearly the same as the original one from TYPO3 core, except that events2 needs a URI resolving
 * for hidden event records.
 */
class PersistedHiddenAliasMapper extends PersistedAliasMapper
{
    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable($this->tableName)
            ->from($this->tableName);

        $queryBuilder->setRestrictions(
            GeneralUtility::makeInstance(FrontendRestrictionContainer::class, $this->context)
        );

        // Frontend Groups are not available at this time (initialized via TSFE->determineId)
        // So this must be excluded to allow access restricted records
        $queryBuilder->getRestrictions()->removeByType(FrontendGroupRestriction::class);

        // To prevent 404 error while admin clicks on edit or activate link of mail we have to
        // remove the hidden restriction.
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);

        return $queryBuilder;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
