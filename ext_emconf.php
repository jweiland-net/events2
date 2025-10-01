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
    'version' => '10.0.5',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.3-13.4.99',
            'reactions' => '13.4.3-13.4.99',
            'static_info_tables' => '13.4.0-13.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'checkfaluploads' => '',
            'form' => '',
            'maps2' => '12.0.0-0.0.0',
            'solr' => '13.0.1-0.0.0',
        ],
    ],
];
