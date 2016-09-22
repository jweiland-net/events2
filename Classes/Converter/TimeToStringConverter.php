<?php

namespace JWeiland\Events2\Converter;
    
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TimeToStringConverter
{
    /**
     * a method to convert a timestamp to a readable time format like: 21:35
     * maximum value is 23:59.
     *
     * @param int $timestamp Timestamp to convert
     *
     * @return string
     */
    public function convert($timestamp)
    {
        $time = '';
        if (is_int($timestamp)) {
            if ($timestamp >= (60 * 60 * 24)) {
                // return highest allowed value: 23:59 if timestamp is too high
                $time = '23:59';
            } elseif ($timestamp <= 0) {
                // return minimum allowed value: 00:00 if timestamp is too low
                $time = '00:00';
            } else {
                $hours = $this->getHours($timestamp);
                $minutes = $this->getRemainingMinutes($timestamp, $hours);
                $time = str_pad($hours, 2, '0', STR_PAD_LEFT).':'.str_pad($minutes, 2, '0', STR_PAD_LEFT);
            }
        }

        return $time;
    }

    /**
     * this method rounds down (floor) the contained hours in $time
     * Hint: Can also return 0. Be careful with this result (division by zero).
     *
     * @param int $time
     *
     * @return float
     */
    protected function getHours($time)
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
     *
     * @return int remaining minutes
     */
    protected function getRemainingMinutes($time, $hours)
    {
        $seconds = $hours === (float) 0 ? $time : $time % ($hours * 3600);
        if ($seconds) {
            $minutes = floor($seconds / 60);
        } else {
            $minutes = 0;
        }

        return $minutes;
    }
}
