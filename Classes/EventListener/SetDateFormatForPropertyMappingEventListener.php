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
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;

/*
 * Remove videoLink if empty.
 * Add special validation for VideoLink id exists.
 * I can't add this validation to LinkModel, as such a validation would be also valid for organizer link.
 */
class SetDateFormatForPropertyMappingEventListener extends AbstractControllerEventListener
{
    /**
     * @var string
     */
    protected $defaultDateFormat = 'd.m.Y';

    protected $allowedControllerActions = [
        'Event' => [
            'create',
            'update'
        ]
    ];

    public function __invoke(PreProcessControllerActionEvent $event): void
    {
        if (
            $this->isValidRequest($event)
        ) {
            $eventMappingConfiguration = $event->getArguments()
                ->getArgument('event')
                ->getPropertyMappingConfiguration();

            $this->setDatePropertyFormat('eventBegin', $eventMappingConfiguration);
            $this->setDatePropertyFormat('eventEnd', $eventMappingConfiguration);
        }
    }

    protected function setDatePropertyFormat(
        string $property,
        PropertyMappingConfigurationInterface $pmc
    ): void {
        $pmc
            ->forProperty($property)
            ->setTypeConverterOption(
                DateTimeConverter::class,
                DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                'd.m.Y'
            );
    }
}
