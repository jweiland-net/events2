<?php
// Add tx_maps2_uid column to events2 location table
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('maps2')) {
    \JWeiland\Maps2\Tca\Maps2Registry::getInstance()->add(
        'events2',
        'tx_events2_domain_model_location',
        [
            'addressColumns' => ['street', 'house_number', 'zip', 'city', 'country'],
            'countryColumn' => 'country',
            'synchronizeColumns' => [
                [
                    'foreignColumnName' => 'location',
                    'poiCollectionColumnName' => 'title'
                ]
            ]
        ]
    );
}
