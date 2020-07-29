<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Validator;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/*
 * @ToDo: Trash
 */
class ImageValidator extends AbstractValidator
{
    /**
     * Check if $value is valid. If it is not valid, needs to add an error
     * to result.
     *
     * @param mixed $value
     *
     * @return void
     */
    public function isValid($value)
    {
        if ($value instanceof \DateTime) {
            return;
        }

        $this->addError(
            LocalizationUtility::translate(
                'validator.datetime.notvalid',
                'extbase',
                [
                    gettype($value),
                ]
            ),
            1238087674,
            [gettype($value)]
        );
    }
}
