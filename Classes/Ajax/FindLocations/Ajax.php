<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Ajax\FindLocations;

use JWeiland\Events2\Ajax\AbstractAjaxRequest;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This class will be loaded, if you create a new event in frontend. There we have a
 * selectbox for location, which searches for Locations by its name and stores the
 * location UID in a hidden field.
 */
class Ajax extends AbstractAjaxRequest
{
    public function processAjaxRequest(array $arguments): string
    {
        ExtensionManagementUtility::loadBaseTca(true);

        // Hint: search may fail with "&" in $locationPart
        $locationPart = (string)trim(htmlspecialchars(strip_tags($arguments['locationPart'])));
        // keep it in sync to minLength in JS
        if (empty($locationPart) || strlen($locationPart) <= 2) {
            return '';
        } else {
            return json_encode($this->findLocations($locationPart));
        }
    }

    protected function findLocations(string $locationPart): array
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
