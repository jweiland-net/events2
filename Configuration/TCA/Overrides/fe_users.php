<?php
if (!defined('TYPO3')) {
    die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'fe_users',
    [
        'tx_events2_organizer' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_organizer',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_events2_domain_model_organizer',
                'prepend_tname' => false,
                'size' => 1,
                'default' => 0,
                'maxitems' => 1,
                'suggestOptions' => [
                    'default' => [
                        'searchWholePhrase' => true,
                        'searchCondition' => 'tx_events2_domain_model_organizer.sys_language_uid IN (-1,0)'
                    ],
                ],
            ],
        ],
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    '--div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_tab,tx_events2_organizer'
);
