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
 * With this class you can convert a time like 08:34 into its integer representation (seconds after midnight)
 */
class TimeToStringConverter
{
    public function convert(int $timestamp): string
    {
        $time = '';
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
     *
     * @param int $time
     * @return float
     */
    protected function getHours(int $time)
    {
        return floor((int)$time / 3600);
    }

    /**
     * Extracts the minutes from $time
     * Example:
     * 33.300 Seconds / 3.600 = 9,25 hours
     * 9 * 3.600 = 32.400
     * 33.300 - 32.400 = 900 seconds remaining
     * 900 / 60 = 15 minutes.
     *
     * @param int   $time  seconds since midnight
     * @param float $hours
     * @return int remaining minutes
     */
    protected function getRemainingMinutes(int $time, float $hours): int
    {
        $seconds = $hours === (float) 0 ? $time : $time % ($hours * 3600);
        if ($seconds) {
            $minutes = floor($seconds / 60);
        } else {
            $minutes = 0;
        }

        return (int)$minutes;
    }
}
