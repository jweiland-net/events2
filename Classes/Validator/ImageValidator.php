<?php

namespace JWeiland\Events2\Validator;

/*
 * This file is part of the TYPO3 CMS project.
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
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
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
            ), 1238087674, [gettype($value)]);
    }
}
