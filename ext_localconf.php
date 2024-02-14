<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(static function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'JWeiland.events2',
        'Events',
        [
            'Day' => 'list, listLatest, listToday, listWeek, listRange, listSearchResults, show, showByTimestamp',
            'Event' => 'listMyEvents, perform, new, create, edit, update, delete, activate',
            'Location' => 'show',
            'Video' => 'show',
        ],
        // non-cacheable actions
        [
            'Day' => 'listSearchResults',
            'Event' => 'listMyEvents, perform, create, edit, update, delete, activate',
        ]
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'JWeiland.events2',
        'Calendar',
        [
            'Calendar' => 'show',
        ],
        // non-cacheable actions
        [
            'Calendar' => 'show',
        ]
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'JWeiland.events2',
        'Search',
        [
            'Search' => 'show',
            'Ajax' => 'callAjaxObject',
        ],
        // non-cacheable actions
        [
            'Search' => 'show',
            'Ajax' => 'callAjaxObject',
        ]
    );

    // register an eval function to check for time
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][\JWeiland\Events2\Tca\Type\Time::class] = '';
    // delete and recreate day relations for an event while saving
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['events2_createDayRelations'] = \JWeiland\Events2\Hooks\DataHandler::class;
    // Clear cache of pages with events, if event was edited/created/deleted in BE
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['events2_clearcache'] = \JWeiland\Events2\Hooks\DataHandler::class . '->clearCachePostProc';

    // Prefill EXT:form element of type Checkboxes with categories from database
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['initializeFormElement'][1660727041]
        = \JWeiland\Events2\Hooks\Form\PrefillCategoriesHook::class;
    // Set values from request as default values for edit usage
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterBuildingFinished'][1662039121]
        = \JWeiland\Events2\Hooks\Form\PrefillForEditUsageHook::class;
    // Prefill location label, if submitted before
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeRendering'][1663590603]
        = \JWeiland\Events2\Hooks\Form\PrefillLocationHook::class;

    // create scheduler to create/update days with recurrency
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\JWeiland\Events2\Task\ReGenerateDays::class] = [
        'extension' => 'events2',
        'title' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:task.reCreateDays.title',
        'description' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:task.reCreateDays.description',
        'additionalFields' => JWeiland\Events2\Task\ReGenerateDays::class,
    ];

    // create scheduler to import events from different sources
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\JWeiland\Events2\Task\Import::class] = [
        'extension' => 'events2',
        'title' => 'Import events',
        'description' => 'Import events over a XML interface or by mail into events2.',
        'additionalFields' => \JWeiland\Events2\Task\AdditionalFieldsForImport::class,
    ];

    // Register SVG Icon Identifier
    $svgIcons = [
        'ext-events2-wizard-icon' => 'plugin_wizard.svg',
    ];
    // Register Bitmap Icon Identifier
    $bmpIcons = [
        'ext-events2-calendar-single' => 'calendar_single.png',
        'ext-events2-calendar-recurring' => 'calendar_recurring.png',
        'ext-events2-calendar-duration' => 'calendar_duration.png',
        'ext-events2-exception-add' => 'exception_add.png',
        'ext-events2-exception-remove' => 'exception_remove.png',
        'ext-events2-exception-info' => 'exception_info.png',
        'ext-events2-exception-time' => 'exception_time.png',
    ];
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    foreach ($svgIcons as $identifier => $fileName) {
        $iconRegistry->registerIcon(
            $identifier,
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            ['source' => 'EXT:events2/Resources/Public/Icons/' . $fileName]
        );
    }
    foreach ($bmpIcons as $identifier => $fileName) {
        $iconRegistry->registerIcon(
            $identifier,
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:events2/Resources/Public/Icons/' . $fileName]
        );
    }

    // Add events2 plugin to new element wizard
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:events2/Configuration/TSconfig/ContentElementWizard.txt">');

    // register eID scripts
    $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['events2findDaysForMonth'] = \JWeiland\Events2\Ajax\FindDaysForMonth::class . '::processRequest';
    $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['events2findLocations'] = \JWeiland\Events2\Ajax\FindLocations::class . '::processRequest';

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['TimestampMapper'] = \JWeiland\Events2\Routing\Aspect\TimestampMapper::class;

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('solr')) {
        // Remove non current events from resultSet
        // Add nextDay field to SearchResult object
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['afterSearch']['events2_addNextDay'] = \JWeiland\Events2\Hooks\Solr\ResultsCommandHook::class;
        // As we can't create a SQL Query with JOIN in Solr configuration, we have to remove invalid documents on our own
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['IndexQueueIndexer']['preAddModifyDocuments']['events2_removeInvalidDocs'] = \JWeiland\Events2\Hooks\Solr\IndexerHook::class;
    }

    // Prefill event_begin with current Timestamp
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][\JWeiland\Events2\Backend\FormDataProvider\InitializeNewEventRecord::class] = [
        'depends' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
        ]
    ];
    // Set rootUid of category trees in FlexForms to values of extension configuration
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['flexFormSegment'][\JWeiland\Events2\Backend\FormDataProvider\ModifyRootUidOfTreeSelectElements::class] = [
        'depends' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class,
        ]
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['events2UpdateSlug']
        = \JWeiland\Events2\Updater\EventsSlugUpdater::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['events2MigrateOrganizer']
        = \JWeiland\Events2\Updater\MigrateOrganizerToMMUpdater::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['events2MigrateDetailInformations']
        = \JWeiland\Events2\Updater\MigrateDetailInformationsUpdater::class;
});
