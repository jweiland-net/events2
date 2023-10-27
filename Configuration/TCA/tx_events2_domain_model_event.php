<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'type' => 'event_type',
        'typeicon_column' => 'event_type',
        'typeicon_classes' => [
            'default' => 'ext-events2-calendar-single',
            'single' => 'ext-events2-calendar-single',
            'recurring' => 'ext-events2-calendar-recurring',
            'duration' => 'ext-events2-calendar-duration',
        ],
        'default_sortby' => 'ORDER BY event_begin DESC',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'editlock' => 'editlock',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'searchFields' => 'title,teaser,event_begin,event_end,detail_information,',
    ],
    'types' => [
        'single' => [
            'showitem' => '--palette--;;language, --palette--;;eventTypeHiddenTopOfList, title, path_segment, event_begin, event_time,
            --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.event_details, --palette--;;teaserFreeEntry, detail_information, ticket_link, alternative_times, location, organizers,
            --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.media, images, video_link, download_links, categories
            --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access;access'
        ],
        'recurring' => [
            'showitem' => '--palette--;;language, --palette--;;eventTypeHiddenTopOfList, title, path_segment,
            --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.recurring_event,
            --palette--;;recurringBeginEnd, event_time, same_day, multiple_times, xth, weekday, different_times, --palette--;;recurringWeekMonth,
            --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.exceptions, exceptions,
            --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.event_details, --palette--;;teaserFreeEntry, detail_information, ticket_link, alternative_times, location, organizers,
            --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.media,--palette--;;newline, images, video_link, download_links, categories
            --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access;access'
        ],
        'duration' => [
            'showitem' => '--palette--;;language, --palette--;;eventTypeHiddenTopOfList, title, path_segment, --palette--;;eventBeginEnd, event_time,
            --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.exceptions, exceptions,
            --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.event_details, --palette--;;teaserFreeEntry, detail_information, ticket_link, alternative_times, location, organizers,
            --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.media,--palette--;;newline, images, video_link, download_links, categories
            --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access;access'
        ],
    ],
    'palettes' => [
        'language' => ['showitem' => 'sys_language_uid, l10n_parent'],
        'eventTypeHiddenTopOfList' => ['showitem' => 'event_type, hidden, top_of_list'],
        'eventBeginEnd' => ['showitem' => 'event_begin, event_end'],
        'recurringBeginEnd' => ['showitem' => 'event_begin, recurring_end'],
        'recurringWeekMonth' => ['showitem' => 'each_weeks, each_months'],
        'teaserFreeEntry' => ['showitem' => 'teaser, free_entry'],
        'access' => [
            'showitem' => 'starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel,endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel,
            --linebreak--,
            fe_group;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:fe_group_formlabel,
            --linebreak--,editlock',
        ]
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_events2_domain_model_event',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 0,
                'default' => 0,
            ]
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.visible',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'value' => '',
                        'invertStateDisplay' => true
                    ]
                ],
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
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'default' => 0
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'default' => 0,
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'fe_group' => [
            'exclude' => true,
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'items' => [
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hide_at_login',
                        'value' => -1,
                    ],
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.any_login',
                        'value' => -2,
                    ],
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.usergroups',
                        'value' => '--div--',
                    ],
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
            ],
        ],
        'editlock' => [
            'exclude' => true,
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'displayCond' => 'HIDE_FOR_NON_ADMINS',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:editlock',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'items' => [
                    [
                        'label' => '',
                        'value' => '',
                    ]
                ],
            ]
        ],
        'event_type' => [
            'exclude' => true,
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.event_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.event_type.single',
                        'value' => 'single',
                        'icon' => 'ext-events2-calendar-single',
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.event_type.recurring',
                        'value' => 'recurring',
                        'icon' => 'ext-events2-calendar-recurring',
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.event_type.duration',
                        'value' => 'duration',
                        'icon' => 'ext-events2-calendar-duration',
                    ],
                ],
                'default' => 'single',
            ]
        ],
        'top_of_list' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.top_of_list',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'value' => '',
                        'invertStateDisplay' => false
                    ]
                ],
            ]
        ],
        'title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.title',
            'config' => [
                'type' => 'input',
                'required' => true,
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'path_segment' => [
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.path_segment',
            'displayCond' => 'VERSION:IS:false',
            'config' => [
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => [
                    'fields' => ['title'],
                    // Do not add pageSlug, as we add pageSlug on our own in RouteEnhancer
                    'prefixParentPageSlug' => false,
                    'fieldSeparator' => '-',
                    'replacements' => [
                        '/' => '-'
                    ],
                ],
                'fallbackCharacter' => '-',
                'eval' => 'unique',
                'default' => ''
            ]
        ],
        'teaser' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.teaser',
            'config' => [
                'type' => 'text',
                'cols' => 30,
                'rows' => 4,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'event_begin' => [
            'exclude' => true,
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.event_begin',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'default' => 0,
                'required' => true,
            ],
        ],
        'event_end' => [
            'exclude' => true,
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.event_end',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'default' => 0,
                'required' => true,
            ],
        ],
        'event_time' => [
            'exclude' => true,
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.event_time',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_time',
                'foreign_field' => 'event',
                'foreign_match_fields' => [
                    'type' => 'event_time',
                ],
                'minitems' => 0,
                'maxitems' => 1,
                'appearance' => [
                    'collapseAll' => true,
                    'newRecordLinkTitle' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:createNewRelationForTime',
                    'levelLinksPosition' => 'top',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ],
                'default' => 0,
            ],
        ],
        'same_day' => [
            'exclude' => true,
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.same_day',
            'onChange' => 'reload',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'value' => '',
                        'invertStateDisplay' => false
                    ]
                ],
            ]
        ],
        'multiple_times' => [
            'exclude' => true,
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'displayCond' => 'FIELD:same_day:REQ:true',
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.multiple_times',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_time',
                'foreign_field' => 'event',
                'foreign_match_fields' => [
                    'type' => 'multiple_times',
                ],
                'minitems' => 0,
                'maxitems' => 10,
                'appearance' => [
                    'collapseAll' => true,
                    'newRecordLinkAddTitle' => true,
                    'levelLinksPosition' => 'top',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ],
                'default' => 0,
            ],
        ],
        'xth' => [
            'exclude' => true,
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'displayCond' => [
                'AND' => [
                    'FIELD:each_weeks:=:0',
                    'FIELD:each_months:=:0',
                ]
            ],
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth',
            'config' => [
                'type' => 'check',
                'cols' => 5,
                'items' => [
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.first',
                        'value' => 'first',
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.second',
                        'value' => 'second',
                        ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.third',
                        'value' => 'third',
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.fourth',
                        'value' => 'fourth',
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.fifth',
                        'value' => 'fifth',
                    ],
                ],
                'default' => 0,
            ],
        ],
        'weekday' => [
            'exclude' => true,
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'displayCond' => [
                'AND' => [
                    'FIELD:each_weeks:=:0',
                    'FIELD:each_months:=:0',
                ]
            ],
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday',
            'config' => [
                'type' => 'check',
                'cols' => 7,
                'items' => [
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.monday',
                        'value' => 'monday',
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.tuesday',
                        'value' => 'tuesday',
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.wednesday',
                        'value' =>  'wednesday',
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.thursday',
                        'value' => 'thursday',
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.friday',
                        'value' => 'friday',
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.saturday',
                        'value' => 'saturday',
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.sunday',
                        'value' => 'sunday',
                    ],
                ],
                'default' => 0,
            ],
        ],
        'different_times' => [
            'exclude' => true,
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'displayCond' => [
                'AND' => [
                    'FIELD:each_weeks:=:0',
                    'FIELD:each_months:=:0',
                ]
            ],
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.different_times',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_time',
                'foreign_field' => 'event',
                'overrideChildTca' => [
                    'types' => [
                        '1' => ['showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1, weekday, --palette--;;times,--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,starttime, endtime'],
                    ]
                ],
                'foreign_match_fields' => [
                    'type' => 'different_times',
                ],
                'minitems' => 0,
                'maxitems' => 7,
                'appearance' => [
                    'collapseAll' => true,
                    'newRecordLinkAddTitle' => true,
                    'levelLinksPosition' => 'both',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ],
                'default' => 0,
            ],
        ],
        'each_weeks' => [
            'exclude' => true,
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_weeks',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_weeks.0',
                        'value' => 0,
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_weeks.1',
                        'value' => 1,
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_weeks.2',
                        'value' => 2,
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_weeks.3',
                        'value' => 3,
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_weeks.4',
                        'value' => 4,
                    ],
                ],
                'default' => 0,
            ],
        ],
        'each_months' => [
            'exclude' => true,
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_months',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_months.0',
                        'value' => 0,
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_months.1',
                        'value' => 1,
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_months.2',
                        'value' => 2,
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_months.3',
                        'value' => 3,
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_months.4',
                        'value' => 4,
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_months.5',
                        'value' => 5,
                    ],
                    [
                        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_months.6',
                        'value' => 6,
                    ],
                ],
                'default' => 0,
            ],
        ],
        'recurring_end' => [
            'exclude' => true,
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.recurring_end',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'default' => 0,
            ],
        ],
        'exceptions' => [
            'exclude' => true,
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.exceptions',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_exception',
                'foreign_field' => 'event',
                'foreign_default_sortby' => 'exception_date ASC',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => true,
                    'levelLinksPosition' => 'both',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ],
                'default' => 0,
            ],
        ],
        'detail_information' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.detail_information',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'softref' => 'typolink_tag,images,email[subst],url',
                'enableRichtext' => true,
            ],
        ],
        'free_entry' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.free_entry',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'value' => '',
                        'invertStateDisplay' => false
                    ]
                ],
            ]
        ],
        'ticket_link' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.ticket_link',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_link',
                'maxitems' => 1,
                'minitems' => 0,
                'appearance' => [
                    'levelLinksPosition' => 'top',
                    'newRecordLinkAddTitle' => true,
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ],
                'default' => 0,
            ],
        ],
        'days' => [
            'exclude' => false,
            'config' => [
                // Do not set type to 'inline' or 'select'.
                // If so, DataHandler will try to localize the related records, too.
                // But day table is NOT localizable until now.
                // Extbase will only check for foreign_table, but not for type of relation. So passthrough is OK.
                'type' => 'passthrough',
                'foreign_table' => 'tx_events2_domain_model_day',
                'foreign_field' => 'event',
            ],
        ],
        'location' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.location',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_events2_domain_model_location',
                'foreign_table' => 'tx_events2_domain_model_location',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 0,
                'default' => 0,
                'suggestOptions' => [
                    'default' => [
                        'searchWholePhrase' => true,
                    ],
                ],
            ],
        ],
        'organizers' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.organizers',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_events2_domain_model_organizer',
                'foreign_table_where' => ' AND (###PAGE_TSCONFIG_IDLIST### = 0 OR tx_events2_domain_model_organizer.pid IN(###PAGE_TSCONFIG_IDLIST###)) AND (tx_events2_domain_model_organizer.sys_language_uid IN (-1,0) OR tx_events2_domain_model_organizer.l10n_parent = 0) ORDER BY tx_events2_domain_model_organizer.organizer ASC',
                'MM' => 'tx_events2_event_organizer_mm',
                'size' => 5,
                'maxitems' => 10,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
        'images' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.images',
            'config' => [
                'type' => 'file',
                'minitems' => 0,
                'maxitems' => 5,
                'allowed' => 'common-image-types',
            ],
        ],
        'video_link' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.video_link',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_link',
                'maxitems' => 1,
                'minitems' => 0,
                'appearance' => [
                    'levelLinksPosition' => 'top',
                    'newRecordLinkAddTitle' => true,
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ],
                'default' => 0,
            ],
        ],
        'download_links' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.download_links',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_link',
                'maxitems' => 3,
                'minitems' => 0,
                'appearance' => [
                    'levelLinksPosition' => 'both',
                    'newRecordLinkAddTitle' => true,
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ],
                'default' => 0,
            ]
        ],
        'categories' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.categories',
            'config' => [
                'type' => 'category',
                'foreign_table_where' => ' AND sys_category.sys_language_uid IN (-1, 0) ORDER BY sys_category.title ASC',
            ]
        ],
        'import_id' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
];
