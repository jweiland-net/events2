<?php

declare(strict_types=1);

use JWeiland\Events2\Task\Import;
use JWeiland\Events2\Task\ReGenerateDays;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

if (!isset($GLOBALS['TCA']['tx_scheduler_task'])) {
    return;
}

ExtensionManagementUtility::addTCAcolumns(
    'tx_scheduler_task',
    [
        'events2_import_path' => [
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang.xlf:scheduler.path',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'required' => true,
                'eval' => 'trim',
                'placeholder' => '1:/event_import/Import.xml',
            ],
        ],
        'events2_import_storage_pid' => [
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang.xlf:scheduler.storagePid',
            'config' => [
                'type' => 'number',
                'required' => true,
                'default' => 0,
                'placeholder' => '123',
            ],
        ],
    ],
);

ExtensionManagementUtility::addRecordType(
    [
        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:task.reCreateDays.title',
        'description' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:task.reCreateDays.description',
        'value' => ReGenerateDays::class,
        'icon' => 'mimetypes-x-tx_scheduler_task_group',
        'group' => 'events2',
    ],
    '
        --div--;core.form.tabs:general,
            tasktype,
            task_group,
            description,
        --div--;core.form.tabs:timing,
            --palette--;;execution,
        --div--;core.form.tabs:access,
            disable,
        --div--;core.form.tabs:extended,',
    [],
    '',
    'tx_scheduler_task',
);

ExtensionManagementUtility::addRecordType(
    [
        'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:task.import.title',
        'description' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:task.import.description',
        'value' => Import::class,
        'icon' => 'mimetypes-x-tx_scheduler_task_group',
        'group' => 'events2',
    ],
    '
        --div--;core.form.tabs:general,
            tasktype,
            task_group,
            description,
            events2_import_path,
            events2_import_storage_pid,
        --div--;core.form.tabs:timing,
            --palette--;;execution,
        --div--;core.form.tabs:access,
            disable,
        --div--;core.form.tabs:extended,',
    [],
    '',
    'tx_scheduler_task',
);
