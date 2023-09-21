<?php
if (!defined('TYPO3')) {
    die('Access denied.');
}

// Add tx_maps2_uid column to events2 location table
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('maps2')) {
    \JWeiland\Maps2\Tca\Maps2Registry::getInstance()->add(
        'events2',
        'tx_events2_domain_model_location',
        [
            'addressColumns' => ['street', 'house_number', 'zip', 'city'],
            'countryColumn' => 'country',
            'defaultStoragePid' => [
                'extKey' => 'events2',
                'property' => 'poiCollectionPid'
            ],
            'synchronizeColumns' => [
                [
                    'foreignColumnName' => 'location',
                    'poiCollectionColumnName' => 'title'
                ],
                [
                    'foreignColumnName' => 'hidden',
                    'poiCollectionColumnName' => 'hidden'
                ]
            ]
        ]
    );
}
