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

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DayController extends \JWeiland\Events2\Controller\AbstractController
{
    /**
     * action show.
     *
     * Hint: I call showAction with int instead of DomainModel
     * to prevent that recursive validators will be called
     *
     * @param int $day
     */
    public function showAction($day)
    {
        /** @var \JWeiland\Events2\Domain\Model\Day $dayObject */
        $dayObject = $this->dayRepository->findByIdentifier($day);
        /** @var \JWeiland\Events2\Domain\Model\Event $event */
        foreach ($dayObject->getEvents($this->settings['categories']) as $event) {
            $event->setDay($dayObject);
        }
        $this->view->assign('day', $dayObject);
    }
}
