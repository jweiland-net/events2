<?php
call_user_func(function() {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable(
        'events2',
        'tx_events2_domain_model_event',
        'categories',
        [
            'fieldConfiguration' => [
                'foreign_table_where' => ' AND sys_category.sys_language_uid IN (-1, 0) ORDER BY sys_category.title ASC',
            ],
        ]
    );

    /** @var \JWeiland\Events2\Configuration\ExtConf $extConf */
    $extConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JWeiland\Events2\Configuration\ExtConf::class);

    // check, if organizer is required
    if ($extConf->getOrganizerIsRequired()) {
        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['organizer']['config']['required'] = true;
        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['organizer']['config']['minitems'] = 1;
    }
    // check, if location is required
    if ($extConf->getLocationIsRequired()) {
        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['location']['config']['required'] = true;
        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['location']['config']['minitems'] = 1;
    }
});
