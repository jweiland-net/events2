<?php

namespace JWeiland\Events2\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Stefan Froemken <projects@jweiland.net>, jweiland.net
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class VideoController extends ActionController
{

    /**
     * eventRepository
     *
     * @var \JWeiland\Events2\Domain\Repository\EventRepository
     */
    protected $eventRepository;

    /**
     * inject event repository
     *
     * @param \JWeiland\Events2\Domain\Repository\EventRepository $eventRepository
     * @return void
     */
    public function injectEventRepository(\JWeiland\Events2\Domain\Repository\EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    /**
     * action show
     *
     * @param int $event
     * @return void
     */
    public function showAction($event)
    {
        $event = $this->eventRepository->findByIdentifier((int) $event);
        $this->view->assign('event', $event);
    }

}
