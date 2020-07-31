<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time',
        'label' => 'time_begin',
        'label_userFunc' => \JWeiland\Events2\Tca\TimeLabel::class . '->getTitle',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'hideTable' => true,
        'default_sortby' => 'ORDER BY time_begin',
        'versioningWS' => true,
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
        'iconfile' => 'EXT:events2/Resources/Public/Icons/tx_events2_domain_model_time.png',
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, type, weekday, time_begin, time_entry, duration, time_end',
    ],
    'types' => [
        '1' => [
            'showitem' => '--palette--;;language, type, --palette--;;times,
            --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access, 
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access;access'
        ],
    ],
    'palettes' => [
        'language' => ['showitem' => 'sys_language_uid, l10n_parent, hidden'],
        'times' => ['showitem' => 'time_begin, duration, time_entry, time_end'],
        'access' => [
            'showitem' => 'starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel,endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel',
        ]
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'items' => [
                    [
                        'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages',
                        -1,
                        'flags-multiple'
                    ],
                ],
                'default' => 0,
            ]
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_events2_domain_model_time',
                'foreign_table_where' => 'AND tx_events2_domain_model_time.pid=###CURRENT_PID### AND tx_events2_domain_model_time.sys_language_uid IN (-1,0)',
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => true,
                    ],
                ],
                'default' => 0,
            ]
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ]
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0
            ]
        ],
        'cruser_id' => [
            'label' => 'cruser_id',
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'pid' => [
            'label' => 'pid',
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'crdate' => [
            'label' => 'crdate',
            'config' => [
                'type' => 'passthrough',
            ]
        ],
        'tstamp' => [
            'label' => 'tstamp',
            'config' => [
                'type' => 'passthrough',
            ]
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 16,
                'eval' => 'datetime,int',
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ]
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 16,
                'eval' => 'datetime,int',
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ]
        ],
        'type' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'weekday' => [
            'exclude' => true,
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
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.time_begin',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'max' => 5,
                'checkbox' => 1,
                'default' => '08:00',
                'eval' => \JWeiland\Events2\Tca\Type\Time::class,
            ],
        ],
        'time_entry' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.time_entry',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'max' => 5,
                'checkbox' => 1,
                'default' => '',
                'placeholder' => '07:30',
                'eval' => \JWeiland\Events2\Tca\Type\Time::class,
            ],
        ],
        'duration' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.duration',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'max' => 5,
                'checkbox' => 1,
                'default' => '',
                'placeholder' => '02:30',
                'eval' => \JWeiland\Events2\Tca\Type\Time::class,
            ],
        ],
        'time_end' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.time_end',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'max' => 5,
                'checkbox' => 1,
                'default' => '',
                'placeholder' => '14:00',
                'eval' => \JWeiland\Events2\Tca\Type\Time::class,
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
            ]
        ]
    ]
];
