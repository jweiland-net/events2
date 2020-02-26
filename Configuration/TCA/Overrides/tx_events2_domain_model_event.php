<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(function () {
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

    if (version_compare(TYPO3_branch, '9.4', '>=')) {
        // Router configuration can not access sanitize() method of slugs, so we have to create our own column
        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['path_segment']['config'] = [
            'type' => 'slug',
            'size' => 50,
            'generatorOptions' => [
                'fields' => ['title'],
                // As pageSlug may contain slashes, we have to remove page slug
                'prefixParentPageSlug' => false,
                'replacements' => [
                    '/' => '-'
                ],
            ],
            'fallbackCharacter' => '-',
            // Do not add / in path_segments, as they are not allowed in RouteEnhancer configuration
            'prependSlash' => false,
            'eval' => 'uniqueInSite',
            'default' => ''
        ];
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'tx_events2_domain_model_event',
            'path_segment',
            '',
            'after:title'
        );
        $GLOBALS['TCA']['tx_events2_domain_model_event']['interface']['showRecordFieldList'] .= ',path_segment';
    }
});
