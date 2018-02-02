<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'dividers2tabs' => true,
        'type' => 'event_type',
        'typeicon_column' => 'event_type',
        'typeicon_classes' => [
            'default' => 'ext-events2-calendar-single',
            'single' => 'ext-events2-calendar-single',
            'recurring' => 'ext-events2-calendar-recurring',
            'duration' => 'ext-events2-calendar-duration',
        ],
        'requestUpdate' => 'same_day,each_weeks',
        'default_sortby' => 'ORDER BY title',
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
        'searchFields' => 'title,teaser,event_begin,event_end,detail_informations,',
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, event_type, top_of_list, title, teaser, event_begin, event_end, event_time, same_day, multiple_times, xth, weekday, different_times, each_weeks, recurring_end, exceptions, detail_informations, free_entry, ticket_link, alternative_times, location, organizer, images, video_link, download_links',
    ],
    'types' => [
        'single' => [
            'showitem' => '--palette--;;typeAndOnTop, --palette--;;titleLanguage, event_begin, event_time,
            --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.event_details, teaser, detail_informations, free_entry, ticket_link, alternative_times, location, organizer,
            --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.media, images, video_link, download_links,
            --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access, 
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access;access'
        ],
        'recurring' => [
            'showitem' => 'event_type, hidden, --palette--;;titleLanguage,
            --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.recurring_event,
            --palette--;;recurringBeginEnd, event_time, same_day, multiple_times, xth, weekday, different_times, each_weeks,
            --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.exceptions, exceptions,
            --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.event_details, teaser, detail_informations, free_entry, ticket_link, alternative_times, location, organizer,
            --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.media;newline, images, video_link, download_links,
            --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access, 
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access;access'
        ],
        'duration' => [
            'showitem' => '--palette--;;typeAndOnTop, --palette--;;titleLanguage, --palette--;;eventBeginEnd, event_time,
            --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.exceptions, exceptions,
            --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.event_details, teaser, detail_informations, free_entry, ticket_link, alternative_times, location, organizer,
            --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.media;newline, images, video_link, download_links,
            --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access, 
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access;access'
        ],
    ],
    'palettes' => [
        'titleLanguage' => ['showitem' => 'title, sys_language_uid, l10n_parent'],
        'eventBeginEnd' => ['showitem' => 'event_begin, event_end'],
        'typeAndOnTop' => ['showitem' => 'event_type, hidden, top_of_list'],
        'recurringBeginEnd' => ['showitem' => 'event_begin, recurring_end'],
        'access' => [
            'showitem' => 'starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel,endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel',
        ]
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
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
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => true,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_events2_domain_model_event',
                'foreign_table_where' => 'AND tx_events2_domain_model_event.pid=###CURRENT_PID### AND tx_events2_domain_model_event.sys_language_uid IN (-1,0)',
                'showIconTable' => false,
                'default' => 0,
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
            'exclude' => true,
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
            'exclude' => true,
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
            'exclude' => true,
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
        'event_type' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.event_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.event_type.single', 'single', 'ext-events2-calendar-single'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.event_type.recurring', 'recurring', 'ext-events2-calendar-recurring'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.event_type.duration', 'duration', 'ext-events2-calendar-duration'],
                ],
                'default' => 'single',
            ]
        ],
        'top_of_list' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.top_of_list',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ],
        ],
        'teaser' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.teaser',
            'config' => [
                'type' => 'text',
                'cols' => 30,
                'rows' => 4,
                'max' => '255',
                'eval' => 'trim',
            ],
        ],
        'event_begin' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.event_begin',
            'config' => [
                'type' => 'input',
                'size' => 7,
                'eval' => 'date,required',
                'default' => mktime(0, 0, 0, date('m'), date('d'), date('Y')),
            ],
        ],
        'event_end' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.event_end',
            'config' => [
                'type' => 'input',
                'size' => 7,
                'eval' => 'date,required',
            ],
        ],
        'event_time' => [
            'exclude' => true,
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
            ],
        ],
        'same_day' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.same_day',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'multiple_times' => [
            'exclude' => true,
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
            ],
        ],
        'xth' => [
            'exclude' => true,
            'displayCond' => 'FIELD:each_weeks:=:0',
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth',
            'config' => [
                'type' => 'check',
                'cols' => 5,
                'items' => [
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.first', 'first'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.second', 'second'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.third', 'third'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.fourth', 'fourth'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.fifth', 'fifth'],
                ],
                'default' => 0,
            ],
        ],
        'weekday' => [
            'exclude' => true,
            'displayCond' => 'FIELD:each_weeks:=:0',
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday',
            'config' => [
                'type' => 'check',
                'cols' => 7,
                'items' => [
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.monday', 'monday'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.tuesday', 'tuesday'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.wednesday', 'wednesday'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.thursday', 'thursday'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.friday', 'friday'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.saturday', 'saturday'],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.sunday', 'sunday'],
                ],
                'default' => 0,
            ],
        ],
        'different_times' => [
            'exclude' => true,
            'displayCond' => 'FIELD:each_weeks:=:0',
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.different_times',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_time',
                'foreign_field' => 'event',
                'foreign_types' => [
                    '1' => ['showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1, weekday, --palette--;;times,--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,starttime, endtime'],
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
            ],
        ],
        'each_weeks' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_weeks',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_weeks.0', 0],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_weeks.1', 1],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_weeks.2', 2],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_weeks.3', 3],
                    ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_weeks.4', 4],
                ],
                'default' => 0,
            ],
        ],
        'recurring_end' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.recurring_end',
            'config' => [
                'type' => 'input',
                'size' => 7,
                'eval' => 'date',
            ],
        ],
        'exceptions' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.exceptions',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_exception',
                'foreign_field' => 'event',
                'foreign_default_sortby' => 'tx_events2_domain_model_exception.exception_date ASC',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => true,
                    'levelLinksPosition' => 'both',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ],
            ],
        ],
        'detail_informations' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.detail_informations',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'wizards' => [
                    'RTE' => [
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_rte.gif',
                        'module' => [
                            'name' => 'wizard_rte',
                        ],
                        'notNewRecords' => 1,
                        'RTEonly' => 1,
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.W.RTE',
                        'type' => 'script',
                    ],
                ],
            ],
            'defaultExtras' => 'richtext:rte_transform[flag=rte_enabled|mode=ts]',
        ],
        'free_entry' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.free_entry',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
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
            ],
        ],
        'days' => [
            'config' => [
                'type' => 'select',
                'foreign_table' => 'tx_events2_domain_model_day',
                'foreign_field' => 'event',
            ],
        ],
        'location' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.location',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_events2_domain_model_location',
                'foreign_table' => 'tx_events2_domain_model_location',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 1,
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest',
                        'default' => [
                            'searchWholePhrase' => true,
                        ],
                    ],
                ],
            ],
        ],
        'organizer' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.organizer',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_events2_domain_model_organizer',
                'foreign_table' => 'tx_events2_domain_model_organizer',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 1,
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest',
                        'default' => [
                            'searchWholePhrase' => true,
                        ],
                    ],
                ],
            ],
        ],
        'images' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.images',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'images',
                [
                    'minitems' => 0,
                    'maxitems' => 5,
                    'foreign_match_fields' => [
                        'fieldname' => 'images',
                        'tablenames' => 'tx_events2_domain_model_event',
                        'table_local' => 'sys_file',
                    ],
                    'appearance' => [
                        'createNewRelationLinkTitle' => $ttContentLanguageFile . ':images.addFileReference'
                    ],
                    // custom configuration for displaying fields in the overlay/reference table
                    // to use the imageoverlayPalette instead of the basicoverlayPalette
                    'foreign_types' => [
                        '0' => [
                            'showitem' => '
                            --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => [
                            'showitem' => '
                            --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                            'showitem' => '
                            --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => [
                            'showitem' => '
                            --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.audioOverlayPalette;audioOverlayPalette,
                            --palette--;;filePalette'
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => [
                            'showitem' => '
                            --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.videoOverlayPalette;videoOverlayPalette,
                            --palette--;;filePalette'
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => [
                            'showitem' => '
                            --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                        ]
                    ]
                ]
            ),
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
            ],
        ],
        'download_links' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.download_links',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_link',
                'maxitems' => 1,
                'minitems' => 0,
                'appearance' => [
                    'levelLinksPosition' => 'both',
                    'newRecordLinkAddTitle' => true,
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ]
            ]
        ]
    ]
];
