<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time',
        'label' => 'time_begin',
        'label_userFunc' => 'JWeiland\\Events2\\Tca\\TimeLabel->getTitle',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'dividers2tabs' => true,
        'hideTable' => true,
        'default_sortby' => 'ORDER BY time_begin',
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
        'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('events2') . 'Resources/Public/Icons/tx_events2_domain_model_time.png',
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, type, weekday, time_begin, time_entry, duration, time_end',
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
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
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
        'l10n_diffsource' => [
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
        'type' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'weekday' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.weekday',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.weekday.monday', 'monday'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.weekday.tuesday', 'tuesday'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.weekday.wednesday', 'wednesday'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.weekday.thursday', 'thursday'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.weekday.friday', 'friday'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.weekday.saturday', 'saturday'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.weekday.sunday', 'sunday'],
                ],
                'eval' => 'required',
                'default' => strtolower(date('l')),
            ],
        ],
        'time_begin' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.time_begin',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'max' => 5,
                'checkbox' => 1,
                'default' => '08:00',
                'eval' => 'JWeiland\\Events2\\Tca\\Type\\Time',
            ],
        ],
        'time_entry' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.time_entry',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'max' => 5,
                'checkbox' => 1,
                'default' => '',
                'placeholder' => '07:30',
                'eval' => 'JWeiland\\Events2\\Tca\\Type\\Time',
            ],
        ],
        'duration' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.duration',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'max' => 5,
                'checkbox' => 1,
                'default' => '',
                'placeholder' => '02:30',
                'eval' => 'JWeiland\\Events2\\Tca\\Type\\Time',
            ],
        ],
        'time_end' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.time_end',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'max' => 5,
                'checkbox' => 1,
                'default' => '',
                'placeholder' => '14:00',
                'eval' => 'JWeiland\\Events2\\Tca\\Type\\Time',
            ],
        ],
        'event' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'exception' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => '--palette--;;language, type, --palette--;;times,--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,starttime, endtime'],
    ],
    'palettes' => [
        'language' => ['showitem' => 'sys_language_uid, l10n_parent, hidden'],
        'times' => ['showitem' => 'time_begin, duration, time_entry, time_end'],
    ],
];
