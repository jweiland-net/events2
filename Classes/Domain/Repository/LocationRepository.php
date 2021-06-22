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
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

/*
 * The location repository is used to sort the locations in our create-new-form. Further it will be used in
 * our event importer
 */
class LocationRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = [
        'location' => QueryInterface::ORDER_ASCENDING,
    ];

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
     * This method does not use any Extbase Queries, as it was needed by Ajax Request FindLocations
     * which does not have any Extbase Context.
     *
     * @param string $locationPart
     * @return array
     */
    public function findLocations(string $locationPart): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_events2_domain_model_location');

        $locations = $queryBuilder
            ->select('uid', 'location as label')
            ->from('tx_events2_domain_model_location')
            ->where(
                $queryBuilder->expr()->like(
                    'location',
                    $queryBuilder->createNamedParameter('%' . $locationPart . '%')
                )
            )
            ->orderBy('location', 'ASC')
            ->execute()
            ->fetchAll();

        if (empty($locations)) {
            $locations = [];
        }
        return $locations;
    }

    /**
     * A very fast method to just add the related location record to event record.
     * Currently used to re-generate day records at CLI and task.
     *
     * @param array $eventRecord
     * @param bool $ignoreEnableFields If true hidden/start/end will not be included.
     * @return void
     */
    public function addLocation(array &$eventRecord, bool $ignoreEnableFields = false): void
    {
        if (
            $eventRecord === []
            || !array_key_exists('location', $eventRecord)
            || empty($eventRecord['location'])
            || !MathUtility::canBeInterpretedAsInteger($eventRecord['location'])
        ) {
            return;
        }

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_location');
        if ($this->environmentService->isEnvironmentInFrontendMode()) {
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        }

        if ($ignoreEnableFields) {
            $queryBuilder->getRestrictions()->removeByType(StartTimeRestriction::class);
            $queryBuilder->getRestrictions()->removeByType(EndTimeRestriction::class);
            $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        }

        $locationRecord = $queryBuilder
            ->select('*')
            ->from('tx_events2_domain_model_location')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter((int)$eventRecord['location'])
                )
            )
            ->execute()
            ->fetch();

        if ($locationRecord) {
            $eventRecord['location'] = $locationRecord;
        }
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
