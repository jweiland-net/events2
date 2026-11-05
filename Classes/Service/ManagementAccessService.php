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
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Contains all rules of the frontend management of events. It is used by
 * the RestrictAccessEventListener for the actions of the
 * ManagementController, by the SaveEventFinisher and the
 * PrefillForEditUsageHook of EXT:form and by the ModerationTokenViewHelper,
 * so all of them share the same rules:
 *
 * - Create and list own events: Logged-in frontend users of the configured
 *   user group with an organizer only. There is no bypass for backend
 *   administrators, as they have no organizer.
 * - Edit: The owner (organizer of the frontend user) of the event or a
 *   backend administrator. The event to edit is always taken from the
 *   query parameter "tx_events2_management[event]", see getEventUid().
 * - Confirm activation: A backend administrator or everyone with a valid
 *   "moderationToken" of the signed mail link.
 * - Activate: Everyone with a valid "moderationToken". The confirmation
 *   page renders the token for backend administrators, too. That way the
 *   token protects the state changing request against CSRF. Owners never
 *   get a token, so they can not bypass the moderation.
 *
 * The token signs the event UID only, not the "tstamp" of the event
 * record: In the EXT:form flow (NewEvent.form.yaml) the EmailToReceiver
 * finisher renders the mail before a later SaveEventFinisher step hides
 * the event. That step may change the "tstamp", which would invalidate the
 * link of the mail immediately. As the confirmation page always shows the
 * current data of the event, a long-living token is acceptable.
 */
readonly class ManagementAccessService
{
    private const TABLE_EVENT = 'tx_events2_domain_model_event';

    private const TABLE_EVENT_ORGANIZER_MM = 'tx_events2_event_organizer_mm';

    private const PLUGIN_NAMESPACE = 'tx_events2_management';

    /**
     * Do not change. Else all activation links of already sent mails get invalid.
     */
    private const MODERATION_TOKEN_SECRET = 'events2/event-moderation-link';

    public function __construct(
        private Context $context,
        private UserRepository $userRepository,
        private ConnectionPool $connectionPool,
        private HashService $hashService,
    ) {}

    /**
     * Returns the UID of the event to edit. 0 means: create a new event.
     * It is read from the query only. So a manipulated POST body can not
     * change the event, which was checked by getDenialReason().
     */
    public function getEventUid(ServerRequestInterface $request): int
    {
        $eventUid = $request->getQueryParams()[self::PLUGIN_NAMESPACE]['event'] ?? 0;

        return MathUtility::canBeInterpretedAsInteger($eventUid) ? max(0, (int)$eventUid) : 0;
    }

    /**
     * Returns the label key of the reason, why the current user is not
     * allowed to create, list or edit events. Without an event UID in the
     * query (see getEventUid()) the rules for creating and listing events
     * apply, else the rules for editing this event. An empty string means:
     * allowed. Not existing and foreign events result in the same reason.
     *
     * The user group is read from TypoScript, as EXT:form finishers do not
     * get the settings of the plugin.
     */
    public function getDenialReason(ServerRequestInterface $request): string
    {
        $eventUid = $this->getEventUid($request);
        if ($eventUid > 0 && $this->isBackendAdmin()) {
            return $this->eventExists($eventUid) ? '' : 'unauthorizedOrganizerForEdit';
        }

        $denialReason = $this->getDenialReasonForFrontendUser($this->getUserGroup($request));
        if ($denialReason !== '' || $eventUid === 0) {
            return $denialReason;
        }

        return $this->eventExists($eventUid, $this->getOrganizerOfFrontendUser())
            ? ''
            : 'unauthorizedOrganizerForEdit';
    }

    public function canConfirmActivation(int $eventUid, string $moderationToken): bool
    {
        return $this->isBackendAdmin()
            ? $this->eventExists($eventUid)
            : $this->canActivateEvent($eventUid, $moderationToken);
    }

    public function canActivateEvent(int $eventUid, string $moderationToken): bool
    {
        return $moderationToken !== ''
            && $this->eventExists($eventUid)
            && $this->hashService->validateHmac((string)$eventUid, self::MODERATION_TOKEN_SECRET, $moderationToken);
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

    private function getDenialReasonForFrontendUser(int $userGroup): string
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
            ->where($queryBuilder->expr()->eq('e.uid', $queryBuilder->createNamedParameter($eventUid, Connection::PARAM_INT)));

        if ($organizerUid !== null) {
            $organizerParameter = $queryBuilder->createNamedParameter($organizerUid, Connection::PARAM_INT);
            $queryBuilder->join('e', self::TABLE_EVENT_ORGANIZER_MM, 'mm', (string)$queryBuilder->expr()->and(
                $queryBuilder->expr()->eq('mm.uid_local', $queryBuilder->quoteIdentifier('e.uid')),
                $queryBuilder->expr()->eq('mm.uid_foreign', $organizerParameter),
            ));
        }

        return (int)$queryBuilder->executeQuery()->fetchOne() > 0;
    }

    /**
     * Read like Extbase does: plugin.tx_events2_management overrides
     * plugin.tx_events2. Without TypoScript no user group is configured,
     * which denies access.
     */
    private function getUserGroup(ServerRequestInterface $request): int
    {
        $frontendTypoScript = $request->getAttribute('frontend.typoscript');
        try {
            $pluginSetup = $frontendTypoScript instanceof FrontendTypoScript
                ? $frontendTypoScript->getSetupArray()['plugin.'] ?? []
                : [];
        } catch (\RuntimeException) {
            $pluginSetup = [];
        }

        return (int)(
            $pluginSetup[self::PLUGIN_NAMESPACE . '.']['settings.']['userGroup']
            ?? $pluginSetup['tx_events2.']['settings.']['userGroup']
            ?? 0
        );
    }

    private function getOrganizerOfFrontendUser(): int
    {
        return (int)$this->userRepository->getFieldFromUser('tx_events2_organizer');
    }
}
