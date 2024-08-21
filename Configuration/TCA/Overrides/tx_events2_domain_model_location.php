<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

use JWeiland\Maps2\Tca\Maps2Registry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

// Add tx_maps2_uid column to events2 location table
if (ExtensionManagementUtility::isLoaded('maps2')) {
    Maps2Registry::getInstance()->add(
        'events2',
        'tx_events2_domain_model_location',
        [
            'addressColumns' => ['street', 'house_number', 'zip', 'city'],
            'countryColumn' => 'country',
            'defaultStoragePid' => [
                'extKey' => 'events2',
                'property' => 'poiCollectionPid',
            ],
            'synchronizeColumns' => [
                [
                    'foreignColumnName' => 'location',
                    'poiCollectionColumnName' => 'title',
                ],
                [
                    'foreignColumnName' => 'hidden',
                    'poiCollectionColumnName' => 'hidden',
                ],
            ],
        ],
    );
}
