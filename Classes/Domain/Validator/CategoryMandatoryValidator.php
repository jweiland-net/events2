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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Validates, if category is a mandatory property, if configured in Extension Settings
 */
class CategoryMandatoryValidator extends AbstractValidator
{
    /**
     * @param mixed $value
     */
    public function isValid($value)
    {
        if (!$value instanceof ObjectStorage) {
            return;
        }

        if (!$this->getExtConf()->getCategoryIsRequired()) {
            return;
        }

        if ($value->count() === 0) {
            $this->addError(
                $this->translateErrorMessage(
                    'validator.event.categories.isMandatory',
                    'events2'
                ),
                1697100735
            );
        }
    }

    public function getExtConf(): ExtConf
    {
        return GeneralUtility::makeInstance(ExtConf::class);
    }

    public function getObjectManager(): ObjectManager
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }
}
