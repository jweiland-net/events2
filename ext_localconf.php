<?php
if (!defined('TYPO3')) {
    die('Access denied.');
}

call_user_func(static function (): void {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Events2',
        'List',
        [
            \JWeiland\Events2\Controller\DayController::class => 'list, show',
            \JWeiland\Events2\Controller\LocationController::class => 'show',
            \JWeiland\Events2\Controller\VideoController::class => 'show',
            \JWeiland\Events2\Controller\ICalController::class => 'download',
        ]
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Events2',
        'Show',
        [
            \JWeiland\Events2\Controller\DayController::class => 'show',
            \JWeiland\Events2\Controller\LocationController::class => 'show',
            \JWeiland\Events2\Controller\VideoController::class => 'show',
            \JWeiland\Events2\Controller\ICalController::class => 'download',
        ]
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Events2',
        'Management',
        [
            \JWeiland\Events2\Controller\ManagementController::class => 'listMyEvents, new, create, edit, update, perform, delete, activate',
        ],
        // non-cacheable actions
        [
            \JWeiland\Events2\Controller\ManagementController::class => 'create, edit, update, perform, delete, activate',
        ]
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Events2',
        'Calendar',
        [
            \JWeiland\Events2\Controller\CalendarController::class => 'show',
        ]
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Events2',
        'SearchForm',
        [
            \JWeiland\Events2\Controller\SearchController::class => 'show',
        ],
        // Needs to be uncached, to show dynamic search values again after reload.
        [
            \JWeiland\Events2\Controller\SearchController::class => 'show',
        ]
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Events2',
        'SearchResults',
        [
            \JWeiland\Events2\Controller\SearchController::class => 'listSearchResults',
        ],
        // needs to be uncached to show fresh search results
        [
            \JWeiland\Events2\Controller\SearchController::class => 'listSearchResults',
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
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:events2/Configuration/TSconfig/ContentElementWizard.tsconfig">'
    );

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['TimestampMapper'] = \JWeiland\Events2\Routing\Aspect\TimestampMapper::class;

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('solr')) {
        $typo3Version = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Information\Typo3Version::class
        );

        // Remove non current events from resultSet
        // Add nextDay field to SearchResult object
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['afterSearch']['events2_addNextDay']
            = \JWeiland\Events2\Hooks\Solr\ResultsCommandHook::class;

        // As we can't create a SQL Query with JOIN in Solr configuration, we have to remove invalid documents on our own
        if (version_compare($typo3Version->getBranch(), '11.4', '>=')) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['IndexQueueIndexer']['preAddModifyDocuments']['events2_removeInvalidDocs']
                = \JWeiland\Events2\Hooks\Solr\IndexerHook::class;
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['IndexQueueIndexer']['preAddModifyDocuments']['events2_removeInvalidDocs']
                = \JWeiland\Events2\Hooks\Solr\IndexerHook104::class;
        }
    }

    // Prefill event_begin with current Timestamp
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][\JWeiland\Events2\Backend\FormDataProvider\InitializeNewEventRecord::class] = [
        'depends' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
        ],
    ];
    // Set rootUid of category trees in FlexForms to values of extension configuration
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['flexFormSegment'][\JWeiland\Events2\Backend\FormDataProvider\ModifyRootUidOfTreeSelectElements::class] = [
        'depends' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class,
        ],
    ];

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\JWeiland\Events2\Property\TypeConverter\DateTimeImmutableConverter::class);

    $GLOBALS['TYPO3_CONF_VARS']['LOG']['JWeiland']['Events2']['writerConfiguration'] = [
        \TYPO3\CMS\Core\Log\LogLevel::WARNING => [
            \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                'logFileInfix' => 'events2',
            ],
        ],
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['events2UpdateSlug']
        = \JWeiland\Events2\Upgrade\EventsSlugUpgrade::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['events2MigrateOrganizer']
        = \JWeiland\Events2\Upgrade\MigrateOrganizerToMMUpgrade::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['events2MigrateDetailInformations']
        = \JWeiland\Events2\Upgrade\MigrateDetailInformationsUpgrade::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['events2MoveFlexFormFields']
        = \JWeiland\Events2\Upgrade\MoveOldFlexFormSettingsUpgrade::class;
});
