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
 * We have build our own form-tag for search plugin, so extbase will not configure PropertyMappingConfiguration
 * automatically. We have to do it manually here.
 * With fluid-form-VHs the $_GET request in browser URL will get extremely long.
 */
#[AsEventListener('events2/allowSearchParameter')]
final readonly class AllowSearchParameterEventListener
{
    use IsValidEventListenerRequestTrait;

    protected const ALLOWED_CONTROLLER_ACTIONS = [
        'Search' => [
            'listSearchResults',
            'show',
        ],
    ];

    public function __invoke(PreProcessControllerActionEvent $controllerActionEvent): void
    {
        if ($this->isValidRequest($controllerActionEvent)) {
            $pmc = $controllerActionEvent->getArguments()
                ->getArgument('search')
                ->getPropertyMappingConfiguration();

            $pmc->allowAllProperties();

            $pmc->setTypeConverterOptions(
                PersistentObjectConverter::class,
                [
                    PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true,
                ],
            );
        }
    }
}
