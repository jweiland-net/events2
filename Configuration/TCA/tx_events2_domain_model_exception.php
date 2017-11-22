<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_exception',
        'label' => 'exception_type',
        'label_alt' => 'exception_date',
        'label_alt_force' => 1,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'dividers2tabs' => true,
        'hideTable' => true,
        'type' => 'exception_type',
        'typeicon_column' => 'exception_type',
        'typeicon_classes' => [
            'Add' => 'extensions-events2-exception-add',
            'Remove' => 'extensions-events2-exception-remove',
            'Info' => 'extensions-events2-exception-info',
            'Time' => 'extensions-events2-exception-time',
        ],
        'versioningWS' => 2,
        'versioning_followPages' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'exception_details',
        'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('events2').'Resources/Public/Icons/tx_events2_domain_model_exception.png',
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, exception_type, exception_date, exception_time, exception_details',
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'items' => [
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages',
                        -1,
                        'flags-multiple'
                    ],
                ],
                'default' => 0,
            ]
        ],
        'l10n_parent' => [
            'exclude' => 1,
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l10n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0
                    ]
                ],
                'foreign_table' => 'tt_content',
                'foreign_table_where' => 'AND tt_content.pid=###CURRENT_PID### AND tt_content.sys_language_uid IN (-1,0)',
                'default' => 0
            ]
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ]
        ],
        't3ver_label' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'max' => '255'
            ]
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:hidden.I.0'
                    ]
                ]
            ]
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'eval' => 'datetime',
                'default' => 0
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'eval' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038)
                ]
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'exception_type' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_exception.exception_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'size' => 1,
                'items' => [
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_exception.exception_type.Add', 'Add', 'extensions-events2-exception-add'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_exception.exception_type.Remove', 'Remove', 'extensions-events2-exception-remove'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_exception.exception_type.Time', 'Time', 'extensions-events2-exception-time'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_exception.exception_type.Info', 'Info', 'extensions-events2-exception-info'],
                ],
                'default' => 'Add',
            ],
        ],
        'exception_date' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_exception.exception_date',
            'config' => [
                'type' => 'input',
                'size' => 7,
                'eval' => 'date, required',
                'checkbox' => 1,
                'default' => time(),
            ],
        ],
        'exception_time' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_exception.exception_time',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_time',
                'foreign_field' => 'exception',
                'foreign_match_fields' => [
                    'type' => 'exception_time',
                ],
                'minitems' => 0,
                'maxitems' => 1,
                'appearance' => [
                    'collapseAll' => true,
                    'levelLinksPosition' => 'top',
                    'newRecordLinkAddTitle' => true,
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ],
            ],
        ],
        'exception_details' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_exception.exception_details',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ],
        ],
        'event' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
    'types' => [
        'Add' => ['showitem' => '--palette--;;exception, sys_language_uid, l10n_parent, l10n_diffsource, exception_time, exception_details,--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,starttime, endtime'],
        'Remove' => ['showitem' => '--palette--;;exception, sys_language_uid, l10n_parent, l10n_diffsource, --div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,starttime, endtime'],
        'Time' => ['showitem' => '--palette--;;exception, sys_language_uid, l10n_parent, l10n_diffsource, exception_time, exception_details,--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,starttime, endtime'],
        'Info' => ['showitem' => '--palette--;;exception, sys_language_uid, l10n_parent, l10n_diffsource, exception_details,--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,starttime, endtime'],
    ],
    'palettes' => [
        'exception' => ['showitem' => 'exception_type, exception_date, hidden'],
    ],
];
