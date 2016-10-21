<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'JWeiland.' . $_EXTKEY,
    'Events',
    array(
        'Event' => 'list, listLatest, listToday, listRange, listSearchResults, listMyEvents, new, create, edit, update, delete, activate',
        'Day' => 'show, showByTimestamp',
        'Location' => 'show',
        'Video' => 'show',
        'Ajax' => 'callAjaxObject',
    ),
    // non-cacheable actions
    array(
        'Event' => 'listSearchResults, create, update, delete, activate',
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

if (TYPO3_MODE === 'BE') {
    // repair records of events2
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'JWeiland\\Events2\\Command\\RepairCommandController';
    // here we register an eval function to check for time
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['JWeiland\\Events2\\Tca\\Type\\Time'] = 'EXT:events2/Classes/Tca/Type/Time.php';
    // use hook to automatically add a map record to event
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'JWeiland\\Events2\\Hooks\\CreateMap';
    // hook to automatically add/update related days
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'JWeiland\\Events2\\Tca\\CreateUpdateDays';
    // HOOK: Delete relations of deleted events
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = 'JWeiland\\Events2\\Tca\\DeleteDayRelations';
    // HOOK: Override rootUid in TCA for category trees
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass'][] = 'JWeiland\\Events2\\Hooks\\ModifyTcaOfCategoryTrees';
    // Hook: Render Plugin preview item
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['events2_events'][] = 'JWeiland\\Events2\\Hooks\\RenderPluginItem->render';
    
    // create scheduler to create/update days with recurrency
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['JWeiland\\Events2\\Task\\ReGenerateDays'] = array(
        'extension' => $_EXTKEY,
        'title' => 'Create/Update Days',
        'description' => 'Re-Generate day records for events with recurrency.',
        'additionalFields' => 'JWeiland\\Events2\\Task\\ReGenerateDays',
    );
    
    if (\TYPO3\CMS\Core\Utility\GeneralUtility::compat_version('7.0')) {
        /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
        $iconRegistry->registerIcon(
            'extensions-events2-calendar-single',
            'TYPO3\\CMS\\Core\\Imaging\\IconProvider\\BitmapIconProvider',
            array('source' => 'EXT:events2/Resources/Public/Icons/calendar_single.png')
        );
        $iconRegistry->registerIcon(
            'extensions-events2-calendar-recurring',
            'TYPO3\\CMS\\Core\\Imaging\\IconProvider\\BitmapIconProvider',
            array('source' => 'EXT:events2/Resources/Public/Icons/calendar_recurring.png')
        );
        $iconRegistry->registerIcon(
            'extensions-events2-calendar-duration',
            'TYPO3\\CMS\\Core\\Imaging\\IconProvider\\BitmapIconProvider',
            array('source' => 'EXT:events2/Resources/Public/Icons/calendar_duration.png')
        );
        $iconRegistry->registerIcon(
            'extensions-events2-exception-add',
            'TYPO3\\CMS\\Core\\Imaging\\IconProvider\\BitmapIconProvider',
            array('source' => 'EXT:events2/Resources/Public/Icons/exception_add.png')
        );
        $iconRegistry->registerIcon(
            'extensions-events2-exception-remove',
            'TYPO3\\CMS\\Core\\Imaging\\IconProvider\\BitmapIconProvider',
            array('source' => 'EXT:events2/Resources/Public/Icons/exception_remove.png')
        );
        $iconRegistry->registerIcon(
            'extensions-events2-exception-info',
            'TYPO3\\CMS\\Core\\Imaging\\IconProvider\\BitmapIconProvider',
            array('source' => 'EXT:events2/Resources/Public/Icons/exception_info.png')
        );
        $iconRegistry->registerIcon(
            'extensions-events2-exception-time',
            'TYPO3\\CMS\\Core\\Imaging\\IconProvider\\BitmapIconProvider',
            array('source' => 'EXT:events2/Resources/Public/Icons/exception_time.png')
        );
    }
}

// register eID scripts
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['events2findDaysForMonth'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('events2') . 'Classes/Ajax/FindDaysForMonth.php';
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['events2findLocations'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('events2') . 'Classes/Ajax/FindLocations.php';
