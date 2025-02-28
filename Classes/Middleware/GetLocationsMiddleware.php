<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Middleware;

use JWeiland\Events2\Domain\Repository\LocationRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

/**
 * This middleware is needed for management plugin. It is needed to show a list of location titles
 * in AutoComplete input box.
 */
final readonly class GetLocationsMiddleware implements MiddlewareInterface
{
    /**
     * Will be called by call_user_func_array, so don't add Extbase classes with inject methods as argument
     */
    public function __construct(
        protected LocationRepository $locationRepository
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getHeader('ext-events2') !== ['getLocations']) {
            return $handler->handle($request);
        }

        // Hint: search may fail with "&" in $search
        $search = trim(htmlspecialchars(strip_tags(
            $request->getQueryParams()['events2SearchLocation'] ?? '',
        )));

        if ($search === '') {
            return new JsonResponse();
        }

        // keep it in sync to minLength in JS
        if (strlen($search) <= 2) {
            return new JsonResponse();
        }

        return new JsonResponse($this->locationRepository->findLocations($search));
    }
}
