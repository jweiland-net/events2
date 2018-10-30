<?php
call_user_func(function() {
    // Add tx_maps2_uid column to events2 location table
    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('maps2')) {
        $result = \JWeiland\Maps2\Tca\Maps2Registry::getInstance()->add(
            'events2',
            'tx_events2_domain_model_location'
        );
    }
});
