<?php
return array(
	'ctrl' => array(
		'title'	=> 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time',
		'label_userFunc' => 'JWeiland\\Events2\\Tca\\TimeLabel->getTitle',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'dividers2tabs' => TRUE,
		'hideTable' => TRUE,

		'versioningWS' => 2,
		'versioning_followPages' => TRUE,
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
		'searchFields' => '',
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('events2') . 'Configuration/TCA/Time.php',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('events2') . 'Resources/Public/Icons/tx_events2_domain_model_time.gif'
	),
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, type, weekday, time_begin, time_entry, duration, time_end',
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
					array('LLL:EXT:lang/locallang_general.xlf:LGL.default_value', 0)
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
				'foreign_table' => 'tx_events2_domain_model_time',
				'foreign_table_where' => 'AND tx_events2_domain_model_time.pid=###CURRENT_PID### AND tx_events2_domain_model_time.sys_language_uid IN (-1,0)',
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
			)
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
					'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y'))
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
					'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y'))
				),
			),
		),
		'type' => array(
			'config' => array(
				'type' => 'passthrough',
			),
		),
		'weekday' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.weekday',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.weekday.monday', 'monday'),
					array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.weekday.tuesday', 'tuesday'),
					array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.weekday.wednesday', 'wednesday'),
					array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.weekday.thursday', 'thursday'),
					array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.weekday.friday', 'friday'),
					array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.weekday.saturday', 'saturday'),
					array('LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.weekday.sunday', 'sunday'),
				),
				'eval' => 'required',
				'default' => strtolower(date('l')),
			),
		),
		'time_begin' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.time_begin',
			'config' => array(
				'type' => 'input',
				'size' => 5,
				'max' => 5,
				'checkbox' => 1,
				'default' => '8:00',
				'eval' => 'Tx_Events2_Tca_Type_Time'
			),
		),
		'time_entry' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.time_entry',
			'config' => array(
				'type' => 'input',
				'size' => 5,
				'max' => 5,
				'checkbox' => 1,
				'default' => '',
				'eval' => 'Tx_Events2_Tca_Type_Time'
			),
		),
		'duration' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.duration',
			'config' => array(
				'type' => 'input',
				'size' => 5,
				'max' => 5,
				'checkbox' => 1,
				'default' => '',
				'eval' => 'Tx_Events2_Tca_Type_Time'
			),
		),
		'time_end' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_time.time_end',
			'config' => array(
				'type' => 'input',
				'size' => 5,
				'max' => 5,
				'checkbox' => 1,
				'default' => '',
				'eval' => 'Tx_Events2_Tca_Type_Time'
			),
		),
		'event' => array(
			'config' => array(
				'type' => 'passthrough',
			),
		),
	),
	'types' => array(
		'1' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1, type, time_begin;;2, duration, time_end,--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,starttime, endtime'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
		'2' => array('showitem' => 'time_entry'),
	),
);