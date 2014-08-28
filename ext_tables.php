<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
$extensionName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($_EXTKEY);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	'JWeiland.' . $_EXTKEY,
	'Events',
	'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.events.title'
);
$pluginSignature = strtolower($extensionName) . '_events';
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/Events.xml');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	'JWeiland.' . $_EXTKEY,
	'Calendar',
	'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.calendar.title'
);
$pluginSignature = strtolower($extensionName) . '_calendar';
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/Calendar.xml');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	'JWeiland.' . $_EXTKEY,
	'Search',
	'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.search.title'
);
$pluginSignature = strtolower($extensionName) . '_search';
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/Search.xml');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	'JWeiland.' . $_EXTKEY,
	'Culture',
	'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.culture.title'
);
$pluginSignature = strtolower($extensionName) . '_culture';
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/Culture.xml');

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		$_EXTKEY,
		'web',	// Make module a submodule of 'web'
		'events',	// Submodule key
		'',	// Position
		array(
			'Release' => 'list',
		),
		array(
			'access' => 'user,group',
			'icon'   => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_release.xlf',
		)
	);
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Events');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_events2_domain_model_event', 'EXT:events2/Resources/Private/Language/locallang_csh_tx_events2_domain_model_event.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_events2_domain_model_event');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_events2_domain_model_day', 'EXT:events2/Resources/Private/Language/locallang_csh_tx_events2_domain_model_day.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_events2_domain_model_day');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_events2_domain_model_time', 'EXT:events2/Resources/Private/Language/locallang_csh_tx_events2_domain_model_time.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_events2_domain_model_time');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_events2_domain_model_exception', 'EXT:events2/Resources/Private/Language/locallang_csh_tx_events2_domain_model_exception.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_events2_domain_model_exception');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_events2_domain_model_location', 'EXT:events2/Resources/Private/Language/locallang_csh_tx_events2_domain_model_location.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_events2_domain_model_location');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_events2_domain_model_organizer', 'EXT:events2/Resources/Private/Language/locallang_csh_tx_events2_domain_model_organizer.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_events2_domain_model_organizer');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_events2_domain_model_link', 'EXT:events2/Resources/Private/Language/locallang_csh_tx_events2_domain_model_link.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_events2_domain_model_link');

$tmp_feusers_columns = array(
	'tx_events2_organizer' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_organizer',
		'config' => array (
			'type' => 'group',
			'internal_type' => 'db',
			'allowed' => 'tx_events2_domain_model_organizer',
			'prepend_tname' => FALSE,
			'show_thumbs' => FALSE,
			'size' => 1,
			'maxitems' => 1,
			'wizards' => array(
				'suggest' => array(
					'type' => 'suggest',
					'default' => array(
						'searchWholePhrase' => TRUE
					),
				),
			),
		),
	),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $tmp_feusers_columns);
$TCA['fe_users']['types']['0']['showitem'] .= ',--div--;LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_tab,';
$TCA['fe_users']['types']['0']['showitem'] .= 'tx_events2_organizer';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable(
	$_EXTKEY,
	'tx_events2_domain_model_event',
	'categories',
	array(
		'fieldConfiguration' => array(
			'foreign_table_where' => ' AND sys_category.sys_language_uid IN (-1, 0) ORDER BY sys_category.title ASC',
		)
	)
);

$extConf = unserialize($_EXTCONF);
$tsConfig = 'ext.events2.pid = ' . (integer) $extConf['poiCollectionPid'];
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig($tsConfig);