<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Helper;

use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * Helper class to replace timestamp values of database record with DateTime objects
 */
class DateTimeHelper
{
    /**
     * @var DateTimeUtility
     */
    protected $dateTimeUtility;

    public function __construct(DateTimeUtility $dateTimeUtility)
    {
        $this->dateTimeUtility = $dateTimeUtility;
    }

    public function addDateTimeObjects(array &$record, string $table): void
    {
        if ($record === [] || !array_key_exists($table, $GLOBALS['TCA'])) {
            return;
        }

        foreach ($record as $column => $value) {
            if (
                isset($GLOBALS['TCA'][$table]['columns'][$column]['config'])
                && ($columnConfiguration = $GLOBALS['TCA'][$table]['columns'][$column]['config'])
                && (
                    GeneralUtility::inList($columnConfiguration['eval'], 'date')
                    || GeneralUtility::inList($columnConfiguration['eval'], 'datetime')
                )
            ) {
                $record[$column] = $this->dateTimeUtility->convert($value);

                // Since PHP 7.4 we can not access timezone_type directly anymore.
                // If location is false, timezone_type is 1 or 2, but we need 3
                if (
                    $record[$column] instanceof \DateTime
                    && $record[$column]->getTimezone()->getLocation() === false
                ) {
                    $record[$column]->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                }
            }
        }
    }
}
