<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\EventListener;

use JWeiland\Checkfaluploads\Validation\Validator\CheckFalUploadValidator;
use JWeiland\Events2\Event\PreProcessControllerActionEvent;
use JWeiland\Events2\Traits\IsValidEventListenerRequestTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;

/**
 * If EXT:checkfaluploads is loaded, add its validator to the file upload
 * configuration of the event images. The validator checks the "rights"
 * checkbox rendered next to the upload field.
 */
#[AsEventListener(
    identifier: 'events2/check-fal-uploads',
)]
final readonly class CheckFalUploadsEventListener
{
    use IsValidEventListenerRequestTrait;

    private const ALLOWED_CONTROLLER_ACTIONS = [
        'Management' => [
            'create',
            'update',
        ],
    ];

    private const VALIDATOR = CheckFalUploadValidator::class;

    private const PROPERTY_NAMES = [
        'images',
    ];

    public function __construct(
        private ValidatorResolver $validatorResolver,
    ) {}

    public function __invoke(PreProcessControllerActionEvent $controllerActionEvent): void
    {
        if (!ExtensionManagementUtility::isLoaded('checkfaluploads')) {
            return;
        }

        if (!$this->isValidRequest($controllerActionEvent)
            || !$controllerActionEvent->getArguments()->hasArgument('event')
        ) {
            return;
        }

        foreach (self::PROPERTY_NAMES as $propertyName) {
            $this->assignValidatorToFileUploadConfiguration($controllerActionEvent, $propertyName);
        }
    }

    private function assignValidatorToFileUploadConfiguration(
        PreProcessControllerActionEvent $controllerActionEvent,
        string $propertyName,
    ): void {
        // The request is mandatory, as the validator reads the "rights" checkbox from the parsed body
        $checkFalUploadsValidator = $this->validatorResolver->createValidator(
            self::VALIDATOR,
            ['propertyPath' => 'event.' . $propertyName],
            $controllerActionEvent->getRequest(),
        );

        $controllerActionEvent
            ->getArguments()
            ->getArgument('event')
            ->getFileHandlingServiceConfiguration()
            ->getFileUploadConfigurationForProperty($propertyName)
            ?->addValidator($checkFalUploadsValidator);
    }
}
