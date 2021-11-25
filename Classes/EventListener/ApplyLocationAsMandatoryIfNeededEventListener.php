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
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;

/**
 * Add validator for event location in event forms, if it is was configured in extension settings.
 */
class ApplyLocationAsMandatoryIfNeededEventListener extends AbstractControllerEventListener
{
    protected ObjectManagerInterface $objectManager;

    protected ExtConf $extConf;

    protected array $allowedControllerActions = [
        'Event' => [
            'create',
            'update'
        ]
    ];

    public function __construct(ObjectManagerInterface $objectManager, ExtConf $extConf)
    {
        $this->objectManager = $objectManager;
        $this->extConf = $extConf;
    }

    public function __invoke(PreProcessControllerActionEvent $event): void
    {
        if (
            $this->isValidRequest($event)
            && $this->extConf->getLocationIsRequired()
            && ($validatorResolver = $this->objectManager->get(ValidatorResolver::class))
            && ($notEmptyValidator = $validatorResolver->createValidator(NotEmptyValidator::class))
            && $notEmptyValidator instanceof NotEmptyValidator
        ) {
            /** @var ConjunctionValidator $eventValidator */
            $eventValidator = $event->getArguments()->getArgument('event')->getValidator();
            /** @var ConjunctionValidator $conjunctionValidator */
            $conjunctionValidator = $eventValidator->getValidators()->current();
            /** @var GenericObjectValidator $genericEventValidator */
            $genericEventValidator = $conjunctionValidator->getValidators()->current();
            $genericEventValidator->addPropertyValidator(
                'location',
                $notEmptyValidator
            );
        }
    }
}
