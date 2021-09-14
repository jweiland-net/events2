<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Repository;

use JWeiland\Events2\Event\ModifyQueriesOfFindLocationsEvent;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/*
 * The location repository is used to sort the locations in our create-new-form. Further it will be used in
 * our event importer
 */
class LocationRepository extends Repository
{
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var array
     */
    protected $defaultOrderings = [
        'location' => QueryInterface::ORDER_ASCENDING,
    ];

    public function __construct(
        ObjectManagerInterface $objectManager,
        EventDispatcher $eventDispatcher
    ) {
        parent::__construct($objectManager);

        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * This method does not use any Extbase Queries, as it was needed by Ajax Request FindLocations
     * which does not have any Extbase Context.
     *
     * @param string $search
     * @return array
     */
    public function findLocations(string $search): array
    {
        $queryBuilder = $this
            ->getConnectionPool()
            ->getQueryBuilderForTable('tx_events2_domain_model_location');
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

        $queryBuilder
            ->select('uid', 'location as label')
            ->from('tx_events2_domain_model_location')
            ->where(
                $queryBuilder->expr()->like(
                    'location',
                    $queryBuilder->createNamedParameter('%' . $search . '%')
                )
            )
            ->orderBy('location', 'ASC');

        // Remember: column "uid" and "label" are a must have for autocompletion
        // Use CONCAT to add further columns to label. Example:
        // $queryBuilder->add('select', 'uid, CONCAT(location, \', \', street, \' \', house_number, \', \', zip, \' \', city) AS label')
        // Hint: add() overwrites all columns defined by select() by default
        $this->eventDispatcher->dispatch(
            new ModifyQueriesOfFindLocationsEvent($queryBuilder, $search)
        );

        $statement = $queryBuilder->execute();
        $locations = [];
        while ($location = $statement->fetch()) {
            $locations[] = $location;
        }

        return $locations;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
