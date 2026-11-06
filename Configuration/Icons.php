<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'ext-events2-wizard-icon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/plugin.svg',
    ],
    'ext-events2-record-event' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/tx_events2_domain_model_event.svg',
    ],
    'ext-events2-record-holiday' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/tx_events2_domain_model_holiday.svg',
    ],
    'ext-events2-record-link' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/tx_events2_domain_model_link.svg',
    ],
    'ext-events2-record-location' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/tx_events2_domain_model_location.svg',
    ],
    'ext-events2-record-organizer' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/tx_events2_domain_model_organizer.svg',
    ],
    'ext-events2-record-time' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/tx_events2_domain_model_time.svg',
    ],
    'ext-events2-calendar-single' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/calendar_single.svg',
    ],
    'ext-events2-calendar-recurring' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/calendar_recurring.svg',
    ],
    'ext-events2-calendar-duration' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/calendar_duration.svg',
    ],
    'ext-events2-exception-add' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/exception_add.svg',
    ],
    'ext-events2-exception-remove' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/exception_remove.svg',
    ],
    'ext-events2-exception-info' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/exception_info.svg',
    ],
    'ext-events2-exception-time' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/exception_time.svg',
    ],
];
