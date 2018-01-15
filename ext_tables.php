<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$boot = function ($extensionKey, $extensionConfiguration) {
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

    if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) >= 8004000) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($extensionKey, 'Configuration/TypoScript/Typo384', 'Events (>=8.4)');
    } elseif (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) >= 7006000) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($extensionKey, 'Configuration/TypoScript/Typo376', 'Events (>=7.6)');
    } else {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($extensionKey, 'Configuration/TypoScript/Typo362', 'Events (>=6.2)');
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_events2_scheduler', 'EXT:events2/Resources/Private/Language/locallang_csh_scheduler.xlf');
    foreach (['event', 'exception', 'holiday', 'link', 'location', 'organizer', 'time'] as $value) {
        $tableName = 'tx_events2_domain_model_' . $value;
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr($tableName, 'EXT:events2/Resources/Private/Language/locallang_csh_' . $tableName . '.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages($tableName);
    }

    if (
        TYPO3_MODE === 'BE' &&
        \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) < 7000000
    ) {
        $extRelPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('events2');
        \TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons(
            [
                'calendar-single' => $extRelPath . 'Resources/Public/Icons/calendar_single.png',
                'calendar-recurring' => $extRelPath . 'Resources/Public/Icons/calendar_recurring.png',
                'calendar-duration' => $extRelPath . 'Resources/Public/Icons/calendar_duration.png',
                'exception-add' => $extRelPath . 'Resources/Public/Icons/exception_add.png',
                'exception-remove' => $extRelPath . 'Resources/Public/Icons/exception_remove.png',
                'exception-info' => $extRelPath . 'Resources/Public/Icons/exception_info.png',
                'exception-time' => $extRelPath . 'Resources/Public/Icons/exception_time.png',
            ],
            'events2'
        );
    }

    $extConf = unserialize($extensionConfiguration);
    $tsConfig = [];
    $tsConfig[] = 'ext.events2.pid = ' . (int)$extConf['poiCollectionPid'];
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(implode(chr(10), $tsConfig));
};
$boot($_EXTKEY, $_EXTCONF);
unset($boot);
