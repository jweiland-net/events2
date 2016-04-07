<?php

namespace JWeiland\Events2\Ajax\FindLocations;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Stefan Froemken <projects@jweiland.net>, jweiland.net
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use JWeiland\Events2\Ajax\AbstractAjaxRequest;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Bootstrap;

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
        Bootstrap::getInstance()->loadCachedTca();
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
        $locationPart = (string) trim(htmlspecialchars(strip_tags($arguments['locationPart'])));
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
            'location LIKE "%'.$this->getDatabaseConnection()->escapeStrForLike($locationPart, 'tx_events2_domain_model_location').'%"'.
            BackendUtility::BEenableFields('tx_events2_domain_model_location').
            BackendUtility::deleteClause('tx_events2_domain_model_location'),
            '', 'location', ''
        );

        if ($locations === null) {
            // SQL-Error: return empty array
            return array();
        } else {
            return $locations;
        }
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
