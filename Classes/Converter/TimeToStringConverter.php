<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Converter;

/*
 * With this class you can convert seconds from midnight into a time format like 08:34.
 *
 * ToDo: I remember we have implemented it as backwards compatibility where we have entered duration with
 * ToDo: seconds after midnight. Maybe we can remove that class with next major release.
 */
class TimeToStringConverter
{
    public function convert(int $timestamp): string
    {
        if ($timestamp >= (60 * 60 * 24)) {
            // return highest allowed value: 23:59 if timestamp is too high
            $time = '23:59';
        } elseif ($timestamp <= 0) {
            // return minimum allowed value: 00:00 if timestamp is too low
            $time = '00:00';
        } else {
            $hours = $this->getHours($timestamp);
            $minutes = $this->getRemainingMinutes($timestamp, $hours);
            $time = sprintf(
                '%s:%s',
                str_pad((string)$hours, 2, '0', STR_PAD_LEFT),
                str_pad((string)$minutes, 2, '0', STR_PAD_LEFT)
            );
        }

        return $time;
    }

    /**
     * this method rounds down (floor) the contained hours in $time
     * Hint: Can also return 0. Be careful with this result (division by zero).
     */
    protected function getHours(int $time): float
    {
        return floor($time / 3600);
    }

    /**
     * Extracts the minutes from $time
     * Example:
     * 33.300 Seconds / 3.600 = 9,25 hours
     * 9 * 3.600 = 32.400
     * 33.300 - 32.400 = 900 seconds remaining
     * 900 / 60 = 15 minutes.
     */
    protected function getRemainingMinutes(int $time, float $hours): int
    {
        $seconds = $hours === (float)0 ? $time : $time % ($hours * 3600);
        $minutes = $seconds !== 0 ? floor($seconds / 60) : 0;

        return (int)$minutes;
    }
}
