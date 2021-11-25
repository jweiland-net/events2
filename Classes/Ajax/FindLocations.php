<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Ajax;

use JWeiland\Events2\Domain\Repository\LocationRepository;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

/*
 * This class will be loaded, if you create a new event in frontend. There we have a
 * selector for location, which searches for Locations by its name and stores the
 * location UID in a hidden field.
 */
class FindLocations
{
    protected LocationRepository $locationRepository;

    /**
     * Will be called by call_user_func_array, so don't add Extbase classes with inject methods as argument
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->locationRepository = $objectManager->get(LocationRepository::class);
    }

    public function processRequest(ServerRequestInterface $request): JsonResponse
    {
        $parameters = $request->getQueryParams()['tx_events2_events']['arguments'] ?? [];

        // Hint: search may fail with "&" in $search
        $search = trim(htmlspecialchars(strip_tags($parameters['search'])));

        // keep it in sync to minLength in JS
        if (empty($search)) {
            return new JsonResponse();
        }

        if (strlen($search) <= 2) {
            return new JsonResponse();
        }

        return new JsonResponse($this->locationRepository->findLocations($search));
    }
}
