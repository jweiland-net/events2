<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_day',
        'label' => 'day',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'hideTable' => true,
        'versioningWS' => true,
        // Do not add 'deleted'. Else table will grow very fast.
        // There is no need to un-delete a day-record. Save an event in BE and you're done.
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
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
        'is_removed_date' => [
            'label' => 'Is removed date. True, if show_anyway in exception is set.',
            'config' => [
                'type' => 'passthrough',
            ]
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
