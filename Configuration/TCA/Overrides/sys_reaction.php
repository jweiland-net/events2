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
        ],
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
        'sys_reaction',
        [
            'storage_folder' => [
                'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:sys_reactions.storage_folder',
                'description' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:sys_reactions.storage_folder.description',
                'config' => [
                    'type' => 'folder',
                    'required' => true,
                    'maxitems' => 1,
                    'size' => 1,
                ],
            ],
            'parent_category' => [
                'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:sys_reactions.parent_category',
                'description' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:sys_reactions.parent_category.description',
                'config' => [
                    'type' => 'category',
                    'foreign_table_where' => ' AND sys_category.sys_language_uid IN (-1, 0) ORDER BY sys_category.title ASC',
                    'required' => false,
                    'maxitems' => 1,
                ],
            ],
        ],
    );

    $GLOBALS['TCA']['sys_reaction']['palettes']['setStorage'] = [
        'label' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:palette.additional',
        'showitem' => 'storage_pid, impersonate_user, --linebreak--, storage_folder, --linebreak--, parent_category',
    ];

    $GLOBALS['TCA']['sys_reaction']['types'][\JWeiland\Events2\Reaction\ImportEventsReaction::getType()] = [
        'showitem' => '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
        --palette--;;config,
        --palette--;;setStorage,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
        --palette--;;access',
        'columnsOverrides' => [
            'storage_pid' => [
                'config' => [
                    'required' => true,
                ],
            ],
            'impersonate_user' => [
                'config' => [
                    'required' => true,
                ],
            ],
        ],
    ];
}
