<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

use JWeiland\Events2\Domain\Model\Location;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class LocationController extends AbstractController
{
    /**
     * action show.
     *
     * @param Location $location
     */
    public function showAction(Location $location)
    {
        $this->view->assign('location', $location);
    }
}
