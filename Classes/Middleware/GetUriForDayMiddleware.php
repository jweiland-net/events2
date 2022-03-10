<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Middleware;

use JWeiland\Events2\Utility\DateTimeUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/*
 * This middleware is needed for jQuery calendar. If you click on a day this class will be called
 * and returns the URI to the expected events for given day.
 */
class GetUriForDayMiddleware implements MiddlewareInterface
{
    protected UriBuilder $uriBuilder;

    protected DateTimeUtility $dateTimeUtility;

    /**
     * Will be called by call_user_func_array, so don't add Extbase classes with inject methods as argument
     */
    public function __construct(
        UriBuilder $uriBuilder,
        DateTimeUtility $dateTimeUtility
    ) {
        $this->uriBuilder = $uriBuilder;
        $this->dateTimeUtility = $dateTimeUtility;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getHeader('ext-events2') !== ['getUriForDay']) {
            return $handler->handle($request);
        }

        $startDate = $this->getStartDateFromRequest($request);
        if ($startDate === null) {
            return new JsonResponse([
                'error' => 'Date can not be generated. Missing day, month or year in request.'
            ], 400);
        }

        $uri = $this->getUriWithTimestamp((int)$startDate->format('U'), $request);

        if ($uri === '[MISSING]') {
            return new JsonResponse([
                'errors' => 'URI can not be build, as pidOfListPage is missing in request.'
            ], 400);
        }

        if ($uri === '[0]') {
            return new JsonResponse([
                'errors' => 'pidOfListPage in request can not be 0.'
            ], 400);
        }

        return new JsonResponse(['uri' => $uri]);
    }

    protected function getStartDateFromRequest(ServerRequestInterface $request): ?\DateTimeImmutable
    {
        $getParameters = $request->getQueryParams();

        if (!isset($getParameters['day'], $getParameters['month'], $getParameters['year'])) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat(
            'j.n.Y H:i:s',
            sprintf(
                '%d.%d.%d 00:00:00',
                MathUtility::forceIntegerInRange($getParameters['day'], 1, 31),
                MathUtility::forceIntegerInRange($getParameters['month'], 1, 12),
                (int)$getParameters['year']
            ),
            new \DateTimeZone(date_default_timezone_get())
        );
    }

    protected function getUriWithTimestamp(int $timestamp, ServerRequestInterface $request): string
    {
        $getParameters = $request->getQueryParams();

        if (!isset($getParameters['pidOfListPage'])) {
            return '[MISSING]';
        }

        $pidOfListPage = MathUtility::convertToPositiveInteger($getParameters['pidOfListPage']);

        if ($pidOfListPage === 0) {
            return '[0]';
        }

        return $this->uriBuilder
            ->reset()
            ->setTargetPageUid($pidOfListPage)
            ->setCreateAbsoluteUri(true)
            ->uriFor(
                'showByTimestamp',
                ['timestamp' => $timestamp],
                'Day',
                'events2',
                'events'
            );
    }
}
