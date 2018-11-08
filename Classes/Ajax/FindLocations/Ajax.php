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
        // Hint: search may fail with "&" in $locationPart
        $locationPart = (string)trim(htmlspecialchars(strip_tags($arguments['locationPart'])));
        // keep it in sync to minLength in JS
        if (empty($locationPart) || strlen($locationPart) <= 2) {
            return '';
        } else {
            return json_encode($this->findLocations($locationPart));
        }
    }

    /**
     * Find locations by locationsPart.
     *
     * @param $locationPart
     * @return array
     */
    protected function findLocations($locationPart): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_events2_domain_model_location');

        $locations = $queryBuilder
            ->select('uid', 'location')
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
