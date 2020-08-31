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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

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
                    $queryBuilder->createNamedParameter('%' . $locationPart . '%', \PDO::PARAM_STR)
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
}
