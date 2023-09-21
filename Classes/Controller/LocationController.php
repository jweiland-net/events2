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
use Psr\Http\Message\ResponseInterface;

/*
 * A simple controller to just show the location/address or maybe a Google Maps record
 */
class LocationController extends AbstractController
{
    public function showAction(Location $location): ResponseInterface
    {
        $this->postProcessAndAssignFluidVariables([
            'location' => $location
        ]);

        return $this->htmlResponse();
    }
}
