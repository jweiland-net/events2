<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Validator;

use JWeiland\Events2\Configuration\ExtConf;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Validates, if category is a mandatory property, if configured in Extension Settings
 */
class CategoryMandatoryValidator extends AbstractValidator
{
    public function __construct(
        protected readonly ExtConf $extConf,
    ) {}

    protected function isValid(mixed $value): void
    {
        if (!$value instanceof ObjectStorage) {
            return;
        }

        if (!$this->extConf->getCategoryIsRequired()) {
            return;
        }

        if ($value->count() === 0) {
            $this->addError(
                $this->translateErrorMessage(
                    'validator.event.categories.isMandatory',
                    'events2',
                ),
                1697100735,
            );
        }
    }
}
