<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Middleware;

use JWeiland\Events2\Domain\Repository\UserRepository;
use JWeiland\Events2\Traits\Typo3RequestTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * There is no hidden text-field in new-form for current user (organizer) which will prevent modifying that value
 * and creating events with wrong organizer. This middleware will attach the organizer to event record in $_POST.
 *
 * As we don't have access to extbase PropertyMappingConfiguration here to allow this unknown organizer
 * property to event there is EventListener "AddOrganizerToEventRecordEventListener" to configure that.
 *
 * There is no need to update the organizer in edit/update process. The organizer will stay the same.
 * There is no need to adopt that for our EXT:form solution. It will be attached via {__currentOrganizer} there.
 */
class AttachOrganizerToEventMiddleware implements MiddlewareInterface
{
    use Typo3RequestTrait;

    public function __construct(
        protected readonly UserRepository $userRepository,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (
            ($postArguments = $this->getPostFromRequest($request))
            && ($managementArguments = $this->getMergedWithPostFromRequest('tx_events2_management', $request))
            && (($managementArguments['action'] ?? '') === 'create')
            && ($eventRecord = ($managementArguments['event'] ?? []))
            && !array_key_exists('organizers', $eventRecord)
            && ($organizerOfCurrentUser = $this->userRepository->getFieldFromUser('tx_events2_organizer'))
            && MathUtility::canBeInterpretedAsInteger($organizerOfCurrentUser)
        ) {
            $postArguments['tx_events2_management']['event']['organizers'] = [];
            $postArguments['tx_events2_management']['event']['organizers'][0] = $organizerOfCurrentUser;

            $request = $request->withParsedBody($postArguments);
        }

        return $handler->handle($request);
    }
}
