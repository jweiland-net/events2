<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tca;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/*
 * This class adds the time and weekday to lable of time-records in TCA.
 */
class TimeLabel
{
    /**
     * add weekday to time records
     * but only if record is for field different_times.
     *
     * @param array $ctrlArray
     * @param $parentObject
     */
    public function getTitle(array &$ctrlArray, $parentObject)
    {
        if ($ctrlArray['table'] === 'tx_events2_domain_model_time') {
            // add begin and end to title in general
            $ctrlArray['title'] = $ctrlArray['row']['time_begin'];
            $ctrlArray['title'] .= $ctrlArray['row']['time_end'] ? ' - ' . $ctrlArray['row']['time_end'] : '';

            // if we are in different_time context, we add weekday to time
            if ($ctrlArray['row']['type'] === 'different_times') {
                $translationKey = 'tx_events2_domain_model_time.weekday.' . $ctrlArray['row']['weekday'];
                $ctrlArray['title'] .= ': ' . LocalizationUtility::translate($translationKey, 'events2');
            }
        }
    }
}
