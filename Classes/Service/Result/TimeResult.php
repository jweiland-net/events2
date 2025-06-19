<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service\Result;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Time data object. Instantiate this object with new()
 */
final readonly class TimeResult
{
    public function __construct(
        private array $timeRecord = [],
    ) {}

    public function getTimeRecord(): array
    {
        return $this->timeRecord;
    }

    public function getHour(): int
    {
        return $this->getHourAndMinuteFromTime()[0];
    }

    public function getMinute(): int
    {
        return $this->getHourAndMinuteFromTime()[1];
    }

    /**
     * Analyze for valid time value like "21:40" and return exploded time parts: hour (21) and minute (40).
     * It does not prepend "0" to a value. Time for "08:04" will be returned with hour (8) and minute (4).
     *
     * @return int[]
     */
    private function getHourAndMinuteFromTime(): array
    {
        if (!isset($this->timeRecord['time_begin'])) {
            return [0, 0];
        }

        if (!preg_match('@^([0-1]\d|2[0-3]):[0-5]\d$@', $this->timeRecord['time_begin'])) {
            return [0, 0];
        }

        return GeneralUtility::intExplode(':', $this->timeRecord['time_begin']);
    }
}
