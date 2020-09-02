<?php
return [
    \JWeiland\Events2\Domain\Model\Category::class => [
        'tableName' => 'sys_category',
    ],
    \JWeiland\Events2\Domain\Model\Event::class => [
        'properties' => [
            'detailInformation' => [
                'fieldName' => 'detail_informations'
            ],
        ],
    ],
];
