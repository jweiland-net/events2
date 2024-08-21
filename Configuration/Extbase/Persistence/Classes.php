<?php
return [
    \JWeiland\Events2\Domain\Model\Category::class => [
        'tableName' => 'sys_category',
    ],
    \JWeiland\Events2\Domain\Model\Exception::class => [
        'properties' => [
            'primer' => [
                'fieldName' => 'is_primer',
            ],
        ],
    ],
];
