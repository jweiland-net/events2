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
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DayRecordService
{
    private const TABLE = 'tx_events2_domain_model_day';

    public function __construct(
        private readonly QueryBuilder $queryBuilder,
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
}
