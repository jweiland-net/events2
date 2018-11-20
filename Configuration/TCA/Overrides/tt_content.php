<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['events2_events'] = 'select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['events2_events'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'events2_events',
    'FILE:EXT:events2/Configuration/FlexForms/Events.xml'
);

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['events2_calendar'] = 'select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['events2_calendar'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'events2_calendar',
    'FILE:EXT:events2/Configuration/FlexForms/Calendar.xml'
);

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['events2_search'] = 'select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['events2_search'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'events2_search',
    'FILE:EXT:events2/Configuration/FlexForms/Search.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'JWeiland.events2',
    'Events',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.events.title'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'JWeiland.events2',
    'Calendar',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.calendar.title'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'JWeiland.events2',
    'Search',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.search.title'
);
