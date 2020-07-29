<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tca\Type;

use JWeiland\Events2\Converter\TimeToStringConverter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/*
 * This class checks, if given time entry like 08:34 is valid in TCA.
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
            // this is only for backwards compatibility. In earlier versions we calculated these values with int
            /** @var TimeToStringConverter $converter */
            $converter = GeneralUtility::makeInstance(TimeToStringConverter::class);

            return $converter->convert($value);
        } elseif ($value === '24:00') {
            return $value;
        } else {
            $parts = GeneralUtility::intExplode(':', $value);
            if (count($parts) == 2) {
                $parts[0] = str_pad(MathUtility::forceIntegerInRange($parts[0], 0, 23), 2, '0', STR_PAD_LEFT);
                $parts[1] = str_pad(MathUtility::forceIntegerInRange($parts[1], 0, 59), 2, '0', STR_PAD_LEFT);

                return $parts[0] . ':' . $parts[1];
            } else {
                return '';
            }
        }
    }
}
