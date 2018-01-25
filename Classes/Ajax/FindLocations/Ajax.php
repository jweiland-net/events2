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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Ajax extends AbstractAjaxRequest
{
    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * initialize this object with help of ObjectManager.
     */
    public function initializeObject()
    {
        $this->loadBaseTca();
    }

    /**
     * process ajax request.
     *
     * @param array $arguments Arguments to process
     *
     * @return string
     */
    public function processAjaxRequest(array $arguments)
    {
        // Hint: search may fail with "&" in $locationPart
        $locationPart = (string)trim(htmlspecialchars(strip_tags($arguments['locationPart'])));
        // keep it synchron to minLength in JS
        if (empty($locationPart) || strlen($locationPart) <= 2) {
            return '';
        } else {
            $locations = $this->findLocations($locationPart);

            return json_encode($locations);
        }
    }

    /**
     * find locations by locationsPart.
     *
     * @param $locationPart
     *
     * @return array
     */
    protected function findLocations($locationPart)
    {
        $locations = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid, location as label',
            'tx_events2_domain_model_location',
            'location LIKE "%' . $this->getDatabaseConnection()->escapeStrForLike($locationPart, 'tx_events2_domain_model_location') . '%"' .
            BackendUtility::BEenableFields('tx_events2_domain_model_location') .
            BackendUtility::deleteClause('tx_events2_domain_model_location'),
            '',
            'location',
            ''
        );

        if ($locations === null) {
            // SQL-Error: return empty array
            return [];
        } else {
            return $locations;
        }
    }

    /**
     * Load global TCA
     * Needed for enableFields
     *
     * @return void
     */
    protected function loadBaseTca()
    {
        ExtensionManagementUtility::loadBaseTca(false);
    }

    /**
     * Get TYPO3 Database Connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    public function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
