<?php

return [
    'frontend' => [
        'jweiland/events2/get-days-for-month' => [
            'target' => \JWeiland\Events2\Middleware\GetDaysForMonthMiddleware::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
        'jweiland/events2/get-uri-for-day' => [
            'target' => \JWeiland\Events2\Middleware\GetUriForDayMiddleware::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
        'jweiland/events2/get-locations' => [
            'target' => \JWeiland\Events2\Middleware\GetLocationsMiddleware::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
    ],
];
