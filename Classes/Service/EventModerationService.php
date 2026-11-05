<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

/**
 * The activation links of the notification mails are signed with a
 * "moderationToken". It authorizes the administrator, who is never logged
 * in as the frontend user who owns the event, to confirm and activate a
 * hidden event. Owners of an event never get a token, so they can not
 * activate their own events and bypass the moderation.
 *
 * The token signs the event UID only, not the "tstamp" of the event
 * record: In the EXT:form flow (NewEvent.form.yaml) the EmailToReceiver
 * finisher renders the mail before a later SaveEventFinisher step hides
 * the event. That step may change the "tstamp", which would invalidate the
 * link of the mail immediately. As the confirmation page always shows the
 * current data of the event, a long-living token is acceptable.
 */
readonly class EventModerationService
{
    private const TABLE_EVENT = 'tx_events2_domain_model_event';

    private const MODERATION_TOKEN_SECRET = 'events2/event-moderation-link';

    private const MODERATION_TOKEN_ARGUMENT = 'moderationToken';

    public function __construct(
        private ConnectionPool $connectionPool,
        private HashService $hashService,
    ) {}

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

    public function isValidModerationToken(string $moderationToken, int $eventUid): bool
    {
        return $moderationToken !== ''
            && $this->eventExists($eventUid)
            && $this->hashService->validateHmac((string)$eventUid, self::MODERATION_TOKEN_SECRET, $moderationToken);
    }

    /**
     * Hidden events are respected, as events created or updated in frontend
     * are hidden until they get activated. Deleted events are ignored.
     */
    private function eventExists(int $eventUid): bool
    {
        if ($eventUid <= 0) {
            return false;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_EVENT);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        return $queryBuilder
            ->count('uid')
            ->from(self::TABLE_EVENT)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($eventUid, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne() > 0;
    }
}
