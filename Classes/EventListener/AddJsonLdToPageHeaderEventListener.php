<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\EventListener;

use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Event\PostProcessControllerActionEvent;
use JWeiland\Events2\Service\JsonLdService;
use JWeiland\Events2\Traits\IsValidEventListenerRequestTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Add JSON-LD information to page header
 */
#[AsEventListener('events2/addJsonLdToPageHeader')]
final readonly class AddJsonLdToPageHeaderEventListener
{
    use IsValidEventListenerRequestTrait;

    protected const ALLOWED_CONTROLLER_ACTIONS = [
        'Day' => [
            'show',
        ],
    ];

    public function __construct(
        private JsonLdService $jsonLdService,
    ) {}

    public function __invoke(PostProcessControllerActionEvent $controllerActionEvent): void
    {
        if (!$this->isValidRequest($controllerActionEvent)) {
            return;
        }

        if (!$controllerActionEvent->getDay() instanceof Day) {
            return;
        }

        $this->jsonLdService->addJsonLdToPageHeader($controllerActionEvent->getDay());
    }
}
