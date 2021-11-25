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
use JWeiland\Events2\Event\PostProcessControllerActionEvent;
use JWeiland\Events2\Helper\PathSegmentHelper;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * Update path_segment of event.
 * Please check, if this EventListener was loaded before other redirecting EventListeners.
 */
class UpdateEventPathSegmentEventListener extends AbstractControllerEventListener
{
    protected PathSegmentHelper $pathSegmentHelper;

    protected EventRepository $eventRepository;

    protected PersistenceManagerInterface $persistenceManager;

    /**
     * It should never be possible for a FE user to generate slug while update request. This would also change the
     * link to the detail page. If it was needed to change the link, please update slug in TYPO3 backend.
     */
    protected array $allowedControllerActions = [
        'Event' => [
            'create'
        ]
    ];

    public function __construct(
        PathSegmentHelper $pathSegmentHelper,
        EventRepository $eventRepository,
        PersistenceManagerInterface $persistenceManager
    ) {
        $this->pathSegmentHelper = $pathSegmentHelper;
        $this->eventRepository = $eventRepository;
        $this->persistenceManager = $persistenceManager;
    }

    public function __invoke(PostProcessControllerActionEvent $event): void
    {
        if (
            $this->isValidRequest($event)
            && $event->getEvent() instanceof Event
        ) {
            $this->pathSegmentHelper->updatePathSegmentForEvent($event->getEvent());
            $pathSegment = $event->getEvent()->getPathSegment();

            if ($pathSegment === '' || $pathSegment === '/') {
                throw new \Exception(
                    'Path Segment of event is empty. Please check pathSegmentType in Extension Settings',
                    1611157656
                );
            }
        }
    }
}
