<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'ext-events2-wizard-icon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/plugin_wizard.svg',
    ],
    'ext-events2-calendar-single' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/calendar_single.png',
    ],
    'ext-events2-calendar-recurring' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/calendar_recurring.png',
    ],
    'ext-events2-calendar-duration' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/calendar_duration.png',
    ],
    'ext-events2-exception-add' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/exception_add.png',
    ],
    'ext-events2-exception-remove' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/exception_remove.png',
    ],
    'ext-events2-exception-info' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/exception_info.png',
    ],
    'ext-events2-exception-time' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:events2/Resources/Public/Icons/exception_time.png',
    ],
];
