<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Event;

use JWeiland\Events2\Domain\Model\Search;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/*
 * Use this event, if you want to modify the queries of DayRepository::searchEvents.
 */
class ModifyQueriesOfSearchEventsEvent
{
    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var QueryBuilder
     */
    protected $subQueryBuilder;

    /**
     * @var Search
     */
    protected $search;

    /**
     * @var array
     */
    protected $settings = [];

    public function __construct(
        QueryBuilder $queryBuilder,
        QueryBuilder $subQueryBuilder,
        Search $search,
        array $settings
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->subQueryBuilder = $subQueryBuilder;
        $this->search = $search;
        $this->settings = $settings;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getSubQueryBuilder(): QueryBuilder
    {
        return $this->subQueryBuilder;
    }

    public function getSearch(): Search
    {
        return $this->search;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }
}
