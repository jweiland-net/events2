<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Events 2',
    'description' => 'Create single and recurring events',
    'category' => 'plugin',
    'author' => 'Stefan Froemken',
    'author_email' => 'projects@jweiland.net',
    'author_company' => 'jweiland.net',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '6.1.1',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.20-10.4.99',
            'static_info_tables' => '6.9.0',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'checkfaluploads' => '',
            'maps2' => '8.0.0-0.0.0',
        ],
    ],
];
