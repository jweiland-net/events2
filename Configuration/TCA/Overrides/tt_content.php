<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

if (!defined('TYPO3')) {
    die('Access denied.');
}

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['events2_list'] = 'select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['events2_list'] = 'pi_flexform';
ExtensionManagementUtility::addPiFlexFormValue(
    'events2_list',
    'FILE:EXT:events2/Configuration/FlexForms/Events.xml',
);

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['events2_show'] = 'select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['events2_show'] = 'pi_flexform';
ExtensionManagementUtility::addPiFlexFormValue(
    'events2_show',
    'FILE:EXT:events2/Configuration/FlexForms/Show.xml',
);

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['events2_management'] = 'select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['events2_management'] = 'pi_flexform';
ExtensionManagementUtility::addPiFlexFormValue(
    'events2_management',
    'FILE:EXT:events2/Configuration/FlexForms/Management.xml',
);

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['events2_calendar'] = 'select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['events2_calendar'] = 'pi_flexform';
ExtensionManagementUtility::addPiFlexFormValue(
    'events2_calendar',
    'FILE:EXT:events2/Configuration/FlexForms/Calendar.xml',
);

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['events2_searchform'] = 'select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['events2_searchform'] = 'pi_flexform';
ExtensionManagementUtility::addPiFlexFormValue(
    'events2_searchform',
    'FILE:EXT:events2/Configuration/FlexForms/SearchForm.xml',
);

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['events2_searchresults'] = 'select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['events2_searchresults'] = 'pi_flexform';
ExtensionManagementUtility::addPiFlexFormValue(
    'events2_searchresults',
    'FILE:EXT:events2/Configuration/FlexForms/SearchResults.xml',
);

ExtensionUtility::registerPlugin(
    'Events2',
    'List',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.list.title',
);

ExtensionUtility::registerPlugin(
    'Events2',
    'Show',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.show.title',
);

ExtensionUtility::registerPlugin(
    'Events2',
    'Management',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.management.title',
);

ExtensionUtility::registerPlugin(
    'Events2',
    'Calendar',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.calendar.title',
);

ExtensionUtility::registerPlugin(
    'Events2',
    'SearchForm',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.searchForm.title',
);

ExtensionUtility::registerPlugin(
    'Events2',
    'SearchResults',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.searchResults.title',
);
