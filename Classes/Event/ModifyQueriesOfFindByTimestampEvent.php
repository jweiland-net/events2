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
 * Use this event, if you want to modify the queries of DayRepository::findByTimestamp.
 */
class ModifyQueriesOfFindByTimestampEvent
{
    protected QueryBuilder $queryBuilder;

    protected int $timestamp = 0;

    protected array $settings = [];

    public function __construct(
        QueryBuilder $queryBuilder,
        int $timestamp,
        array $settings
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->timestamp = $timestamp;
        $this->settings = $settings;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }
}
