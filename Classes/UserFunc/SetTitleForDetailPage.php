<?php

declare(strict_types = 1);

/**
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\UserFunc;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * UserFunc to show event and date in title of detail page
 */
class SetTitleForDetailPage
{
    /**
     * Render page title for detail page
     *
     * @param string $content
     * @param array $conf
     * @return string
     */
    public function render(string $content, array $conf): string
    {
        $gp = GeneralUtility::_GPmerged('tx_events2_events') ?? [];
        if ($this->isValidRequest($gp)) {
            $dayRecord = $this->getDayRecord((int)$gp['event'], (int)$gp['timestamp']);
            if (!empty($dayRecord)) {
                $date = new \DateTime(date('c', (int)$gp['timestamp']));
                $eventRecord = $this->getEventRecord((int)$dayRecord['event']);
                if (!empty($eventRecord)) {
                    $content = sprintf(
                        '%s - %s',
                        trim($eventRecord['title']),
                        $date->format('d.m.Y')
                    );
                }
            }
        }

        return $content;
    }

    protected function isValidRequest(array $gp): bool
    {
        if (!is_array($gp)) {
            return false;
        }

        if (!isset($gp['controller'], $gp['action'], $gp['timestamp'])) {
            return false;
        }

        if ((int)$gp['timestamp'] <= 0) {
            return false;
        }

        return true;
    }

    protected function getDayRecord(int $eventUid, int $timestamp): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_day');
        $day = $queryBuilder
            ->select('event')
            ->from('tx_events2_domain_model_day')
            ->where(
                $queryBuilder->expr()->eq(
                    'event',
                    $queryBuilder->createNamedParameter($eventUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'day_time',
                    $queryBuilder->createNamedParameter($timestamp, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();

        if (empty($day)) {
            $day = [];
        }
        return $day;
    }

    protected function getEventRecord(int $uid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_event');
        $event = $queryBuilder
            ->select('uid', 'title')
            ->from('tx_events2_domain_model_event')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter((int)$uid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();

        if (empty($event)) {
            $event = [];
        }
        return $event;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
