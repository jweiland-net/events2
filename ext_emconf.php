<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Events 2',
    'description' => 'Create single and recurring events',
    'category' => 'plugin',
    'author' => 'Stefan Froemken',
    'author_email' => 'projects@jweiland.net',
    'author_company' => 'jweiland.net',
    'state' => 'stable',
    'version' => '8.4.9',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.99.99',
            'typo3' => '10.4.36-11.5.99',
            'static_info_tables' => '6.9.0-11.5.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'checkfaluploads' => '',
            'maps2' => '10.0.0-0.0.0',
            'solr' => '11.2.0-0.0.0',
        ],
    ],
];
