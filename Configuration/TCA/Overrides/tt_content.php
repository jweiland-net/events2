<?php

declare(strict_types=1);

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
    'FILE:EXT:events2/Configuration/FlexForms/Events.xml',
);

ExtensionUtility::registerPlugin(
    'Events2',
    'Show',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.show.title',
    'ext-events2-wizard-icon',
    'plugins',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.show.description',
    'FILE:EXT:events2/Configuration/FlexForms/Show.xml',
);

ExtensionUtility::registerPlugin(
    'Events2',
    'Management',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.management.title',
    'ext-events2-wizard-icon',
    'plugins',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.management.description',
    'FILE:EXT:events2/Configuration/FlexForms/Management.xml',
);

ExtensionUtility::registerPlugin(
    'Events2',
    'Calendar',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.calendar.title',
    'ext-events2-wizard-icon',
    'plugins',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.calendar.description',
    'FILE:EXT:events2/Configuration/FlexForms/Calendar.xml',
);

ExtensionUtility::registerPlugin(
    'Events2',
    'SearchForm',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.searchForm.title',
    'ext-events2-wizard-icon',
    'plugins',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.searchForm.description',
    'FILE:EXT:events2/Configuration/FlexForms/SearchForm.xml',
);

ExtensionUtility::registerPlugin(
    'Events2',
    'SearchResults',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.searchResults.title',
    'ext-events2-wizard-icon',
    'plugins',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.searchResults.description',
    'FILE:EXT:events2/Configuration/FlexForms/SearchResults.xml',
);

ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    'pages',
    'events2_list,events2_show,events2_management,events2_calendar,events2_searchform,events2_searchresults',
    'after:pi_flexform',
);
