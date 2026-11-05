<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use JWeiland\Events2\Domain\Repository\UserRepository;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

/**
 * Contains all rules of the frontend management of events. It is used by
 * the RestrictAccessEventListener for the actions of the
 * ManagementController, by the SaveEventFinisher of EXT:form and by the
 * ModerationTokenViewHelper, so all of them share the same rules:
 *
 * - Create: Logged-in frontend users of the configured user group with an
 *   organizer only. There is no bypass for backend administrators, as a
 *   new event would be stored without organizer.
 * - Edit: The owner (organizer of the frontend user) of the event or a
 *   backend administrator.
 * - Activate: A backend administrator or everyone with a valid
 *   "moderationToken" of the signed mail link. Owners never get a token,
 *   so they can not activate their own events and bypass the moderation.
 *
 * The token signs the event UID only, not the "tstamp" of the event
 * record: In the EXT:form flow (NewEvent.form.yaml) the EmailToReceiver
 * finisher renders the mail before a later SaveEventFinisher step hides
 * the event. That step may change the "tstamp", which would invalidate the
 * link of the mail immediately. As the confirmation page always shows the
 * current data of the event, a long-living token is acceptable.
 *
 * Methods returning a "denial reason" return the label key of the reason,
 * why the action is not allowed. An empty string means: allowed.
 */
