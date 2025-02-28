<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\EventListener;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Event\PreProcessControllerActionEvent;
use JWeiland\Events2\Property\TypeConverter\UploadMultipleFilesConverter;
use JWeiland\Events2\Traits\IsValidEventListenerRequestTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;

#[AsEventListener('events2/assignMediaTypeConverter')]
final readonly class AssignMediaTypeConverterEventListener
{
    use IsValidEventListenerRequestTrait;

    protected const ALLOWED_CONTROLLER_ACTIONS = [
        'Management' => [
            'create',
            'update',
        ],
    ];

    public function __construct(
        private EventRepository $eventRepository,
        private UploadMultipleFilesConverter $uploadMultipleFilesConverter,
    ) {}

    public function __invoke(PreProcessControllerActionEvent $controllerActionEvent): void
    {
        if ($this->isValidRequest($controllerActionEvent)) {
            if ($controllerActionEvent->getActionName() === 'create') {
                $this->assignTypeConverterForCreateAction($controllerActionEvent);
            } else {
                $this->assignTypeConverterForUpdateAction($controllerActionEvent);
            }
        }
    }

    private function assignTypeConverterForCreateAction(PreProcessControllerActionEvent $controllerActionEvent): void
    {
        $this->setTypeConverterForProperty('images', null, $controllerActionEvent);
    }

    private function assignTypeConverterForUpdateAction(PreProcessControllerActionEvent $controllerActionEvent): void
    {
        // Needed to get the previously stored images
        /** @var Event|null $persistedEvent */
        $persistedEvent = $this->eventRepository->findHiddenObject(
            (int)$controllerActionEvent->getRequest()->getArgument('event')['__identity'],
        );

        if ($persistedEvent instanceof Event) {
            $this->setTypeConverterForProperty('images', $persistedEvent->getOriginalImages(), $controllerActionEvent);
        }
    }

    private function setTypeConverterForProperty(
        string $property,
        ?ObjectStorage $persistedFiles,
        PreProcessControllerActionEvent $controllerActionEvent,
    ): void {
        $propertyMappingConfiguration = $this->getPropertyMappingConfigurationForEvent($controllerActionEvent)
            ->forProperty($property)
            ->setTypeConverter($this->uploadMultipleFilesConverter);

        // Do not use setTypeConverterOptions() as this will remove all existing options
        $this->addOptionToUploadFilesConverter(
            $propertyMappingConfiguration,
            'settings',
            $controllerActionEvent->getSettings(),
        );

        if ($persistedFiles !== null) {
            $this->addOptionToUploadFilesConverter(
                $propertyMappingConfiguration,
                'IMAGES',
                $persistedFiles,
            );
        }
    }

    private function getPropertyMappingConfigurationForEvent(
        PreProcessControllerActionEvent $controllerActionEvent,
    ): MvcPropertyMappingConfiguration {
        return $controllerActionEvent->getArguments()
            ->getArgument('event')
            ->getPropertyMappingConfiguration();
    }

    private function addOptionToUploadFilesConverter(
        PropertyMappingConfiguration $propertyMappingConfiguration,
        string $optionKey,
        $optionValue,
    ): void {
        $propertyMappingConfiguration->setTypeConverterOption(
            UploadMultipleFilesConverter::class,
            $optionKey,
            $optionValue,
        );
    }
}
