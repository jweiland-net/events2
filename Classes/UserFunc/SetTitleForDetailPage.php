<?php

namespace JWeiland\Events2\UserFunc;

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
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * UserFunc to show event and date in title of detail page
 */
class SetTitleForDetailPage
{
    /**
     * @var ContentObjectRenderer
     */
    public $cObj;

    /**
     * Render page title for detail page
     *
     * @param string $content
     * @param array $conf
     * @return string
     */
    public function render($content, $conf)
    {
        $gp = GeneralUtility::_GPmerged('tx_events2_events');
        if ($this->isValidRequest($gp)) {
            $dayRecord = $this->getDayRecord((int)$gp['day']);
            if (!empty($dayRecord)) {
                $date = new \DateTime(date('c', (int)$dayRecord['day']));
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

    /**
     * Check, if current request is valid
     *
     * @param array $gp
     * @return bool
     */
    protected function isValidRequest($gp)
    {
        if (!is_array($gp)) {
            return false;
        }

        if (
            !isset($gp['controller']) ||
            !isset($gp['action']) ||
            !isset($gp['day'])
        ) {
            return false;
        }

        if (
            !MathUtility::canBeInterpretedAsInteger($gp['day']) ||
            (int)$gp['day'] <= 0
        ) {
            return false;
        }

        return true;
    }

    /**
     * Get day record by UID
     *
     * @param int $uid
     * @return array|false
     */
    protected function getDayRecord($uid)
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_day');
        $day = $queryBuilder
            ->select('uid', 'event', 'day')
            ->from('tx_events2_domain_model_day')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();

        if (empty($day)) {
            $day = [];
        }
        return $day;
    }

    /**
     * Get event record by UID
     *
     * @param int $uid
     * @return array
     */
    protected function getEventRecord($uid)
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

    /**
     * Get TYPO3s Connection Pool
     *
     * @return ConnectionPool
     */
    protected function getConnectionPool()
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
