<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tca;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/*
 * This class adds the time and weekday to label of time-records in TCA.
 */
class TimeLabel
{
    /**
     * Add weekday to time records,
     * but only if record is for field different_times.
     */
    public function getTitle(array &$ctrlArray, ?object $parentObject): void
    {
        if ($ctrlArray['table'] !== 'tx_events2_domain_model_time') {
            return;
        }

        if (!isset($ctrlArray['row']['time_begin'])) {
            return;
        }

        // Add time begin
        $ctrlArray['title'] = $ctrlArray['row']['time_begin'];

        if (isset($ctrlArray['row']['time_end']) && $ctrlArray['row']['time_end'] !== '') {
            $ctrlArray['title'] .= ' - ' . $ctrlArray['row']['time_end'];
        }

        // if we are in different_time context, we add weekday to time
        if (
            $ctrlArray['row']['type'] === 'different_times'
            && isset($ctrlArray['row']['weekday'])
        ) {
            $translationKey = 'tx_events2_domain_model_time.weekday.' . $ctrlArray['row']['weekday'][0];
            $ctrlArray['title'] .= ': ' . LocalizationUtility::translate($translationKey, 'events2');
        }
    }
}
