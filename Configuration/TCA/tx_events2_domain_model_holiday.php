<?php
return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_holiday',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'rootLevel' => 1,
        'searchFields' => 'title',
        'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('events2') . 'Resources/Public/Icons/tx_events2_domain_model_holiday.svg',
    ),
    'interface' => array(
        'showRecordFieldList' => 'title, day, month',
    ),
    'columns' => array(
        'title' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_holiday.title',
            'config' => array(
                'type' => 'input',
                'size' => 30,
            ),
        ),
        'day' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_holiday.day',
            'config' => array(
                'type' => 'input',
                'size' => 2,
                'eval' => 'int,required'
            ),
        ),
        'month' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_holiday.month',
            'config' => array(
                'type' => 'input',
                'size' => 2,
                'eval' => 'int,required'
            ),
        ),
    ),
    'types' => array(
        '1' => array('showitem' => 'title, day, month'),
    ),
    'palettes' => array(
        '1' => array('showitem' => ''),
    ),
);
