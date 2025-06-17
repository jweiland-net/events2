<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service\Record;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

readonly class DayRecordService
{
    private const TABLE = 'tx_events2_domain_model_day';

    public function __construct(
        private QueryBuilder $queryBuilder,
        private ConnectionPool $connectionPool,
    ) {}

    public function getByEventAndTime(int $eventUid, int $timestamp): array
    {
        $queryBuilder = $this->queryBuilder;
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

        try {
            $day = $queryBuilder
                ->select('*')
                ->from(self::TABLE)
                ->where(
                    $queryBuilder->expr()->eq(
                        'event',
                        $queryBuilder->createNamedParameter($eventUid, Connection::PARAM_INT),
                    ),
                    $queryBuilder->expr()->eq(
                        'day_time',
                        $queryBuilder->createNamedParameter($timestamp, Connection::PARAM_INT),
                    ),
                )
                ->executeQuery()
                ->fetchAssociative();
        } catch (Exception $e) {
            return [];
        }

        if ($day === false) {
            $day = [];
        }

        return $day;
    }

    public function removeAllByEventRecord(array $eventRecord): void
    {
        $eventUid = (int)($eventRecord['uid'] ?? 0);
        if ($eventUid === 0) {
            return;
        }

        // Use ConnectionPool here to remove all records without any kind of query restrictions.
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
        $connection->delete(
            self::TABLE,
            [
                'event' => $eventUid,
                't3ver_wsid' => $eventRecord['t3ver_wsid'] ?? 0,
            ],
        );
    }


    public function createAll(array $days, array $columnsToWrite = []): void
    {
        if ($days === []) {
            return;
        }

        $fallbackColumns = [
            'pid',
            'tstamp',
            'crdate',
            'hidden',
            'fe_group',
            't3ver_wsid',
            'day',
            'day_time',
            'sort_day_time',
            'same_day_time',
            'is_removed_date',
            'event',
        ];

        $this->connectionPool
            ->getConnectionForTable(self::TABLE)
            ->bulkInsert(
                self::TABLE,
                $days,
                $columnsToWrite ?: $fallbackColumns,
            );
    }
}
