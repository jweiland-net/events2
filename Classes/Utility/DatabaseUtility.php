<?php
namespace JWeiland\Events2\Utility;

/*
 * This file is part of the events2 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A little helper to organize our DB queries
 */
class DatabaseUtility
{
    /**
     * Get column definitions from table
     *
     * @param string $tableName
     * @return array
     */
    public static function getColumnsFromTable($tableName): array
    {
        $output = [];
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);
        $statement = $connection->query('SHOW FULL COLUMNS FROM `' . $tableName . '`');
        while ($fieldRow = $statement->fetch()) {
            $output[$fieldRow['Field']] = $fieldRow;
        }
        return $output;
    }

    /**
     * With this method you get all current and future events of all event types.
     * It does not select hidden records as eventRepository->findByIdentifier will not find them.
     *
     * @return array
     */
    public static function getCurrentAndFutureEvents()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_events2_domain_model_event');

        $orConstraints = [];

        // add where clause for single events
        $orConstraints[] = $queryBuilder->expr()->andX(
            $queryBuilder->expr()->eq(
                'event_type',
                $queryBuilder->createNamedParameter('single', \PDO::PARAM_STR)
            ),
            $queryBuilder->expr()->gt(
                'event_begin',
                $queryBuilder->createNamedParameter(time(), \PDO::PARAM_INT)
            )
        );

        // add where clause for duration events
        $orConstraints[] = $queryBuilder->expr()->andX(
            $queryBuilder->expr()->eq(
                'event_type',
                $queryBuilder->createNamedParameter('duration', \PDO::PARAM_STR)
            ),
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->gt(
                    'event_end',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->gt(
                    'event_end',
                    $queryBuilder->createNamedParameter(time(), \PDO::PARAM_INT)
                )
            )
        );

        // add where clause for recurring events
        $orConstraints[] = $queryBuilder->expr()->andX(
            $queryBuilder->expr()->eq(
                'event_type',
                $queryBuilder->createNamedParameter('recurring', \PDO::PARAM_STR)
            ),
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->gt(
                    'recurring_end',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->gt(
                    'recurring_end',
                    $queryBuilder->createNamedParameter(time(), \PDO::PARAM_INT)
                )
            )
        );

        $events = $queryBuilder
            ->select('uid', 'pid')
            ->from('tx_events2_domain_model_event')
            ->where(
                $queryBuilder->expr()->orX(...$orConstraints)
            )
            ->execute()
            ->fetchAll();

        if (empty($events)) {
            $events = [];
        }

        return $events;
    }
}
