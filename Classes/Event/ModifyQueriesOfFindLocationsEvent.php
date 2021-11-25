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

/*
 * Use this event, if you want to modify the queries of LocationRepository::findLocations.
 */
class ModifyQueriesOfFindLocationsEvent
{
    protected QueryBuilder $queryBuilder;

    protected string $search = '';

    public function __construct(
        QueryBuilder $queryBuilder,
        string $search
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->search = $search;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getSearch(): string
    {
        return $this->search;
    }
}
