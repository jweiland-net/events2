<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(function ($extensionKey, $extensionConfiguration) {
    $extensionName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($extensionKey);

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'JWeiland.' . $extensionKey,
        'Events',
        'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.events.title'
    );
    $pluginSignature = strtolower($extensionName) . '_events';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'select_key';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $extensionKey . '/Configuration/FlexForms/Events.xml');

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'JWeiland.' . $extensionKey,
        'Calendar',
        'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.calendar.title'
    );
    $pluginSignature = strtolower($extensionName) . '_calendar';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'select_key';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $extensionKey . '/Configuration/FlexForms/Calendar.xml');

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'JWeiland.' . $extensionKey,
        'Search',
        'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:plugin.search.title'
    );
    $pluginSignature = strtolower($extensionName) . '_search';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'select_key';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $extensionKey . '/Configuration/FlexForms/Search.xml');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($extensionKey, 'Configuration/TypoScript/Typo384', 'Events (>=8.4)');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_events2_scheduler', 'EXT:events2/Resources/Private/Language/locallang_csh_scheduler.xlf');
    foreach (['event', 'exception', 'holiday', 'link', 'location', 'organizer', 'time'] as $value) {
        $tableName = 'tx_events2_domain_model_' . $value;
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr($tableName, 'EXT:events2/Resources/Private/Language/locallang_csh_' . $tableName . '.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages($tableName);
    }

    $tsConfig = [];
    $tsConfig[] = 'ext.events2.pid = ' . (int)$extensionConfiguration['poiCollectionPid'];
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(implode(chr(10), $tsConfig));
}, $_EXTKEY, unserialize($_EXTCONF));
