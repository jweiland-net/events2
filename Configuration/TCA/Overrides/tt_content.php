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

ExtensionUtility::registerPlugin(
    'Events2',
    'List',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.list.title',
    'ext-events2-wizard-icon',
    'plugins',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.list.description',
);

ExtensionUtility::registerPlugin(
    'Events2',
    'Show',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.show.title',
    'ext-events2-wizard-icon',
    'plugins',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.show.description',
);

ExtensionUtility::registerPlugin(
    'Events2',
    'Management',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.management.title',
    'ext-events2-wizard-icon',
    'plugins',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.management.description',
);

ExtensionUtility::registerPlugin(
    'Events2',
    'Calendar',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.calendar.title',
    'ext-events2-wizard-icon',
    'plugins',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.calendar.description',
);

ExtensionUtility::registerPlugin(
    'Events2',
    'SearchForm',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.searchForm.title',
    'ext-events2-wizard-icon',
    'plugins',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.searchForm.description',
);

ExtensionUtility::registerPlugin(
    'Events2',
    'SearchResults',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.searchResults.title',
    'ext-events2-wizard-icon',
    'plugins',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.searchResults.description',
);

ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;Configuration,pi_flexform',
    'events2_list',
    'after:subheader',
);
ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:events2/Configuration/FlexForms/Events.xml',
    'events2_list',
);

ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;Configuration,pi_flexform',
    'events2_show',
    'after:subheader',
);
ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:events2/Configuration/FlexForms/Show.xml',
    'events2_show',
);

ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;Configuration,pi_flexform',
    'events2_management',
    'after:subheader',
);
ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:events2/Configuration/FlexForms/Management.xml',
    'events2_management',
);

ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;Configuration,pi_flexform',
    'events2_calendar',
    'after:subheader',
);
ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:events2/Configuration/FlexForms/Calendar.xml',
    'events2_calendar',
);

ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;Configuration,pi_flexform',
    'events2_searchform',
    'after:subheader',
);
ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:events2/Configuration/FlexForms/SearchForm.xml',
    'events2_searchform',
);

ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;Configuration,pi_flexform',
    'events2_searchresults',
    'after:subheader',
);
ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:events2/Configuration/FlexForms/SearchResults.xml',
    'events2_searchresults',
);
