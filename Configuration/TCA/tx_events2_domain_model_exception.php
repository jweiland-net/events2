<?php
return array(
    'ctrl' => array(
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
        'searchFields' => 'exception_details',
        'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('events2').'Resources/Public/Icons/tx_events2_domain_model_exception.gif',
    ),
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, exception_type, exception_date, exception_time, exception_details',
    ),
    'columns' => array(
        'sys_language_uid' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => array(
                    array('LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages', -1),
                    array('LLL:EXT:lang/locallang_general.xlf:LGL.default_value', 0),
                ),
            ),
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
                'foreign_table' => 'tx_events2_domain_model_exception',
                'foreign_table_where' => 'AND tx_events2_domain_model_exception.pid=###CURRENT_PID### AND tx_events2_domain_model_exception.sys_language_uid IN (-1,0)',
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
        'exception_type' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_exception.exception_type',
            'config' => array(
                'type' => 'select',
                'size' => 1,
                'items' => array(
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_exception.exception_type.Add', 'Add'),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_exception.exception_type.Remove', 'Remove'),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_exception.exception_type.Time', 'Time'),
                    array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_exception.exception_type.Info', 'Info'),
                ),
                'default' => 'Add',
            ),
        ),
        'exception_date' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_exception.exception_date',
            'config' => array(
                'type' => 'input',
                'size' => 7,
                'eval' => 'date, required',
                'checkbox' => 1,
                'default' => time(),
            ),
        ),
        'exception_time' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_exception.exception_time',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_time',
                'foreign_field' => 'exception',
                'foreign_match_fields' => array(
                    '	type' => 'exception_time',
                ),
                'minitems' => 0,
                'maxitems' => 1,
                'appearance' => array(
                    'collapseAll' => true,
                    'levelLinksPosition' => 'top',
                    'newRecordLinkAddTitle' => true,
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ),
            ),
        ),
        'exception_details' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_exception.exception_details',
            'config' => array(
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ),
        ),
        'event' => array(
            'config' => array(
                'type' => 'passthrough',
            ),
        ),
    ),
    'types' => array(
        'Add' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1, exception_type, exception_date, exception_time, exception_details,--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,starttime, endtime'),
        'Remove' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1, exception_type, exception_date,--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,starttime, endtime'),
        'Time' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1, exception_type, exception_date, exception_time, exception_details,--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,starttime, endtime'),
        'Info' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1, exception_type, exception_date, exception_details,--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,starttime, endtime'),
    ),
    'palettes' => array(
        '1' => array('showitem' => ''),
    ),
);
