<?php
return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_day',
        'label' => 'day',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'dividers2tabs' => true,
        'hideTable' => true,
        'searchFields' => 'day',
        'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('events2') . 'Resources/Public/Icons/tx_events2_domain_model_day.gif',
    ),
    'interface' => array(
        'showRecordFieldList' => 'day',
    ),
    'columns' => array(
        'day' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_day.day',
            'config' => array(
                'type' => 'input',
                'size' => 7,
                'eval' => 'date',
                'checkbox' => 1,
                'default' => time(),
            ),
        ),
        'events' => array(
            'config' => array(
                'type' => 'passthrough',
                'foreign_table' => 'tx_events2_domain_model_event',
                'MM' => 'tx_events2_event_day_mm',
                'MM_opposite_field' => 'days',
            ),
        ),
        'event' => array(
            'config' => array(
                'type' => 'passthrough',
            ),
        ),
    ),
    'types' => array(
        '1' => array('showitem' => 'day,'),
    ),
    'palettes' => array(
        '1' => array('showitem' => ''),
    ),
);
