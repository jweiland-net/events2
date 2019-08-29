<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$boot = function ($extKey) {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'JWeiland.' . $extKey,
        'Events',
        [
            'Day' => 'list, listLatest, listToday, listWeek, listRange, show, showByTimestamp',
            'Event' => 'listSearchResults, listMyEvents, new, create, edit, update, delete, activate',
            'Location' => 'show',
            'Video' => 'show',
        ],
        // non-cacheable actions
        [
            'Event' => 'listSearchResults, create, update, delete, activate',
        ]
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'JWeiland.' . $extKey,
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
        'JWeiland.' . $extKey,
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

    // Add command to truncate day table and recreate day records from scratch
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \JWeiland\Events2\Command\RepairCommandController::class;
    // register an eval function to check for time
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][\JWeiland\Events2\Tca\Type\Time::class] = 'EXT:events2/Classes/Tca/Type/Time.php';
    // delete and recreate day relations for an event while saving
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \JWeiland\Events2\Hooks\RecreateDayRelationsHook::class;
    // HOOK: Override rootUid in TCA for category trees
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass'][] = \JWeiland\Events2\Hooks\ModifyTcaOfCategoryTrees::class;
    // Hook: Render Plugin preview item
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['events2_events'][] = \JWeiland\Events2\Hooks\RenderPluginItem::class . '->render';

    // create scheduler to create/update days with recurrency
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\JWeiland\Events2\Task\ReGenerateDays::class] = [
        'extension' => $extKey,
        'title' => 'Create/Update Days',
        'description' => 'Re-Generate day records for events with recurrency. It also deletes old iCAL downloads.',
        'additionalFields' => 'JWeiland\\Events2\\Task\\ReGenerateDays',
    ];

    // create scheduler to import events from different sources
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\JWeiland\Events2\Task\Import::class] = [
        'extension' => $extKey,
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

    // add maps2 plugin to new element wizard
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:events2/Configuration/TSconfig/ContentElementWizard.txt">');

    // register eID scripts
    $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['events2findDaysForMonth'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('events2') . 'Classes/Ajax/FindDaysForMonth.php';
    $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['events2findLocations'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('events2') . 'Classes/Ajax/FindLocations.php';

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('realurl')) {
        // RealUrl auto configuration
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/realurl/class.tx_realurl_autoconfgen.php']['extensionConfiguration']['events2'] = 'JWeiland\\Events2\\Hooks\\RealUrlAutoConfiguration->addEvents2Config';
    }

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('solr')) {
        // Remove non current events from resultSet
        // Add nextDay field to SearchResult object
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['afterSearch'][] = \JWeiland\Events2\Hooks\Solr\ResultsCommandHook::class;
        // As we can't create a SQL Query with JOIN in Solr configuration, we have to remove invalid documents on our own
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['IndexQueueIndexer']['preAddModifyDocuments'][] = \JWeiland\Events2\Hooks\Solr\IndexerHook::class;
    }

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['TimestampMapper'] = \JWeiland\Events2\Routing\Aspect\TimestampMapper::class;

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][\JWeiland\Events2\Backend\FormDataProvider\InitializeNewEventRecord::class] = [
        'depends' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
        ]
    ];
};
$boot($_EXTKEY);
unset($boot);
