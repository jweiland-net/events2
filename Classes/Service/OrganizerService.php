<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service to work with EXT:events2 organization records.
 */
class OrganizerService
{
    private const TABLE = 'tx_events2_domain_model_organizer';

    public function getOrganizerRecordByTitle(string $title, int $language = 0): ?array
    {
        $queryBuilder = $this->getQueryBuilder();
        try {
            $organizerRecord = $queryBuilder->select('*')
                ->from(self::TABLE)
                ->where(
                    $queryBuilder->expr()->eq(
                        'organizer',
                        $queryBuilder->createNamedParameter($title),
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter($language, Connection::PARAM_INT),
                    ),
                )
                ->executeQuery()
                ->fetchAssociative();
        } catch (Exception $e) {
            return null;
        }

        return is_array($organizerRecord) ? $organizerRecord : null;
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

        return $queryBuilder;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
