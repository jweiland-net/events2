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
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/*
 * The location repository is used to sort the locations in our create-new-form. Further it will be used in
 * our event importer
 */
class LocationRepository extends AbstractRepository
{
    protected EventDispatcher $eventDispatcher;

    protected $defaultOrderings = [
        'location' => QueryInterface::ORDER_ASCENDING,
    ];

    public function injectEventDispatcher(EventDispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * This method does not use any Extbase Queries, as it was needed by Ajax Request FindLocations
     * which does not have any Extbase Context.
     */
    public function findLocations(string $search): array
    {
        $queryBuilder = $this->getQueryBuilderForTable('tx_events2_domain_model_location', 'l');
        $queryBuilder
            ->select('l.uid', 'l.location as label')
            ->where(
                $queryBuilder->expr()->like(
                    'l.location',
                    $queryBuilder->createNamedParameter('%' . $search . '%')
                )
            )
            ->orderBy('l.location', 'ASC');

        // Remember: column "uid" and "label" are a must-have for autocompletion
        // Use CONCAT to add further columns to label. Example:
        // $queryBuilder->add('select', 'uid, CONCAT(location, \', \', street, \' \', house_number, \', \', zip, \' \', city) AS label')
        // Hint: add() overwrites all columns defined by select() by default
        $this->eventDispatcher->dispatch(
            new ModifyQueriesOfFindLocationsEvent($queryBuilder, $search)
        );

        $statement = $queryBuilder->execute();
        $locations = [];
        while ($location = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $locations[] = $location;
        }

        return $locations;
    }
}
