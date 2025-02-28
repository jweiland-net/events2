<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\EventListener;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Event\PreProcessControllerActionEvent;
use JWeiland\Events2\Traits\IsValidEventListenerRequestTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;

/**
 * Add validator for event location in event forms, if it was configured in extension settings.
 */
#[AsEventListener('events2/applyLocationAsMandatoryIfNeeded')]
final readonly class ApplyLocationAsMandatoryIfNeededEventListener
{
    use IsValidEventListenerRequestTrait;

    protected const ALLOWED_CONTROLLER_ACTIONS = [
        'Management' => [
            'create',
            'update',
        ],
    ];

    public function __construct(
        private ExtConf $extConf,
        private ValidatorResolver $validatorResolver,
    ) {}

    public function __invoke(PreProcessControllerActionEvent $controllerActionEvent): void
    {
        if (
            $this->isValidRequest($controllerActionEvent)
            && $this->extConf->getLocationIsRequired()
            && ($notEmptyValidator = $this->validatorResolver->createValidator(NotEmptyValidator::class))
            && $notEmptyValidator instanceof NotEmptyValidator
        ) {
            /** @var ConjunctionValidator $eventValidator */
            $eventValidator = $controllerActionEvent->getArguments()->getArgument('event')->getValidator();
            /** @var ConjunctionValidator $conjunctionValidator */
            $conjunctionValidator = $eventValidator->getValidators()->current();
            /** @var GenericObjectValidator $genericEventValidator */
            $genericEventValidator = $conjunctionValidator->getValidators()->current();
            $genericEventValidator->addPropertyValidator(
                'location',
                $notEmptyValidator,
            );
        }
    }
}
