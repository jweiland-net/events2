<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Event;

use JWeiland\Events2\Domain\Model\Filter;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/*
 * Use this event, if you want to modify the queries of DayRepository::findEvents.
 */
class ModifyQueriesOfFindEventsEvent
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
     * @var string
     */
    protected $type = '';

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var array
     */
    protected $settings = [];

    public function __construct(
        QueryBuilder $queryBuilder,
        QueryBuilder $subQueryBuilder,
        string $type,
        Filter $filter,
        array $settings
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->subQueryBuilder = $subQueryBuilder;
        $this->type = $type;
        $this->filter = $filter;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function getFilter(): Filter
    {
        return $this->filter;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }
}
