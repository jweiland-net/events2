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
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;

/**
 * If you work with the calendar plugin the timestamp will be given by $_GET. In that case "timestamp" has to be
 * allowed in PropertyMapper to be added into filter object.
 */
#[AsEventListener('events2/allowFilterByGetRequest')]
final readonly class AllowFilterByGetRequestEventListener
{
    use IsValidEventListenerRequestTrait;

    protected const ALLOWED_CONTROLLER_ACTIONS = [
        'Day' => [
            'list',
        ],
    ];

    public function __invoke(PreProcessControllerActionEvent $controllerActionEvent): void
    {
        if (!$this->isValidRequest($controllerActionEvent)) {
            return;
        }

        $arguments = $controllerActionEvent->getArguments();
        if (!$arguments->hasArgument('filter')) {
            return;
        }

        $pmc = $arguments
            ->getArgument('filter')
            ->getPropertyMappingConfiguration();

        $pmc->allowProperties('organizer', 'timestamp');

        $pmc->setTypeConverterOptions(
            PersistentObjectConverter::class,
            [
                PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true,
            ],
        );
    }
}
