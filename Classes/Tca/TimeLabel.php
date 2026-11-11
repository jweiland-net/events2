<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tca;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * This class adds the time and weekday to label of time-records in TCA.
 *
 * Some core places (e.g. items of select fields with foreign_table) call
 * this label_userFunc with a reduced row containing only the fields of
 * BackendUtility::getCommonSelectFields(). These fields are configured in
 * ctrl.label_alt. Missing fields will be loaded from database as fallback.
 */
readonly class TimeLabel
{
    private const TABLE = 'tx_events2_domain_model_time';

    private const REQUIRED_FIELDS = ['time_begin', 'time_end', 'type', 'weekday'];

    /**
     * Add weekday to time records,
     * but only if record is for field different_times.
     */
    public function getTitle(array &$ctrlArray, ?object $parentObject): void
    {
        if (($ctrlArray['table'] ?? '') !== self::TABLE) {
            return;
        }

        $row = $this->getCompleteRow($ctrlArray['row'] ?? []);
        if (!isset($row['time_begin'])) {
            return;
        }

        $ctrlArray['title'] = $this->getTimeRange($row) . $this->getWeekdaySuffix($row);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function getCompleteRow(array $row): array
    {
        $missingFields = array_diff(self::REQUIRED_FIELDS, array_keys($row));
        $uid = (int)($row['uid'] ?? 0);
        if ($missingFields === [] || $uid <= 0) {
            return $row;
        }

        $record = BackendUtility::getRecord(self::TABLE, $uid, implode(',', $missingFields));

        return is_array($record) ? array_replace($record, $row) : $row;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function getTimeRange(array $row): string
    {
        $title = (string)$row['time_begin'];
        if (isset($row['time_end']) && $row['time_end'] !== '') {
            $title .= ' - ' . $row['time_end'];
        }

        return $title;
    }

    /**
     * Weekday is only relevant in different_times context
     *
     * @param array<string, mixed> $row
     */
    private function getWeekdaySuffix(array $row): string
    {
        if (($row['type'] ?? '') !== 'different_times') {
            return '';
        }

        $weekday = $row['weekday'] ?? '';
        if (is_array($weekday)) {
            $weekday = reset($weekday);
        }

        if (!is_string($weekday) || $weekday === '') {
            return '';
        }

        $translationKey = self::TABLE . '.weekday.' . $weekday;

        return ': ' . LocalizationUtility::translate($translationKey, 'events2');
    }
}
