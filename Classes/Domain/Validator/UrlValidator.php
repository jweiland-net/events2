<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Validator;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Validates, if value is a valid URL
 * Currently, needed by EXT:form elements of type link
 */
class UrlValidator extends AbstractValidator
{
    /**
     * @param mixed $value
     */
    public function isValid($value): void
    {
        if (!GeneralUtility::isValidUrl($value)) {
            $this->addError(
                'The URL is invalid. Please insert full URL like https:://example.com/target.html',
                1660809223
            );
        }
    }
}
