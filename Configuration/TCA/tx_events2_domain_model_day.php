<?php
return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_day',
        'label' => 'day',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'hideTable' => true,
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
    ),
    'interface' => array(
        'showRecordFieldList' => 'day',
    ),
    'columns' => array(
        'day' => array(
            'label' => 'Day',
            'config' => array(
                'type' => 'input',
                'eval' => 'date',
            ),
        ),
        'day_time' => array(
            'label' => 'DayTime',
            'config' => array(
                'type' => 'input',
                'eval' => 'datetime',
            ),
        ),
        'sort_day_time' => array(
            'label' => 'DayTime',
            'config' => array(
                'type' => 'input',
                'eval' => 'datetime',
            ),
        ),
        'event' => array(
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'tx_events2_domain_model_event',
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
