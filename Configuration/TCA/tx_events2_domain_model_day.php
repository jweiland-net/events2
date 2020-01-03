<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_day',
        'label' => 'day',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'hideTable' => true,
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
    ],
    'interface' => [
        'showRecordFieldList' => 'day',
    ],
    'types' => [
        '1' => ['showitem' => 'day'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
    'columns' => [
        'hidden' => [
            'config' => [
                'type' => 'passthrough',
            ]
        ],
        'day' => [
            'label' => 'Day',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'default' => 0,
                'eval' => 'date,int',
            ],
        ],
        'day_time' => [
            'label' => 'DayTime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'default' => 0,
                'eval' => 'datetime,int',
            ],
        ],
        'sort_day_time' => [
            'label' => 'DayTime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'default' => 0,
                'eval' => 'datetime,int',
            ],
        ],
        'same_day_time' => [
            'label' => 'DayTime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'default' => 0,
                'eval' => 'datetime,int',
            ],
        ],
        'event' => [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_events2_domain_model_event',
            ]
        ]
    ]
];
