<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class VideoController extends AbstractController
{
    /**
     * action show
     *
     * @param int $event
     *
     * @return void
     */
    public function showAction($event)
    {
        $event = $this->eventRepository->findByIdentifier((int)$event);
        $this->view->assign('event', $event);
    }
}
