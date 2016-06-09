<?php
if (\TYPO3\CMS\Core\Utility\GeneralUtility::compat_version('7.6')) {
    $ttContentLanguageFile = 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf';
} else {
    $ttContentLanguageFile = 'LLL:EXT:cms/locallang_ttc.xlf';
}
return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'dividers2tabs' => true,
        'requestUpdate' => 'same_day,each_weeks',
        'default_sortby' => 'ORDER BY title',
        'versioningWS' => 2,
        'versioning_followPages' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ),
        'searchFields' => 'title,teaser,event_begin,event_end,detail_informations,',
        'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('events2') . 'Resources/Public/Icons/tx_events2_domain_model_event.gif',
    ),
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, title, top_of_list, teaser, event_begin, event_time, event_end, recurring_event, same_day, multiple_times, xth, weekday, different_times, each_weeks, exceptions, detail_informations, free_entry, ticket_link, alternative_times, location, organizer, images, video_link, download_links, theater_details, facebook, release_date, social_teaser, facebook_channel',
    ),
    'columns' => array(
        'sys_language_uid' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'items' => array(
                    array(
                        'LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages',
                        -1,
                        'flags-multiple'
                    ),
                ),
                'default' => 0,
            )
        ),
        'l10n_parent' => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('', 0),
                ),
                'foreign_table' => 'tx_events2_domain_model_event',
                'foreign_table_where' => 'AND tx_events2_domain_model_event.pid=###CURRENT_PID### AND tx_events2_domain_model_event.sys_language_uid IN (-1,0)',
            ),
        ),
        'l10n_diffsource' => array(
            'config' => array(
                'type' => 'passthrough',
            ),
        ),
        't3ver_label' => array(
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'max' => 255,
            ),
        ),
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config' => array(
                'type' => 'check',
            ),
        ),
        'starttime' => array(
            'exclude' => 1,
            'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'config' => array(
                'type' => 'input',
                'size' => 13,
                'max' => 20,
                'eval' => 'datetime',
                'checkbox' => 0,
                'default' => 0,
                'range' => array(
                    'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y')),
                ),
            ),
        ),
        'endtime' => array(
            'exclude' => 1,
            'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
            'config' => array(
                'type' => 'input',
                'size' => 13,
                'max' => 20,
                'eval' => 'datetime',
                'checkbox' => 0,
                'default' => 0,
                'range' => array(
                    'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y')),
                ),
            ),
        ),
        'title' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.title',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ),
        ),
        'top_of_list' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.top_of_list',
            'config' => array(
                'type' => 'check',
                'default' => 0,
            ),
        ),
        'teaser' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.teaser',
            'config' => array(
                'type' => 'text',
                'cols' => 30,
                'rows' => 4,
                'eval' => 'trim',
            ),
        ),
        'event_begin' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.event_begin',
            'config' => array(
                'type' => 'input',
                'size' => 7,
                'eval' => 'date,required',
                'default' => mktime(0, 0, 0, date('m'), date('d'), date('Y')),
            ),
        ),
        'event_time' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.event_time',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_time',
                'foreign_field' => 'event',
                'foreign_match_fields' => array(
                    'type' => 'event_time',
                ),
                'minitems' => 0,
                'maxitems' => 1,
                'appearance' => array(
                    'collapseAll' => true,
                    'newRecordLinkTitle' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:createNewRelationForTime',
                    'levelLinksPosition' => 'top',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ),
            ),
        ),
        'event_end' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.event_end',
            'config' => array(
                'type' => 'input',
                'size' => 7,
                'eval' => 'date',
            ),
        ),
        'recurring_event' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.recurring_event',
            'config' => array(
                'type' => 'check',
                'default' => 0,
            ),
        ),
        'same_day' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.same_day',
            'config' => array(
                'type' => 'check',
                'default' => 0,
            ),
        ),
        'multiple_times' => array(
            'exclude' => 1,
            'displayCond' => 'FIELD:same_day:REQ:true',
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.multiple_times',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_time',
                'foreign_field' => 'event',
                'foreign_match_fields' => array(
                    'type' => 'multiple_times',
                ),
                'minitems' => 0,
                'maxitems' => 10,
                'appearance' => array(
                    'collapseAll' => true,
                    'newRecordLinkAddTitle' => true,
                    'levelLinksPosition' => 'top',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ),
            ),
        ),
        'xth' => array(
            'exclude' => 1,
            'displayCond' => 'FIELD:each_weeks:=:0',
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth',
            'config' => array(
                'type' => 'check',
                'cols' => 5,
                'items' => array(
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.first', 'first'),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.second', 'second'),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.third', 'third'),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.fourth', 'fourth'),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.fifth', 'fifth'),
                ),
                'default' => 31,
            ),
        ),
        'weekday' => array(
            'exclude' => 1,
            'displayCond' => 'FIELD:each_weeks:=:0',
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday',
            'config' => array(
                'type' => 'check',
                'cols' => 7,
                'items' => array(
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.monday', 'monday'),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.tuesday', 'tuesday'),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.wednesday', 'wednesday'),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.thursday', 'thursday'),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.friday', 'friday'),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.saturday', 'saturday'),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.sunday', 'sunday'),
                ),
                'default' => 127,
            ),
        ),
        'different_times' => array(
            'exclude' => 1,
            'displayCond' => 'FIELD:each_weeks:=:0',
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.different_times',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_time',
                'foreign_field' => 'event',
                'foreign_types' => array(
                    '1' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1, weekday, time_begin;;2, time_end,--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,starttime, endtime'),
                ),
                'foreign_match_fields' => array(
                    'type' => 'different_times',
                ),
                'minitems' => 0,
                'maxitems' => 7,
                'appearance' => array(
                    'collapseAll' => true,
                    'newRecordLinkAddTitle' => true,
                    'levelLinksPosition' => 'both',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ),
            ),
        ),
        'each_weeks' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_weeks',
            'config' => array(
                'type' => 'radio',
                'items' => array(
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_weeks.0', 0),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_weeks.1', 1),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_weeks.2', 2),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_weeks.3', 3),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.each_weeks.4', 4),
                ),
                'default' => 0,
            ),
        ),
        'exceptions' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.exceptions',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_exception',
                'foreign_field' => 'event',
                'foreign_default_sortby' => 'tx_events2_domain_model_exception.exception_date ASC',
                'maxitems' => 9999,
                'appearance' => array(
                    'collapseAll' => true,
                    'levelLinksPosition' => 'both',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ),
            ),
        ),
        'detail_informations' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.detail_informations',
            'config' => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'wizards' => array(
                    'RTE' => array(
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_rte.gif',
                        'module' => array(
                            'name' => 'wizard_rte',
                        ),
                        'notNewRecords' => 1,
                        'RTEonly' => 1,
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.W.RTE',
                        'type' => 'script',
                    ),
                ),
            ),
            'defaultExtras' => 'richtext:rte_transform[flag=rte_enabled|mode=ts]',
        ),
        'free_entry' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.free_entry',
            'config' => array(
                'type' => 'check',
                'default' => 0,
            ),
        ),
        'ticket_link' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.ticket_link',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_link',
                'maxitems' => 1,
                'minitems' => 0,
                'appearance' => array(
                    'levelLinksPosition' => 'top',
                    'newRecordLinkAddTitle' => true,
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ),
            ),
        ),
        'days' => array(
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'tx_events2_domain_model_day',
                'foreign_sortby' => 'sorting',
                'MM' => 'tx_events2_event_day_mm',
            ),
        ),
        'day' => array(
            'config' => array(
                'type' => 'passthrough',
            ),
        ),
        'location' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.location',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_events2_domain_model_location',
                'foreign_table' => 'tx_events2_domain_model_location',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 1,
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest',
                        'default' => array(
                            'searchWholePhrase' => true,
                        ),
                    ),
                ),
            ),
        ),
        'organizer' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.organizer',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_events2_domain_model_organizer',
                'foreign_table' => 'tx_events2_domain_model_organizer',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 1,
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest',
                        'default' => array(
                            'searchWholePhrase' => true,
                        ),
                    ),
                ),
            ),
        ),
        'images' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.images',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'images',
                array(
                    'minitems' => 0,
                    'maxitems' => 5,
                    'foreign_match_fields' => array(
                        'fieldname' => 'images',
                        'tablenames' => 'tx_events2_domain_model_event',
                        'table_local' => 'sys_file',
                    ),
                )
            ),
        ),
        'video_link' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.video_link',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_link',
                'maxitems' => 1,
                'minitems' => 0,
                'appearance' => array(
                    'levelLinksPosition' => 'top',
                    'newRecordLinkAddTitle' => true,
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ),
            ),
        ),
        'download_links' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.download_links',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_link',
                'maxitems' => 1,
                'minitems' => 0,
                'appearance' => array(
                    'levelLinksPosition' => 'both',
                    'newRecordLinkAddTitle' => true,
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ),
            ),
        ),
        'facebook' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.facebook',
            'config' => array(
                'type' => 'check',
                'default' => false,
            ),
        ),
        'release_date' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.release_date',
            'config' => array(
                'type' => 'input',
                'size' => 7,
                'eval' => 'date',
                'checkbox' => 1,
                'default' => 0,
            ),
        ),
        'social_teaser' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.social_teaser',
            'config' => array(
                'type' => 'text',
                'cols' => 30,
                'rows' => 4,
                'eval' => 'trim',
            ),
        ),
        'facebook_channel' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.facebook_channel',
            'config' => array(
                'type' => 'check',
                'items' => array(
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.facebook_channel.pforzheim', 'pforzheim.de'),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.facebook_channel.theater', 'theater-pforzheim'),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.facebook_channel.bibliothek', 'stadtbibliothek'),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.facebook_channel.marketing', 'citymarketing'),
                ),
                'default' => '',
            ),
        ),
        'theater_details' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.theater_details',
            'config' => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'wizards' => array(
                    'RTE' => array(
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_rte.gif',
                        'module' => array(
                            'name' => 'wizard_rte',
                        ),
                        'notNewRecords' => 1,
                        'RTEonly' => 1,
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.W.RTE',
                        'type' => 'script',
                    ),
                ),
            ),
            'defaultExtras' => 'richtext:rte_transform[flag=rte_enabled|mode=ts]',
        ),
    ),
    'types' => array(
        '1' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1, title, top_of_list, teaser, event_begin, event_time, event_end, --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.recurring_event, recurring_event, same_day, multiple_times, xth, weekday, different_times, each_weeks, --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.exceptions, exceptions, --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.event_details, detail_informations, free_entry, ticket_link, alternative_times, location, organizer, --div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.media;newline, images, video_link, download_links, theater_details,--div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.social, facebook, release_date, social_teaser, facebook_channel,--div--;' . $ttContentLanguageFile . ':tabs.access,starttime, endtime'),
    ),
    'palettes' => array(
        '1' => array('showitem' => ''),
    ),
);
unset($ttContentLanguageFile);