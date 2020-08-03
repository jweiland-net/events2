<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

/*
 * A simple controller to show video-link as YouTube-Implementation
 */
class VideoController extends AbstractController
{
    /**
     * @param int $event
     * @return void
     */
    public function showAction(int $event): void
    {
        $event = $this->eventRepository->findByIdentifier((int)$event);
        $this->view->assign('event', $event);
    }
}
