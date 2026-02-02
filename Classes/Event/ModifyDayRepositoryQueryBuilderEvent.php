<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Event;

use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * PSR-14 event fired immediately after creating a new TYPO3 DBAL QueryBuilder for DayRepository,
 * but before the repository adds FROM/WHERE/JOIN parts.
 *
 * Use this event for *early* customization of the QueryBuilder, especially:
 * - to adjust the restriction container (FrontendRestrictionContainer), e.g. add/remove restrictions
 * - to add statements that must be in place before joins are built
 *
 * Why so early?
 * Later events like ModifyQueriesOfFindEventsEvent / ModifyQueriesOfSearchEventsEvent run after
 * joins have already been added. At that point TYPO3/DBAL may have already transformed restrictions
 * into composite join expressions, making it hard (or impossible) to reliably remove individual
 * restrictions because column/table identifiers are no longer available.
 *
 * Intended usage examples:
 * - remove or replace specific frontend restrictions for this repository query
 * - add additional custom restrictions before any JOIN conditions are generated
 *
 * Not intended for:
 * - changing filters that are applied later by the repository logic
 * - modifying the sub-query builders used for list/search (use the dedicated later events instead)
 */
class ModifyDayRepositoryQueryBuilderEvent
{
    private QueryBuilder $queryBuilder;

    private string $table;

    private string $alias;

    private array $settings;

    public function __construct(
        QueryBuilder $queryBuilder,
        string $table,
        string $alias,
        array $settings = []
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->table = $table;
        $this->alias = $alias;
        $this->settings = $settings;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }
}
