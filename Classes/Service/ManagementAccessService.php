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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;

/**
 * Checks, if the current visitor is allowed to create or edit events in
 * frontend. It is used by the RestrictAccessEventListener for the actions of
 * the ManagementController and by the SaveEventFinisher of EXT:form, so both
 * ways to store an event share the same rules.
 */
readonly class ManagementAccessService
{
    private const TABLE_EVENT_ORGANIZER_MM = 'tx_events2_event_organizer_mm';

    public function __construct(
        private Context $context,
        private UserRepository $userRepository,
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * Returns the label key of the reason, why the current frontend user is
     * not allowed to create or edit events. An empty string means: allowed.
     */
    public function getDenialReasonForFrontendUser(int $userGroup): string
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

    public function isFrontendUserAllowed(int $userGroup): bool
    {
        return $this->getDenialReasonForFrontendUser($userGroup) === '';
    }

    public function isBackendAdmin(): bool
    {
        return $this->context->getAspect('backend.user')->get('isAdmin') === true;
    }

    /**
     * Checks the organizer relation in the database. Hidden events are
     * included, as events created in frontend are hidden until activation.
     */
    public function isEventOfFrontendUser(int $eventUid): bool
    {
        $organizerUid = $this->getOrganizerOfFrontendUser();
        if ($eventUid <= 0 || $organizerUid === 0) {
            return false;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_EVENT_ORGANIZER_MM);

        return (int)$queryBuilder
            ->count('*')
            ->from(self::TABLE_EVENT_ORGANIZER_MM)
            ->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter($eventUid)),
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($organizerUid)),
            )
            ->executeQuery()
            ->fetchOne() > 0;
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
