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

use JWeiland\Events2\Backend\FormDataProvider\InitializeNewEventRecord;
use JWeiland\Events2\Backend\FormDataProvider\ModifyRootUidOfTreeSelectElements;
use JWeiland\Events2\Controller\CalendarController;
use JWeiland\Events2\Controller\DayController;
use JWeiland\Events2\Controller\ICalController;
use JWeiland\Events2\Controller\LocationController;
use JWeiland\Events2\Controller\ManagementController;
use JWeiland\Events2\Controller\SearchController;
use JWeiland\Events2\Controller\VideoController;
use JWeiland\Events2\Hook\Events2DataHandler;
use JWeiland\Events2\Hook\Form\PrefillCategoriesHook;
use JWeiland\Events2\Hook\Form\PrefillForEditUsageHook;
use JWeiland\Events2\Hook\Solr\IndexerHook;
use JWeiland\Events2\Hook\Solr\ResultsCommandHook;
use JWeiland\Events2\Routing\Aspect\PersistedHiddenAliasMapper;
use JWeiland\Events2\Routing\Aspect\TimestampMapper;
use JWeiland\Events2\Task\AdditionalFieldsForImport;
use JWeiland\Events2\Task\Import;
use JWeiland\Events2\Task\ReGenerateDays;
use JWeiland\Events2\Tca\Type\Time;
use JWeiland\Events2\Upgrade\EventsSlugUpgrade;
use JWeiland\Events2\Upgrade\MigrateDetailInformationsUpgrade;
use JWeiland\Events2\Upgrade\MigrateOrganizerToMMUpgrade;
use JWeiland\Events2\Upgrade\MoveOldFlexFormSettingsUpgrade;
use Psr\Log\LogLevel;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionUtility::configurePlugin(
    'Events2',
    'List',
    [
        DayController::class => 'list, show',
        LocationController::class => 'show',
        VideoController::class => 'show',
        ICalController::class => 'download',
    ],
    [],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

ExtensionUtility::configurePlugin(
    'Events2',
    'Show',
    [
        DayController::class => 'show',
        LocationController::class => 'show',
        VideoController::class => 'show',
        ICalController::class => 'download',
    ],
    [],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

ExtensionUtility::configurePlugin(
    'Events2',
    'Management',
    [
        ManagementController::class => 'listMyEvents, new, create, edit, update, perform, delete, activate',
    ],
    // non-cacheable actions
    [
        ManagementController::class => 'listMyEvents, create, edit, update, perform, delete, activate',
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

ExtensionUtility::configurePlugin(
    'Events2',
    'Calendar',
    [
        CalendarController::class => 'show',
    ],
    [],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

ExtensionUtility::configurePlugin(
    'Events2',
    'SearchForm',
    [
        SearchController::class => 'show',
    ],
    // Needs to be uncached, to show dynamic search values again after reload.
    [
        SearchController::class => 'show',
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

ExtensionUtility::configurePlugin(
    'Events2',
    'SearchResults',
    [
        SearchController::class => 'listSearchResults',
    ],
    // needs to be uncached to show fresh search results
    [
        SearchController::class => 'listSearchResults',
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

// register an eval function to check for time
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][Time::class] = '';
// delete and recreate day relations for an event while saving
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['events2_createDayRelations'] = Events2DataHandler::class;
// Clear the cache of pages with events if the event was edited/created/deleted in BE
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['events2_clearcache'] = Events2DataHandler::class . '->clearCachePostProc';

// Prefill EXT:form element of type Checkboxes with categories from a database
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['initializeFormElement'][1660727041]
    = PrefillCategoriesHook::class;
// Set values from request as default values for edit usage
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterBuildingFinished'][1662039121]
    = PrefillForEditUsageHook::class;

// create scheduler to create/update days with recurrency
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][ReGenerateDays::class] = [
    'extension' => 'events2',
    'title' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:task.reCreateDays.title',
    'description' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:task.reCreateDays.description',
    'additionalFields' => ReGenerateDays::class,
];

// create scheduler to import events from different sources
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][Import::class] = [
    'extension' => 'events2',
    'title' => 'Import events',
    'description' => 'Import events over a XML interface or by mail into events2.',
    'additionalFields' => AdditionalFieldsForImport::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['TimestampMapper'] = TimestampMapper::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['PersistedHiddenAliasMapper'] = PersistedHiddenAliasMapper::class;

if (ExtensionManagementUtility::isLoaded('solr')) {
    // Remove non-current events from resultSet
    // Add nextDay field to SearchResult object
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['afterSearch']['events2_addNextDay']
        = ResultsCommandHook::class;

    // As we can't create a SQL Query with JOIN in Solr configuration, we have to remove invalid documents on our own
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['IndexQueueIndexer']['preAddModifyDocuments']['events2_removeInvalidDocs']
        = IndexerHook::class;
}

// Prefill event_begin with the current Timestamp
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][InitializeNewEventRecord::class] = [
    'depends' => [
        DatabaseRowInitializeNew::class,
    ],
];
// Set rootUid of category trees in FlexForms to values of extension configuration
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['flexFormSegment'][ModifyRootUidOfTreeSelectElements::class] = [
    'depends' => [
        TcaSelectItems::class,
    ],
];

$GLOBALS['TYPO3_CONF_VARS']['LOG']['JWeiland']['Events2']['writerConfiguration'] = [
    LogLevel::WARNING => [
        FileWriter::class => [
            'logFileInfix' => 'events2',
        ],
    ],
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['events2UpdateSlug']
    = EventsSlugUpgrade::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['events2MigrateOrganizer']
    = MigrateOrganizerToMMUpgrade::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['events2MigrateDetailInformations']
    = MigrateDetailInformationsUpgrade::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['events2MoveFlexFormFields']
    = MoveOldFlexFormSettingsUpgrade::class;
