<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tca\BitMask;

use TYPO3\CMS\Core\Type\BitSet;

/**
 * Represents a day of a week.
 * Interpret this as: 1st and 3rd and 6th day of week.
 * Use getSelectedWeekdays to get an array with all selected weekdays (1st, 3rd, ...) of week.
 */
class WeekDayBitMask extends BitSet
{
    public const MONDAY = 1;
    public const TUESDAY = 2;
    public const WEDNESDAY = 4;
    public const THURSDAY = 8;
    public const FRIDAY = 16;
    public const SATURDAY = 32;
    public const SUNDAY = 64;

    protected array $weekdays = [
        'monday' => self::MONDAY,
        'tuesday' => self::TUESDAY,
        'wednesday' => self::WEDNESDAY,
        'thursday' => self::THURSDAY,
        'friday' => self::FRIDAY,
        'saturday' => self::SATURDAY,
        'sunday' => self::SUNDAY,
    ];

    public function getSelectedWeekdays(): array
    {
        return array_filter($this->weekdays, function ($value): bool {
            return $this->get($value);
        });
    }
}
