<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'JWeiland.' . $_EXTKEY,
	'Events',
	array(
		'Event' => 'list, listLatest, listToday, listRange, listSearchResults, show, new, create, edit, update, delete',
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
		'Calendar' => '',
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

// here we register an eval function to check for time
if(TYPO3_MODE == 'BE') {
	$TYPO3_CONF_VARS['SC_OPTIONS']['tce']['formevals']['Tx_Events2_Tca_Type_Time'] = 'EXT:events2/Classes/Tca/Type/Time.php';
}

// This was needed to get this ext working with RealUrl
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_events2_events[location]';

// hook to automatically add/update related days
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'JWeiland\\Events2\\Tca\\CreateUpdateDays';