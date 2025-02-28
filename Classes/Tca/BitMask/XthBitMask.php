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
 * "xth" comes from fourTH, fifTH, sixTH. The "x" stands for the week number of current month.
 * Interpret this as: Each 1st and 3rd and 4th week of month.
 * Use getSelectedWeeks to get an array with all selected weeks (1st, 3rd, ...) of month.
 */
class XthBitMask extends BitSet
{
    public const FIRST = 1;
    public const SECOND = 2;
    public const THIRD = 4;
    public const FOURTH = 8;
    public const FIFTH = 16;

    private const XTH = [
        'first' => self::FIRST,
        'second' => self::SECOND,
        'third' => self::THIRD,
        'fourth' => self::FOURTH,
        'fifth' => self::FIFTH,
    ];

    public function getSelectedWeeks(): array
    {
        return array_filter(self::XTH, function ($value): bool {
            return $this->get($value);
        });
    }
}
