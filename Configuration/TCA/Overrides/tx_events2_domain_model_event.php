<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(static function (): void {
    $extConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \JWeiland\Events2\Configuration\ExtConf::class
    );

    $fieldConfigurationForCategories = [
        'foreign_table_where' => ' AND sys_category.sys_language_uid IN (-1, 0) ORDER BY sys_category.title ASC',
    ];

    // check, if categories are required
    if ($extConf->getCategoryIsRequired()) {
        $fieldConfigurationForCategories['minitems'] = 1;
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable(
        'events2',
        'tx_events2_domain_model_event',
        'categories',
        [
            'fieldConfiguration' => $fieldConfigurationForCategories,
        ]
    );

    // check, if organizer is required
    if ($extConf->getOrganizerIsRequired()) {
        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['organizers']['config']['minitems'] = 1;
    }

    // check, if location is required
    if ($extConf->getLocationIsRequired()) {
        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['location']['config']['minitems'] = 1;
    }
});
