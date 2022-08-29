<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Validator;

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Validates, if value is a valid YouTube URL
 * Currently, needed by EXT:form elements of type YouTube
 */
class YouTubeValidator extends AbstractValidator
{
    /**
     * @param mixed $value
     */
    public function isValid($value): void
    {
        if (!preg_match('~^(|http:|https:)//(|www.)youtu(\.be|be)(.*?)(v=|embed/|)([a-zA-Z0-9_-]+)$~i', $value)) {
            $this->addError(
                'The YouTube URL is invalid. Please insert full URL like https://www.youtube.com/watch?v=qzqEUgQu67Q or https://youtu.be/qzqEUgQu67Q',
                1660809641
            );
        }
    }
}
