<?php

namespace JWeiland\Events2\Tca;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Stefan Froemken <projects@jweiland.net>, jweiland.net
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
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
            $ctrlArray['title'] .= $ctrlArray['row']['time_end'] ? ' - '.$ctrlArray['row']['time_end'] : '';

            // if we are in different_time context, we add weekday to time
            if ($ctrlArray['row']['type'] === 'different_times') {
                $translationKey = 'tx_events2_domain_model_time.weekday.'.$ctrlArray['row']['weekday'];
                $ctrlArray['title'] .= ': '.LocalizationUtility::translate($translationKey, 'events2');
            }
        }
    }
}
