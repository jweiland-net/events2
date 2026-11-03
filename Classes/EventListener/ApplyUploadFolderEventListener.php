<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\EventListener;

use JWeiland\Events2\Event\PreProcessControllerActionEvent;
use JWeiland\Events2\Traits\IsValidEventListenerRequestTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Overrides the uploadFolder of the #[FileUpload] attribute of Event::$images with
 * the value configured in TypoScript (settings.new.uploadFolder), as attribute
 * arguments cannot be filled with settings.
 *
 * @link https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/13.3/Feature-103511-IntroduceExtbaseFileUploadHandling.html#modifying-existing-configuration
 */
#[AsEventListener(
    identifier: 'events2/apply-upload-folder',
)]
final readonly class ApplyUploadFolderEventListener
{
    use IsValidEventListenerRequestTrait;

    private const ALLOWED_CONTROLLER_ACTIONS = [
        'Management' => [
            'create',
            'update',
        ],
    ];

    private const PROPERTY_NAMES = [
        'images',
    ];

    public function __invoke(PreProcessControllerActionEvent $controllerActionEvent): void
    {
        if (!$this->isValidRequest($controllerActionEvent)) {
            return;
        }

        $uploadFolder = (string)($controllerActionEvent->getSettings()['new']['uploadFolder'] ?? '');
        if ($uploadFolder === '' || !$controllerActionEvent->getArguments()->hasArgument('event')) {
            return;
        }

        $fileHandlingServiceConfiguration = $controllerActionEvent
            ->getArguments()
            ->getArgument('event')
            ->getFileHandlingServiceConfiguration();

        foreach (self::PROPERTY_NAMES as $propertyName) {
            $fileHandlingServiceConfiguration
                ->getFileUploadConfigurationForProperty($propertyName)
                ?->setUploadFolder($uploadFolder);
        }
    }
}
