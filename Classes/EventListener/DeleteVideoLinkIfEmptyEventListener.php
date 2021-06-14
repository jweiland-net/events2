<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\EventListener;

use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Domain\Repository\LinkRepository;
use JWeiland\Events2\Event\PostProcessControllerActionEvent;

/*
 * To prevent problems while PropertyMapping we remove the video link from request, if link is empty.
 * Extbase can not set deleted=1 itself.
 */
class DeleteVideoLinkIfEmptyEventListener extends AbstractControllerEventListener
{
    /**
     * @var LinkRepository
     */
    protected $linkRepository;

    protected $allowedControllerActions = [
        'Event' => [
            'create',
            'update'
        ]
    ];

    public function __construct(LinkRepository $linkRepository)
    {
        $this->linkRepository = $linkRepository;
    }

    public function __invoke(PostProcessControllerActionEvent $event): void
    {
        if (
            $this->isValidRequest($event)
            && ($eventObject = $event->getEvent())
            && $eventObject->getVideoLink() instanceof Link
            && empty($eventObject->getVideoLink()->getLink())
        ) {
            $this->linkRepository->remove($eventObject->getVideoLink());
            $eventObject->setVideoLink();
        }
    }
}
