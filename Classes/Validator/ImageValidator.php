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
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ImageValidator extends AbstractValidator
{
    /**
     * Checks if the given value is in list of allowed image extensions.
     *
     * @param mixed $value The value that should be validated
     *
     * @return bool TRUE if the value is valid, FALSE if an error occured
     */
    public function isValid($value)
    {
        $this->errors = array();
        if ($value instanceof \DateTime) {
            return;
        }
        $this->addError(
            \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                'validator.datetime.notvalid',
                'extbase',
                array(
                    gettype($value),
                )
            ), 1238087674, array(gettype($value)));
    }
}
