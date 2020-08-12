<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Ajax;

use JWeiland\Events2\Domain\Repository\LocationRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/*
 * This class will be loaded, if you create a new event in frontend. There we have a
 * selectbox for location, which searches for Locations by its name and stores the
 * location UID in a hidden field.
 */
class FindLocations
{
    /**
     * @var LocationRepository
     */
    protected $locationRepository;

    public function __construct(LocationRepository $locationRepository = null)
    {
        if ($locationRepository === null) {
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            $this->locationRepository = $objectManager->get(LocationRepository::class);
        } else {
            $this->locationRepository = $locationRepository;
        }
    }
    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getQueryParams()['tx_events2_events']['arguments'] ?? [];

        //ExtensionManagementUtility::loadBaseTca(true);

        // Hint: search may fail with "&" in $locationPart
        $locationPart = trim(htmlspecialchars(strip_tags($parameters['locationPart'])));
        // keep it in sync to minLength in JS
        if (empty($locationPart) || strlen($locationPart) <= 2) {
            return new JsonResponse('');
        }
        return new JsonResponse($this->locationRepository->findLocations($locationPart));
    }
}
