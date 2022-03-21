<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Validation\Validator;

use JWeiland\Events2\Domain\Model\Time;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Validates to true, if Time model is null or its sub-property timeBegin is filled.
 */
class TimeModelValidator extends AbstractValidator
{
    /**
     * Checks, if the given time model is empty or its sub-property timeBegin is filled.
     *
     * @param mixed $value
     */
    public function isValid($value)
    {
        if (
            $value instanceof Time
            && $value->getTimeBegin() === ''
        ) {
            $this->addError(
                $this->translateErrorMessage(
                    'validator.timeModel.empty',
                    'events2'
                ),
                1647875338
            );
        }
    }
}
