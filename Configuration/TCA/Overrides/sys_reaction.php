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
