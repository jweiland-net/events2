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

/**
 * Add JSON-LD information to page header
 */
class AddJsonLdToPageHeaderEventListener extends AbstractControllerEventListener
{
    /**
     * @var JsonLdService
     */
    protected $jsonLdService;

    protected $allowedControllerActions = [
        'Day' => [
            'show'
        ]
    ];

    public function __construct(JsonLdService $jsonLdService)
    {
        $this->jsonLdService = $jsonLdService;
    }

    public function __invoke(PostProcessControllerActionEvent $event): void
    {
        if (
            $this->isValidRequest($event)
            && $event->getDay() instanceof Day
        ) {
            $this->jsonLdService->addJsonLdToPageHeader($event->getDay());
        }
    }
}
