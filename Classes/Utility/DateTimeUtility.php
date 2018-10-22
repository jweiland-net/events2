<?php

namespace JWeiland\Events2\Utility;

/*
 * This file is part of the events2 project.
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
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DateTimeUtility
{
    /**
     * Creates a DateTime from an unix timestamp or date/datetime value.
     * If the input is empty, NULL is returned.
     *
     * @param int|string $value Unix timestamp or date/datetime value
     * @return \DateTime|null
     */
    public function convert($value)
    {
        try {
            if (is_bool($value) || empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
                $dateTimeObject = null;
            } elseif (is_string($value) && !MathUtility::canBeInterpretedAsInteger($value)) {
                // SF: This is my own converter for modifying the date by special formatting values like "today" OR "tomorrow"
                $currentTimeZone = new \DateTimeZone(date_default_timezone_get());
                $date = new \DateTime($value, $currentTimeZone);
                $dateTimeObject = $this->standardizeDateTimeObject($date);
            } else {
                $date = new \DateTime(date('Y-m-d H:i:s', $value));
                $dateTimeObject = $this->standardizeDateTimeObject($date);
            }
        } catch (\Exception $e) {
            $dateTimeObject = null;
        }

        return $dateTimeObject;
    }

    /**
     * we have our own implementation of Time
     * That's why we change time to midnight in DateTime-Objects
     * Further it's easier to compare DateTime-Objects
     * Hint: This function can also be called with NULL.
     *
     * @param \DateTime $date
     * @return \DateTime
     */
    public function standardizeDateTimeObject($date)
    {
        if ($date instanceof \DateTime) {
            $date->modify('midnight');
        }

        return $date;
    }

    /**
     * Add diff of eventStart and eventEnd to a specific day
     *
     * @param \DateTime $day  The Day to add the difference to
     * @param \DateTime $from The date FROM
     * @param \DateTime $to   The date TO
     * @return \DateTime
     */
    public function addDiffToDay(\DateTime $day, \DateTime $from, \DateTime $to)
    {
        // then and else parts will be parsed before if condition was called. This is in my kind of view a bug: http://forge.typo3.org/issues/49292
        // But eventEnd is not a required event property, but it is a required property here
        // So, if this viewHelper was called within an if-part, that is not true, it could be that $to is null.
        // That's why we have to check this here before further processing
        $clonedDay = clone $day;
        $diff = $from->diff($to);

        return $clonedDay->add($diff);
    }
}
