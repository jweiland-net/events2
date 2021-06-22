<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

/*
 * The location repository is used to sort the locations in our create-new-form. Further it will be used in
 * our event importer
 */
class ExceptionRepository extends Repository
{
    /**
     * @var EnvironmentService
     */
    protected $environmentService;

    public function __construct(
        ObjectManagerInterface $objectManager,
        EnvironmentService $environmentService
    ) {
        parent::__construct($objectManager);

        $this->environmentService = $environmentService;
    }

    /**
     * A very fast method to just add the related exception records to event record.
     * Currently used to re-generate day records at CLI and task.
     *
     * @param array $eventRecord
     * @param bool $ignoreEnableFields If true hidden/start/end will not be included.
     * @return void
     */
    public function addExceptions(array &$eventRecord, bool $ignoreEnableFields = false): void
    {
        if (
            $eventRecord === []
            || !array_key_exists('uid', $eventRecord)
            || empty($eventRecord['uid'])
            || !MathUtility::canBeInterpretedAsInteger($eventRecord['uid'])
        ) {
            return;
        }

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_exception');
        if ($this->environmentService->isEnvironmentInFrontendMode()) {
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        }

        if ($ignoreEnableFields) {
            $queryBuilder->getRestrictions()->removeByType(StartTimeRestriction::class);
            $queryBuilder->getRestrictions()->removeByType(EndTimeRestriction::class);
            $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        }

        $statement = $queryBuilder
            ->select('*')
            ->from('tx_events2_domain_model_exception')
            ->where(
                $queryBuilder->expr()->eq(
                    'event',
                    $queryBuilder->createNamedParameter((int)$eventRecord['uid'])
                )
            )
            ->execute();

        $eventRecord['exceptions'] = [];
        while ($exceptionRecord = $statement->fetch()) {
            $eventRecord['exceptions'][] = $exceptionRecord;
        }
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
