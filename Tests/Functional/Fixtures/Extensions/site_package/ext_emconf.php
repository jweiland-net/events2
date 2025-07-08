<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Functional test related site package extension',
    'description' => 'A test related site package extension',
    'category' => 'example',
    'author' => 'Stefan Froemken',
    'author_company' => 'jweiland.net',
    'author_email' => 'projects@jweiland.net',
    'state' => 'stable',
    'version' => '13.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
