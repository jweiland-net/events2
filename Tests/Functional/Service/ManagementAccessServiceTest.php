<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Service;

use JWeiland\Events2\Service\ManagementAccessService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\FrontendUserTrait;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test
 */
class ManagementAccessServiceTest extends FunctionalTestCase
{
    use FrontendUserTrait;
    use InsertEventTrait;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'phpTimeZone' => Events2Constants::PHP_TIMEZONE,
        ],
    ];

    private ManagementAccessService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->get(ManagementAccessService::class);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);

        parent::tearDown();
    }

    #[Test]
    public function getDenialReasonWithoutConfiguredUserGroupReturnsUserGroupNotConfigured(): void
    {
        $this->loginFrontendUser(1);

        self::assertSame(
            'userGroupNotConfigured',
            $this->subject->getDenialReason($this->buildRequest([])),
        );
    }

    #[Test]
    public function getDenialReasonWithoutLoggedInUserReturnsPluginNeedsLogin(): void
    {
        self::assertSame(
            'pluginNeedsLogin',
            $this->subject->getDenialReason($this->buildRequest()),
        );
    }

    #[Test]
    public function getDenialReasonWithUserOfOtherGroupReturnsNotAllowedToCreate(): void
    {
        $this->loginFrontendUser(1, [2]);

        self::assertSame(
            'notAllowedToCreate',
            $this->subject->getDenialReason($this->buildRequest()),
        );
    }

    #[Test]
    public function getDenialReasonWithUserWithoutOrganizerReturnsMissingOrganizer(): void
    {
        $this->loginFrontendUser(0);

        self::assertSame(
            'missingOrganizerForCreate',
            $this->subject->getDenialReason($this->buildRequest()),
        );
    }

    #[Test]
    public function getDenialReasonWithUserOfGroupWithOrganizerReturnsEmptyString(): void
    {
        $this->loginFrontendUser(1);

        self::assertSame(
            '',
            $this->subject->getDenialReason($this->buildRequest()),
        );
    }

    #[Test]
    public function getDenialReasonReadsUserGroupOfPluginFirst(): void
    {
        $this->loginFrontendUser(1, [7]);

        self::assertSame(
            '',
            $this->subject->getDenialReason($this->buildRequest([
                'tx_events2.' => ['settings.' => ['userGroup' => '5']],
                'tx_events2_management.' => ['settings.' => ['userGroup' => '7']],
            ])),
        );
    }

    #[Test]
    public function getDenialReasonWithoutTypoScriptReturnsUserGroupNotConfigured(): void
    {
        $this->loginFrontendUser(1);

        self::assertSame(
            'userGroupNotConfigured',
            $this->subject->getDenialReason(new ServerRequest('https://www.example.com/')),
        );
    }

    #[Test]
    public function getDenialReasonWithoutSetupArrayReturnsUserGroupNotConfigured(): void
    {
        $this->loginFrontendUser(1);

        self::assertSame(
            'userGroupNotConfigured',
            $this->subject->getDenialReason(
                (new ServerRequest('https://www.example.com/'))
                    ->withAttribute('frontend.typoscript', new FrontendTypoScript(new RootNode(), [], [], [])),
            ),
        );
    }

    /**
     * A backend administrator has no organizer. The new event would be
     * stored without organizer.
     */
    #[Test]
    public function getDenialReasonForNewEventWithBackendAdminReturnsPluginNeedsLogin(): void
    {
        $this->loginBackendUser(true);

        self::assertSame(
            'pluginNeedsLogin',
            $this->subject->getDenialReason($this->buildRequest()),
        );
    }

    #[Test]
    public function getDenialReasonWithOwnHiddenEventReturnsEmptyString(): void
    {
        $eventUid = $this->insertEventOfOrganizer(['hidden' => 1]);
        $this->loginFrontendUser(1);

        self::assertSame(
            '',
            $this->subject->getDenialReason($this->buildRequest(eventUid: $eventUid)),
        );
    }

    #[Test]
    public function getDenialReasonWithForeignEventReturnsUnauthorizedOrganizer(): void
    {
        $eventUid = $this->insertEventOfOrganizer();
        $this->loginFrontendUser(2);

        self::assertSame(
            'unauthorizedOrganizerForEdit',
            $this->subject->getDenialReason($this->buildRequest(eventUid: $eventUid)),
        );
    }

    /**
     * Not existing and foreign events must not be distinguishable
     */
    #[Test]
    public function getDenialReasonWithNotExistingEventReturnsUnauthorizedOrganizer(): void
    {
        $this->loginFrontendUser(1);

        self::assertSame(
            'unauthorizedOrganizerForEdit',
            $this->subject->getDenialReason($this->buildRequest(eventUid: 4711)),
        );
    }

    #[Test]
    public function getDenialReasonWithOwnEventButUserOfOtherGroupReturnsNotAllowedToCreate(): void
    {
        $eventUid = $this->insertEventOfOrganizer();
        $this->loginFrontendUser(1, [2]);

        self::assertSame(
            'notAllowedToCreate',
            $this->subject->getDenialReason($this->buildRequest(eventUid: $eventUid)),
        );
    }

    #[Test]
    public function getDenialReasonWithEventWithoutOrganizerReturnsUnauthorizedOrganizer(): void
    {
        $eventUid = $this->insertEvent(
            title: 'Event without organizer',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
        );
        $this->loginFrontendUser(1);

        self::assertSame(
            'unauthorizedOrganizerForEdit',
            $this->subject->getDenialReason($this->buildRequest(eventUid: $eventUid)),
        );
    }

    #[Test]
    public function getDenialReasonWithOwnDeletedEventReturnsUnauthorizedOrganizer(): void
    {
        $eventUid = $this->insertEventOfOrganizer(['deleted' => 1]);
        $this->loginFrontendUser(1);

        self::assertSame(
            'unauthorizedOrganizerForEdit',
            $this->subject->getDenialReason($this->buildRequest(eventUid: $eventUid)),
        );
    }

    #[Test]
    public function getDenialReasonWithExistingEventAndBackendAdminReturnsEmptyString(): void
    {
        $eventUid = $this->insertEventOfOrganizer(['hidden' => 1]);
        $this->loginBackendUser(true);

        self::assertSame(
            '',
            $this->subject->getDenialReason($this->buildRequest(eventUid: $eventUid)),
        );
    }

    #[Test]
    public function getDenialReasonWithNotExistingEventAndBackendAdminReturnsUnauthorizedOrganizer(): void
    {
        $this->loginBackendUser(true);

        self::assertSame(
            'unauthorizedOrganizerForEdit',
            $this->subject->getDenialReason($this->buildRequest(eventUid: 4711)),
        );
    }

    #[Test]
    public function getDenialReasonWithEventAndBackendEditorReturnsPluginNeedsLogin(): void
    {
        $eventUid = $this->insertEventOfOrganizer();
        $this->loginBackendUser(false);

        self::assertSame(
            'pluginNeedsLogin',
            $this->subject->getDenialReason($this->buildRequest(eventUid: $eventUid)),
        );
    }

    /**
     * Extbase maps POST over GET. The event to edit must come from the
     * query only, so the checked event is the stored event.
     */
    #[Test]
    public function getEventUidIgnoresEventInPostBody(): void
    {
        $request = $this->buildRequest(eventUid: 3)
            ->withParsedBody(['tx_events2_management' => ['event' => 5]]);

        self::assertSame(
            3,
            $this->subject->getEventUid($request),
        );
    }

    public static function invalidEventArgumentDataProvider(): array
    {
        return [
            'missing' => [null],
            'array' => [['__identity' => 1]],
            'string' => ['1abc'],
            'negative' => ['-1'],
        ];
    }

    #[Test]
    #[DataProvider('invalidEventArgumentDataProvider')]
    public function getEventUidWithInvalidEventArgumentReturnsZero(mixed $eventArgument): void
    {
        $queryParams = $eventArgument === null ? [] : ['tx_events2_management' => ['event' => $eventArgument]];

        self::assertSame(
            0,
            $this->subject->getEventUid((new ServerRequest('https://www.example.com/'))->withQueryParams($queryParams)),
        );
    }

    #[Test]
    public function canActivateEventWithValidTokenReturnsTrue(): void
    {
        $eventUid = $this->insertHiddenEvent();

        self::assertTrue(
            $this->subject->canActivateEvent($eventUid, $this->subject->generateModerationToken($eventUid)),
        );
    }

    /**
     * The token protects the state changing request against CSRF.
     * The confirmation page renders the token for administrators, too.
     */
    #[Test]
    public function canActivateEventWithBackendAdminWithoutTokenReturnsFalse(): void
    {
        $eventUid = $this->insertHiddenEvent();
        $this->loginBackendUser(true);

        self::assertFalse(
            $this->subject->canActivateEvent($eventUid, ''),
        );
    }

    /**
     * Owners never get a token. They can not bypass the moderation.
     */
    #[Test]
    public function canActivateEventWithOwnerWithoutTokenReturnsFalse(): void
    {
        $eventUid = $this->insertEventOfOrganizer(['hidden' => 1]);
        $this->loginFrontendUser(1);

        self::assertFalse(
            $this->subject->canActivateEvent($eventUid, ''),
        );
    }

    #[Test]
    public function canActivateEventWithTokenOfForeignEventReturnsFalse(): void
    {
        $eventUid = $this->insertHiddenEvent();
        $foreignEventUid = $this->insertHiddenEvent();

        self::assertFalse(
            $this->subject->canActivateEvent($eventUid, $this->subject->generateModerationToken($foreignEventUid)),
        );
    }

    #[Test]
    public function canActivateEventWithInvalidTokenReturnsFalse(): void
    {
        $eventUid = $this->insertHiddenEvent();

        self::assertFalse(
            $this->subject->canActivateEvent($eventUid, 'invalid'),
        );
    }

    #[Test]
    public function canActivateEventForDeletedEventReturnsFalse(): void
    {
        $eventUid = $this->insertHiddenEvent();
        $moderationToken = $this->subject->generateModerationToken($eventUid);

        $this->getConnectionPool()
            ->getConnectionForTable('tx_events2_domain_model_event')
            ->update('tx_events2_domain_model_event', ['deleted' => 1], ['uid' => $eventUid]);

        self::assertFalse(
            $this->subject->canActivateEvent($eventUid, $moderationToken),
        );
    }

    #[Test]
    public function canActivateEventStaysValidAfterEventWasModified(): void
    {
        $eventUid = $this->insertHiddenEvent();
        $moderationToken = $this->subject->generateModerationToken($eventUid);

        $this->getConnectionPool()
            ->getConnectionForTable('tx_events2_domain_model_event')
            ->update('tx_events2_domain_model_event', ['tstamp' => time() + 60], ['uid' => $eventUid]);

        self::assertTrue(
            $this->subject->canActivateEvent($eventUid, $moderationToken),
        );
    }

    #[Test]
    public function canConfirmActivationWithValidTokenReturnsTrue(): void
    {
        $eventUid = $this->insertHiddenEvent();

        self::assertTrue(
            $this->subject->canConfirmActivation($eventUid, $this->subject->generateModerationToken($eventUid)),
        );
    }

    #[Test]
    public function canConfirmActivationWithBackendAdminWithoutTokenReturnsTrue(): void
    {
        $eventUid = $this->insertHiddenEvent();
        $this->loginBackendUser(true);

        self::assertTrue(
            $this->subject->canConfirmActivation($eventUid, ''),
        );
    }

    #[Test]
    public function canConfirmActivationWithBackendAdminAndNotExistingEventReturnsFalse(): void
    {
        $this->loginBackendUser(true);

        self::assertFalse(
            $this->subject->canConfirmActivation(4711, ''),
        );
    }

    #[Test]
    public function canConfirmActivationWithBackendEditorWithoutTokenReturnsFalse(): void
    {
        $eventUid = $this->insertHiddenEvent();
        $this->loginBackendUser(false);

        self::assertFalse(
            $this->subject->canConfirmActivation($eventUid, ''),
        );
    }

    /**
     * Activation links of already sent mails have to stay valid. So the
     * token format must not change.
     */
    #[Test]
    public function generateModerationTokenKeepsFormatOfSentActivationLinks(): void
    {
        $eventUid = $this->insertHiddenEvent();

        self::assertSame(
            $this->get(HashService::class)->hmac((string)$eventUid, 'events2/event-moderation-link'),
            $this->subject->generateModerationToken($eventUid),
        );
    }

    #[Test]
    public function generateModerationTokenReturnsTokenForExistingEvent(): void
    {
        $eventUid = $this->insertEvent('Event', new \DateTimeImmutable('tomorrow midnight'));

        self::assertNotSame(
            '',
            $this->subject->generateModerationToken($eventUid),
        );
    }

    #[Test]
    public function generateModerationTokenReturnsDifferentTokensForDifferentEvents(): void
    {
        $firstEventUid = $this->insertHiddenEvent();
        $secondEventUid = $this->insertHiddenEvent();

        self::assertNotSame(
            $this->subject->generateModerationToken($firstEventUid),
            $this->subject->generateModerationToken($secondEventUid),
        );
    }

    public static function invalidEventUidDataProvider(): array
    {
        return [
            'zero' => [0],
            'negative' => [-1],
            'not existing' => [4711],
        ];
    }

    #[Test]
    #[DataProvider('invalidEventUidDataProvider')]
    public function generateModerationTokenForInvalidEventUidReturnsEmptyString(int $eventUid): void
    {
        self::assertSame(
            '',
            $this->subject->generateModerationToken($eventUid),
        );
    }

    #[Test]
    public function generateModerationTokenForDeletedEventReturnsEmptyString(): void
    {
        $eventUid = $this->insertEvent(
            'Deleted event',
            new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: ['deleted' => 1],
        );

        self::assertSame(
            '',
            $this->subject->generateModerationToken($eventUid),
        );
    }

    /**
     * The first organizer gets UID 1, which is the organizer of the
     * frontend user logged in with loginFrontendUser(1)
     *
     * @param array<string, int> $additionalFields
     */
    private function insertEventOfOrganizer(array $additionalFields = []): int
    {
        return $this->insertEvent(
            title: 'Event of organizer',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: $additionalFields,
            organizer: 'jweiland.net',
        );
    }

    private function insertHiddenEvent(): int
    {
        return $this->insertEvent(
            'Hidden event',
            new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: ['hidden' => 1],
        );
    }

    /**
     * @param array<string, mixed> $pluginSetup
     */
    private function buildRequest(
        array $pluginSetup = ['tx_events2.' => ['settings.' => ['userGroup' => '1']]],
        int $eventUid = 0,
    ): ServerRequest {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray(['plugin.' => $pluginSetup]);

        return (new ServerRequest('https://www.example.com/'))
            ->withQueryParams($eventUid > 0 ? ['tx_events2_management' => ['event' => (string)$eventUid]] : [])
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
    }
}
