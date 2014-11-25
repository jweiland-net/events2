<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'JWeiland.' . $_EXTKEY,
	'Events',
	array(
		'Event' => 'list, listLatest, listToday, listRange, listSearchResults, listMyEvents, show, new, create, edit, update, delete',
		'Day' => 'list, show',
		'Location' => 'show',
		'Video' => 'show',
		'Ajax' => 'callAjaxObject',
	),
	// non-cacheable actions
	array(
		'Event' => 'listSearchResults, create, update, delete',
		'Ajax' => 'callAjaxObject',
	)
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'JWeiland.' . $_EXTKEY,
	'Calendar',
	array(
		'Calendar' => 'show',
	),
	// non-cacheable actions
	array(
		'Calendar' => 'show',
	)
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'JWeiland.' . $_EXTKEY,
	'Search',
	array(
		'Search' => 'show',
	),
	// non-cacheable actions
	array(
		'Search' => 'show',
	)
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'JWeiland.' . $_EXTKEY,
	'Culture',
	array(
		'Culture' => 'list, search',
	),
	// non-cacheable actions
	array(
		'Culture' => 'search',
	)
);

if (TYPO3_MODE === 'BE') {
	// repair records of events2
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'JWeiland\\Events2\\Command\\RepairCommandController';
	// here we register an eval function to check for time
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['Tx_Events2_Tca_Type_Time'] = 'EXT:events2/Classes/Tca/Type/Time.php';
	// use hook to automatically add a map record to event
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'JWeiland\\Events2\\Hooks\\CreateMap';
	// hook to automatically add/update related days
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'JWeiland\\Events2\\Tca\\CreateUpdateDays';
	// HOOK: Delete relations of deleted events
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = 'JWeiland\\Events2\\Tca\\DeleteDayRelations';
	// HOOK: Override rootUid in TCA for category trees
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass'][] = 'JWeiland\\Events2\\Hooks\\ModifyTcaOfCategoryTrees';
}

// register eID scripts
$TYPO3_CONF_VARS['FE']['eID_include']['events2findDaysForMonth'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('events2') . 'Classes/Ajax/FindDaysForMonth.php';
$TYPO3_CONF_VARS['FE']['eID_include']['events2findLocations'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('events2') . 'Classes/Ajax/FindLocations.php';