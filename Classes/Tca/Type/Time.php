<?php

namespace JWeiland\Events2\Tca\Type;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Stefan Froemken <projects@jweiland.net>, jweiland.net
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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Time
{
    /**
     * This method returns js code to check if valid time was entered
     * JS Validation does not work in IRRE context. So we have to validate by PHP.
     *
     * @return string
     */
    public function returnFieldJS()
    {
        return 'return value;';
    }

    /**
     * This method converts the value into a unique time format: 21:23.
     *
     * @param mixed $value
     *
     * @return string
     */
    public function evaluateFieldValue($value)
    {
        if (MathUtility::canBeInterpretedAsInteger($value)) {
            // this is only for backwards compatibility. In earlier versions we calculated these values with integers
            /** @var \JWeiland\Events2\Converter\TimeToStringConverter $converter */
            $converter = GeneralUtility::makeInstance('JWeiland\\Events2\\Converter\\TimeToStringConverter');

            return $converter->convert($value);
        } elseif ($value === '24:00') {
            return $value;
        } else {
            $parts = GeneralUtility::intExplode(':', $value);
            if (count($parts) == 2) {
                $parts[0] = str_pad(MathUtility::forceIntegerInRange($parts[0], 0, 23), 2, '0', STR_PAD_LEFT);
                $parts[1] = str_pad(MathUtility::forceIntegerInRange($parts[1], 0, 59), 2, '0', STR_PAD_LEFT);

                return $parts[0].':'.$parts[1];
            } else {
                return '';
            }
        }
    }
}
