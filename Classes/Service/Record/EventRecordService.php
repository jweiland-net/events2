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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EventRecordService
{
    private const TABLE = 'tx_events2_domain_model_event';

    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        protected PageRepository $pageRepository,
    ) {}

    public function findByUid(
        int $eventUid,
        bool $doLanguageOverlay = true,
        QueryRestrictionContainerInterface $restrictionContainer = null,
    ): array {
        $eventUidOfLiveVersion = $this->getLiveVersionOfEventUid($eventUid);

        $queryBuilder = $this->getQueryBuilder($restrictionContainer);

        try {
            $eventRecord = $queryBuilder
                ->select('*')
                ->from(self::TABLE)
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($eventUidOfLiveVersion, Connection::PARAM_INT),
                    ),
                )
                ->executeQuery()
                ->fetchAssociative();
        } catch (Exception) {
            return [];
        }

        if ($doLanguageOverlay === true) {
            $this->pageRepository->getLanguageOverlay(self::TABLE, $eventRecord);
        }

        return is_array($eventRecord) ? $eventRecord : [];
    }

    protected function getQueryBuilder(QueryRestrictionContainerInterface $restrictionContainer = null): QueryBuilder
    {
        $queryBuilder = $this->queryBuilder;

        if ($restrictionContainer instanceof QueryRestrictionContainerInterface) {
            $queryBuilder->setRestrictions($restrictionContainer);
        } else {
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(DefaultRestrictionContainer::class));
        }

        return $queryBuilder;
    }

    protected function getLiveVersionOfEventUid(int $eventUid): int
    {
        return BackendUtility::getLiveVersionIdOfRecord(self::TABLE, $eventUid) ?? $eventUid;
    }
}