readonly class ManagementAccessService
{
    private const TABLE_EVENT = 'tx_events2_domain_model_event';

    private const TABLE_EVENT_ORGANIZER_MM = 'tx_events2_event_organizer_mm';

    /**
     * Do not change. Else all activation links of already sent mails get invalid.
     */
    private const MODERATION_TOKEN_SECRET = 'events2/event-moderation-link';

    private const MODERATION_TOKEN_ARGUMENT = 'moderationToken';

    public function __construct(
        private Context $context,
        private UserRepository $userRepository,
        private ConnectionPool $connectionPool,
        private HashService $hashService,
    ) {}

    public function getDenialReasonForCreate(int $userGroup): string
    {
        $userAspect = $this->context->getAspect('frontend.user');

        return match (true) {
            $userGroup <= 0 => 'userGroupNotConfigured',
            $userAspect->get('isLoggedIn') !== true => 'pluginNeedsLogin',
            !in_array($userGroup, $userAspect->get('groupIds'), true) => 'notAllowedToCreate',
            $this->getOrganizerOfFrontendUser() === 0 => 'missingOrganizerForCreate',
            default => '',
        };
    }

    public function canCreateEvent(int $userGroup): bool
    {
        return $this->getDenialReasonForCreate($userGroup) === '';
    }

    /**
     * Hidden events are respected, as events created or edited in frontend
     * are hidden until they get activated.
     */
    public function getDenialReasonForEdit(int $userGroup, int $eventUid): string
    {
        if ($this->isBackendAdmin()) {
            return $this->eventExists($eventUid) ? '' : 'unauthorizedOrganizerForEdit';
        }

        $denialReason = $this->getDenialReasonForCreate($userGroup);
        if ($denialReason !== '') {
            return $denialReason;
        }

        return $this->eventExists($eventUid, $this->getOrganizerOfFrontendUser())
            ? ''
            : 'unauthorizedOrganizerForEdit';
    }

    public function canEditEvent(int $userGroup, int $eventUid): bool
    {
        return $this->getDenialReasonForEdit($userGroup, $eventUid) === '';
    }

    /**
     * The list of own events. Backend administrators are allowed, too.
     */
    public function getDenialReasonForListing(int $userGroup): string
    {
        return $this->isBackendAdmin() ? '' : $this->getDenialReasonForCreate($userGroup);
    }

    public function canActivateEvent(int $eventUid, string $moderationToken): bool
    {
        return $this->isBackendAdmin() || $this->isValidModerationToken($moderationToken, $eventUid);
    }

    /**
     * Returns an empty string, if the event does not exist (anymore)
     */
    public function generateModerationToken(int $eventUid): string
    {
        if (!$this->eventExists($eventUid)) {
            return '';
        }

        return $this->hashService->hmac((string)$eventUid, self::MODERATION_TOKEN_SECRET);
    }

    /**
     * Returns the "moderationToken" argument of the request, if it was
     * generated for the given event. Else an empty string is returned.
     */
    public function getValidModerationToken(RequestInterface $request, int $eventUid): string
    {
        $moderationToken = $request->hasArgument(self::MODERATION_TOKEN_ARGUMENT)
            ? $request->getArgument(self::MODERATION_TOKEN_ARGUMENT)
            : '';

        return is_string($moderationToken) && $this->isValidModerationToken($moderationToken, $eventUid)
            ? $moderationToken
            : '';
    }

    /**
     * EXT:form finishers do not get the settings of the plugin. "userGroup"
     * is a TypoScript setting only, so it is read from the TypoScript of the
     * request like Extbase does: plugin.tx_events2_management overrides
     * plugin.tx_events2.
     */
    public function getUserGroupFromTypoScript(ServerRequestInterface $request): int
    {
        $pluginSetup = $this->getSetupArray($request)['plugin.'] ?? [];

        return (int)(
            $pluginSetup['tx_events2_management.']['settings.']['userGroup']
            ?? $pluginSetup['tx_events2.']['settings.']['userGroup']
            ?? 0
        );
    }

    private function isValidModerationToken(string $moderationToken, int $eventUid): bool
    {
        return $moderationToken !== ''
            && $this->eventExists($eventUid)
            && $this->hashService->validateHmac((string)$eventUid, self::MODERATION_TOKEN_SECRET, $moderationToken);
    }

    private function isBackendAdmin(): bool
    {
        return $this->context->getAspect('backend.user')->get('isAdmin') === true;
    }

    /**
     * Checks, if the event exists. With an organizer it has to be related to
     * this organizer, too. Hidden events are included, deleted events not.
     */
    private function eventExists(int $eventUid, ?int $organizerUid = null): bool
    {
        if ($eventUid <= 0 || $organizerUid === 0) {
            return false;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_EVENT);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $queryBuilder
            ->count('e.uid')
            ->from(self::TABLE_EVENT, 'e')
            ->where(
                $queryBuilder->expr()->eq('e.uid', $queryBuilder->createNamedParameter($eventUid, Connection::PARAM_INT)),
            );

        if ($organizerUid !== null) {
            $this->addOrganizerConstraint($queryBuilder, $organizerUid);
        }

        return (int)$queryBuilder->executeQuery()->fetchOne() > 0;
    }

    private function addOrganizerConstraint(QueryBuilder $queryBuilder, int $organizerUid): void
    {
        $queryBuilder->join(
            'e',
            self::TABLE_EVENT_ORGANIZER_MM,
            'mm',
            (string)$queryBuilder->expr()->and(
                $queryBuilder->expr()->eq('mm.uid_local', $queryBuilder->quoteIdentifier('e.uid')),
                $queryBuilder->expr()->eq(
                    'mm.uid_foreign',
                    $queryBuilder->createNamedParameter($organizerUid, Connection::PARAM_INT),
                ),
            ),
        );
    }

    /**
     * Without TypoScript setup no user group is configured, which denies access
     *
     * @return array<string, mixed>
     */
    private function getSetupArray(ServerRequestInterface $request): array
    {
        $frontendTypoScript = $request->getAttribute('frontend.typoscript');
        if (!$frontendTypoScript instanceof FrontendTypoScript) {
            return [];
        }

        try {
            return $frontendTypoScript->getSetupArray();
        } catch (\RuntimeException) {
            return [];
        }
    }

    private function getOrganizerOfFrontendUser(): int
    {
        return (int)$this->userRepository->getFieldFromUser('tx_events2_organizer');
    }
}
