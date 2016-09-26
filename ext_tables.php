<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$extensionName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($_EXTKEY);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'JWeiland.' . $_EXTKEY,
    'Events',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.events.title'
);
$pluginSignature = strtolower($extensionName) . '_events';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/Events.xml');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'JWeiland.' . $_EXTKEY,
    'Calendar',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.calendar.title'
);
$pluginSignature = strtolower($extensionName) . '_calendar';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/Calendar.xml');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'JWeiland.' . $_EXTKEY,
    'Search',
    'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.search.title'
);
$pluginSignature = strtolower($extensionName) . '_search';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/Search.xml');

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'JWeiland.' . $_EXTKEY,
        'web', // Make module a submodule of 'web'
        'events', // Submodule key
        '', // Position
        array(
            'Release' => 'list',
        ),
        array(
            'access' => 'user,group',
            'icon' => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_release.xlf',
        )
    );
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Events');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_events2_domain_model_event', 'EXT:events2/Resources/Private/Language/locallang_csh_tx_events2_domain_model_event.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_events2_domain_model_event');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_events2_domain_model_day', 'EXT:events2/Resources/Private/Language/locallang_csh_tx_events2_domain_model_day.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_events2_domain_model_day');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_events2_domain_model_time', 'EXT:events2/Resources/Private/Language/locallang_csh_tx_events2_domain_model_time.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_events2_domain_model_time');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_events2_domain_model_exception', 'EXT:events2/Resources/Private/Language/locallang_csh_tx_events2_domain_model_exception.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_events2_domain_model_exception');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_events2_domain_model_location', 'EXT:events2/Resources/Private/Language/locallang_csh_tx_events2_domain_model_location.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_events2_domain_model_location');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_events2_domain_model_organizer', 'EXT:events2/Resources/Private/Language/locallang_csh_tx_events2_domain_model_organizer.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_events2_domain_model_organizer');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_events2_domain_model_link', 'EXT:events2/Resources/Private/Language/locallang_csh_tx_events2_domain_model_link.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_events2_domain_model_link');

if (
    TYPO3_MODE === 'BE' &&
    !\TYPO3\CMS\Core\Utility\GeneralUtility::compat_version('7.0')
) {
    $extRelPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('events2');
    \TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons(
        array(
            'calendar-single' => $extRelPath . 'Resources/Public/Icons/calendar_single.png',
            'calendar-recurring' => $extRelPath . 'Resources/Public/Icons/calendar_recurring.png',
            'calendar-duration' => $extRelPath . 'Resources/Public/Icons/calendar_duration.png',
            'exception-add' => $extRelPath . 'Resources/Public/Icons/exception_add.png',
            'exception-remove' => $extRelPath . 'Resources/Public/Icons/exception_remove.png',
            'exception-info' => $extRelPath . 'Resources/Public/Icons/exception_info.png',
            'exception-time' => $extRelPath . 'Resources/Public/Icons/exception_time.png',
        ),
        'events2'
    );
}

$extConf = unserialize($_EXTCONF);
$tsConfig = array();
$tsConfig[] = 'ext.events2.pid = ' . (int)$extConf['poiCollectionPid'];
// nice hook, but it will render all kinds of CType==list elements.
//$tsConfig[] = 'mod.web_layout.tt_content.preview.list = EXT:events2/Resources/Private/Templates/BackendPluginItem.html';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(implode(chr(10), $tsConfig));
