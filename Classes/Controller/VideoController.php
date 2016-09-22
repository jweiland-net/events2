<?php

namespace JWeiland\Events2\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
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
