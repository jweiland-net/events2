<?php

use JWeiland\Events2\Middleware\GetDaysForMonthMiddleware;
use JWeiland\Events2\Middleware\GetLocationsMiddleware;
use JWeiland\Events2\Middleware\GetSubCategoriesMiddleware;
use JWeiland\Events2\Middleware\GetUriForDayMiddleware;

return [
    'frontend' => [
        'jweiland/events2/get-days-for-month' => [
            'target' => GetDaysForMonthMiddleware::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
        'jweiland/events2/get-uri-for-day' => [
            'target' => GetUriForDayMiddleware::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
        'jweiland/events2/get-sub-categories' => [
            'target' => GetSubCategoriesMiddleware::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
        'jweiland/events2/get-locations' => [
            'target' => GetLocationsMiddleware::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
    ],
];
