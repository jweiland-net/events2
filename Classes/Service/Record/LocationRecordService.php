<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service\Record;

use JWeiland\Events2\Event\ModifyQueriesOfFindLocationsEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class LocationRecordService
{
    use RecordServiceTrait;

    private const TABLE = 'tx_events2_domain_model_location';

    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * This method does not use any Extbase Queries, as it was necessary for Ajax Request FindLocations,
     * which does not have any Extbase Context.
     */
    public function findLocations(string $search): array
    {
        $queryBuilder = $this->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->select('uid', 'location as label')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->like(
                    'location',
                    $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards($search) . '%'),
                ),
            )
            ->orderBy('location', 'ASC');

        // Remember: column "uid" and "label" are a must-have for autocompletion
        // Use CONCAT to add further columns to the label. Example:
        // $queryBuilder->add('select', 'uid, CONCAT(location, \', \', street, \' \', house_number, \', \', zip, \' \', city) AS label')
        // Hint: add() overwrites all columns defined by select() by default
        $this->eventDispatcher->dispatch(
            new ModifyQueriesOfFindLocationsEvent($queryBuilder, $search),
        );

        $queryResult = $queryBuilder->executeQuery();

        $locationOptions = [];
        while ($locationRecord = $queryResult->fetchAssociative()) {
            $locationOptions[] = $locationRecord;
        }

        return $locationOptions;
    }

    /**
     * Use direct DB result to fill the location selector.
     * ->findAll() will start fetching all related objects, too, which results in hundreds of
     * additional unneeded DB queries.
     * ToDo: Switch to LazyLoadingProxy when this patch is merged: https://review.typo3.org/c/Packages/TYPO3.CMS/+/75417
     */
    public function getLocationsForSearchSelector(): array
    {
        return $this->getRecordsByExpression(self::TABLE);
    }
}
