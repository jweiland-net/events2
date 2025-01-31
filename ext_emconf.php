<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'Events 2',
    'description' => 'Create single and recurring events',
    'category' => 'plugin',
    'author' => 'Stefan Froemken',
    'author_email' => 'projects@jweiland.net',
    'author_company' => 'jweiland.net',
    'state' => 'stable',
    'version' => '9.2.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.15-12.4.99',
            'reactions' => '12.4.15-12.4.99',
            'static_info_tables' => '12.4.0-12.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'checkfaluploads' => '',
            'maps2' => '11.0.0-0.0.0',
            'reactions' => '12.4.0-12.4.99',
            'solr' => '11.2.0-0.0.0',
        ],
    ],
];
