<?php

namespace JWeiland\Events2\Ajax\FindLocations;

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

use JWeiland\Events2\Ajax\AbstractAjaxRequest;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class will be loaded, if you create a new event in frontend. There we have a
 * selectbox for location, which searches for Locations by its name and stores the
 * location UID in a hidden field.
 */
class Ajax extends AbstractAjaxRequest
{
    /**
     * Process ajax request.
     *
     * @param array $arguments Arguments to process
     * @return string
     */
    public function processAjaxRequest(array $arguments): string
    {
        ExtensionManagementUtility::loadBaseTca(true);
        
        // Hint: search may fail with "&" in $search
        $search = (string)trim(htmlspecialchars(strip_tags($arguments['search'])));
        // keep it in sync to minLength in JS
        if (empty($search) || strlen($search) <= 2) {
            return '';
        } else {
            return json_encode($this->findLocations($search));
        }
    }

    /**
     * Find locations by search string.
     *
     * @param $search
     * @return array
     */
    protected function findLocations($search): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_events2_domain_model_location');
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_events2_domain_model_event');
        
        $locations = $queryBuilder
            ->select('uid', 'location', 'street', 'house_number', 'zip', 'city', 'country')
            ->from('tx_events2_domain_model_location')
            ->orWhere(
                $queryBuilder->expr()->like(
                    'location',
                    $queryBuilder->createNamedParameter('%' . $search . '%', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->like(
                    'street',
                    $queryBuilder->createNamedParameter('%' . $search . '%', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->like(
                    'city',
                    $queryBuilder->createNamedParameter('%' . $search . '%', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->like(
                    'zip',
                    $queryBuilder->createNamedParameter('%' . $search . '%', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->comparison(
                    $connection->getDatabasePlatform()->getConcatExpression('location, \' \', street, \' \', zip, \' \', city'),
                    'LIKE',
                    $queryBuilder->createNamedParameter('%' . $search . '%', \PDO::PARAM_STR)
                )
            )
            ->orderBy('location', 'ASC')
            ->execute()
            ->fetchAll();
        
        if (empty($locations)) {
            $locations = [];
        }
        foreach ($locations as &$location) {
            $location['label'] = $location['location'];
            if (strlen($location['street'] . $location['house_number']) > 0) {
                $location['label'] .= ', ' . $location['street'] . ' ' . $location['house_number'];
            }
            if (strlen($location['zip'] . $location['city']) > 0) {
                $location['label'] .= ', ' . $location['zip'] . ' ' . $location['city'];
            }
        }
        return $locations;
    }
}
