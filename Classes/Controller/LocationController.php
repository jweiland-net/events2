<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

use JWeiland\Events2\Domain\Model\Location;

/*
 * A simple controller to just show the location/address or maybe a Google Maps record
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
