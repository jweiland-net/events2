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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;

class AssignMediaTypeConverterEventListener extends AbstractControllerEventListener
{
    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @var UploadMultipleFilesConverter
     */
    protected $uploadMultipleFilesConverter;

    protected $allowedControllerActions = [
        'Event' => [
            'create',
            'update'
        ]
    ];

    public function __construct(
        EventRepository $eventRepository,
        UploadMultipleFilesConverter $uploadMultipleFilesConverter
    ) {
        $this->eventRepository = $eventRepository;
        $this->uploadMultipleFilesConverter = $uploadMultipleFilesConverter;
    }

    public function __invoke(PreProcessControllerActionEvent $event): void
    {
        if ($this->isValidRequest($event)) {
            if ($event->getActionName() === 'create') {
                $this->assignTypeConverterForCreateAction($event);
            } else {
                $this->assignTypeConverterForUpdateAction($event);
            }
        }
    }

    protected function assignTypeConverterForCreateAction(PreProcessControllerActionEvent $event): void
    {
        $this->setTypeConverterForProperty('images', null, $event);
    }

    protected function assignTypeConverterForUpdateAction(PreProcessControllerActionEvent $event): void
    {
        // Needed to get the previously stored images
        /** @var Event $persistedEvent */
        $persistedEvent = $this->eventRepository->findHiddenObject(
            (int)$event->getRequest()->getArgument('event')['__identity']
        );

        if ($persistedEvent instanceof Event) {
            $this->setTypeConverterForProperty('images', $persistedEvent->getOriginalImages(), $event);
        }
    }

    protected function setTypeConverterForProperty(
        string $property,
        ?ObjectStorage $persistedFiles,
        PreProcessControllerActionEvent $event
    ): void {
        $propertyMappingConfiguration = $this->getPropertyMappingConfigurationForEvent($event)
            ->forProperty($property)
            ->setTypeConverter($this->uploadMultipleFilesConverter);

        // Do not use setTypeConverterOptions() as this will remove all existing options
        $this->addOptionToUploadFilesConverter(
            $propertyMappingConfiguration,
            'settings',
            $event->getSettings()
        );

        if ($persistedFiles !== null) {
            $this->addOptionToUploadFilesConverter(
                $propertyMappingConfiguration,
                'IMAGES',
                $persistedFiles
            );
        }
    }

    protected function getPropertyMappingConfigurationForEvent(
        PreProcessControllerActionEvent $event
    ): MvcPropertyMappingConfiguration {
        return $event->getArguments()
            ->getArgument('event')
            ->getPropertyMappingConfiguration();
    }

    protected function addOptionToUploadFilesConverter(
        PropertyMappingConfiguration $propertyMappingConfiguration,
        string $optionKey,
        $optionValue
    ): void {
        $propertyMappingConfiguration->setTypeConverterOption(
            UploadMultipleFilesConverter::class,
            $optionKey,
            $optionValue
        );
    }
}
