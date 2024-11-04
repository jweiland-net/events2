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

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('reactions')) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
        'sys_reaction',
        'reaction_type',
        [
            'label' => \JWeiland\Events2\Reaction\ImportEventsReaction::getDescription(),
            'value' => \JWeiland\Events2\Reaction\ImportEventsReaction::getType(),
            'icon' => \JWeiland\Events2\Reaction\ImportEventsReaction::getIconIdentifier(),
        ]
    );
}

$GLOBALS['TCA']['sys_reaction']['palettes']['setStorage'] = [
    'label' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:palette.additional',
    'showitem' => 'storage_pid',
];

$GLOBALS['TCA']['sys_reaction']['types'][\JWeiland\Events2\Reaction\ImportEventsReaction::getType()] = [
    'showitem' => '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
        --palette--;;config,
        --palette--;;setStorage,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
        --palette--;;access',
];
