<?php
if (!defined('TYPO3')) {
    die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
    '_MOD_events2_scheduler',
    'EXT:events2/Resources/Private/Language/locallang_csh_scheduler.xlf'
);

foreach (['event', 'exception', 'holiday', 'link', 'location', 'organizer', 'time'] as $value) {
    $tableName = 'tx_events2_domain_model_' . $value;
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        $tableName,
        'EXT:events2/Resources/Private/Language/locallang_csh_' . $tableName . '.xlf'
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages($tableName);
}
