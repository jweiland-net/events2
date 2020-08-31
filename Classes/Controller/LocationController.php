<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

use JWeiland\Events2\Domain\Model\Location;

/*
 * A simple controller to just show the location/address or maybe a Google Maps record
 */
class LocationController extends AbstractController
{
    /**
     * @param Location $location
     */
    public function showAction(Location $location): void
    {
        $this->view->assign('location', $location);
    }
}
