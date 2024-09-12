<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use JWeiland\Events2\Middleware\AttachOrganizerToEventMiddleware;
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
        'jweiland/events2/attach-organizer' => [
            'target' => AttachOrganizerToEventMiddleware::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
    ],
];
