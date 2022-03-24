<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Utility;

use TYPO3\CMS\Core\Utility\MathUtility;

/*
 * With this class you can convert various strings and integers into a DateTime object.
 */
class DateTimeUtility
{
    /**
     * Creates a DateTime from an unix timestamp or date/datetime value.
     * If the input is empty, NULL is returned.
     *
     * @param mixed $value Unix timestamp or date/datetime value
     */
    public function convert($value): ?\DateTimeImmutable
    {
        try {
            if (is_bool($value) || empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
                $dateTimeObject = null;
            } elseif (is_string($value) && !MathUtility::canBeInterpretedAsInteger($value)) {
                // SF: This is my own converter for modifying the date by special formatting values like "today" OR "tomorrow"
                $currentTimeZone = new \DateTimeZone(date_default_timezone_get());
                $date = new \DateTimeImmutable($value, $currentTimeZone);
                $dateTimeObject = $this->standardizeDateTimeObject($date);
            } else {
                $currentTimeZone = new \DateTimeZone(date_default_timezone_get());
                $date = new \DateTimeImmutable(date('Y-m-d H:i:s', $value), $currentTimeZone);
                $dateTimeObject = $this->standardizeDateTimeObject($date);
            }
        } catch (\Exception $exception) {
            $dateTimeObject = null;
        }

        return $dateTimeObject;
    }

    /**
     * We have our own implementation of Time
     * That's why we change time to midnight in DateTime-Objects
     * Further it's easier to compare DateTime-Objects
     */
    public function standardizeDateTimeObject(?\DateTimeImmutable $date): ?\DateTimeImmutable
    {
        if ($date instanceof \DateTimeImmutable) {
            $standardizeDate = $date->modify('midnight');
            if ($standardizeDate instanceof \DateTimeImmutable) {
                return $standardizeDate;
            }
        }

        return null;
    }

    /**
     * Combines a DateTime object (should be configured as midnight) with hours and minutes from $time argument.
     * $time must be a 5 letter value like: 12:56
     * Keep "ical" as format to retrieve a format matching dates in Outlook.
     * Keep "UTC" as TimeZone for iCal dates
     */
    public function combineAndFormat(
        \DateTimeImmutable $date,
        string $time = '',
        string $format = 'ical',
        string $timeZone = 'UTC'
    ): string {
        if ($format === '') {
            return '';
        }

        $format = $format === 'ical' ? 'Ymd\THis\Z' : $format;

        // Add hours and minutes to date
        if (strlen($time) === 8) {
            $dateWithTime = $date->modify($time);
            if ($dateWithTime instanceof \DateTimeImmutable) {
                $date = $dateWithTime;
            }
        }

        $dateNewTimezone = $date->setTimezone(new \DateTimeZone($timeZone));

        return $dateNewTimezone->format($format);
    }
}
