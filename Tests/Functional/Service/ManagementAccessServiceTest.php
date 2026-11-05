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
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
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
    public function getDenialReasonForCreateWithoutConfiguredUserGroupReturnsUserGroupNotConfigured(): void
    {
        $this->loginFrontendUser(1);

        self::assertSame(
            'userGroupNotConfigured',
            $this->subject->getDenialReasonForCreate(0),
        );
    }

    #[Test]
    public function getDenialReasonForCreateWithoutLoggedInUserReturnsPluginNeedsLogin(): void
    {
        self::assertSame(
            'pluginNeedsLogin',
            $this->subject->getDenialReasonForCreate(1),
        );
    }

    #[Test]
    public function getDenialReasonForCreateWithUserOfOtherGroupReturnsNotAllowedToCreate(): void
    {
        $this->loginFrontendUser(1, [2]);

        self::assertSame(
            'notAllowedToCreate',
            $this->subject->getDenialReasonForCreate(1),
        );
    }

    #[Test]
    public function getDenialReasonForCreateWithUserWithoutOrganizerReturnsMissingOrganizer(): void
    {
        $this->loginFrontendUser(0);

        self::assertSame(
            'missingOrganizerForCreate',
            $this->subject->getDenialReasonForCreate(1),
        );
    }

    #[Test]
    public function canCreateEventWithUserOfGroupWithOrganizerReturnsTrue(): void
    {
        $this->loginFrontendUser(1);

        self::assertTrue(
            $this->subject->canCreateEvent(1),
        );
    }

    /**
     * A backend administrator has no organizer. The new event would be
     * stored without organizer.
     */
    #[Test]
    public function canCreateEventWithBackendAdminReturnsFalse(): void
    {
        $this->loginBackendUser(true);

        self::assertFalse(
            $this->subject->canCreateEvent(1),
        );
    }

    #[Test]
    public function canEditEventWithOwnHiddenEventReturnsTrue(): void
    {
        $eventUid = $this->insertEventOfOrganizer(['hidden' => 1]);
        $this->loginFrontendUser(1);

        self::assertTrue(
            $this->subject->canEditEvent(1, $eventUid),
        );
    }

    #[Test]
    public function getDenialReasonForEditWithForeignEventReturnsUnauthorizedOrganizer(): void
    {
        $eventUid = $this->insertEventOfOrganizer();
        $this->loginFrontendUser(2);

        self::assertSame(
            'unauthorizedOrganizerForEdit',
            $this->subject->getDenialReasonForEdit(1, $eventUid),
        );
    }

    #[Test]
    public function getDenialReasonForEditWithOwnEventButUserOfOtherGroupReturnsNotAllowedToCreate(): void
    {
        $eventUid = $this->insertEventOfOrganizer();
        $this->loginFrontendUser(1, [2]);

        self::assertSame(
            'notAllowedToCreate',
            $this->subject->getDenialReasonForEdit(1, $eventUid),
        );
    }

    #[Test]
    public function canEditEventWithEventWithoutOrganizerReturnsFalse(): void
    {
        $eventUid = $this->insertEvent(
            title: 'Event without organizer',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
        );
        $this->loginFrontendUser(1);

        self::assertFalse(
            $this->subject->canEditEvent(1, $eventUid),
        );
    }

    #[Test]
    public function canEditEventWithOwnDeletedEventReturnsFalse(): void
    {
        $eventUid = $this->insertEventOfOrganizer(['deleted' => 1]);
        $this->loginFrontendUser(1);

        self::assertFalse(
            $this->subject->canEditEvent(1, $eventUid),
        );
    }

    #[Test]
    public function canEditEventWithBackendAdminReturnsTrue(): void
    {
        $eventUid = $this->insertEventOfOrganizer(['hidden' => 1]);
        $this->loginBackendUser(true);

        self::assertTrue(
            $this->subject->canEditEvent(1, $eventUid),
        );
    }

    #[Test]
    public function canEditEventWithBackendAdminAndNotExistingEventReturnsFalse(): void
    {
        $this->loginBackendUser(true);

        self::assertFalse(
            $this->subject->canEditEvent(1, 4711),
        );
    }

    #[Test]
    public function canEditEventWithBackendEditorReturnsFalse(): void
    {
        $eventUid = $this->insertEventOfOrganizer();
        $this->loginBackendUser(false);

        self::assertFalse(
            $this->subject->canEditEvent(1, $eventUid),
        );
    }

    #[Test]
    public function getDenialReasonForListingWithBackendAdminReturnsEmptyString(): void
    {
        $this->loginBackendUser(true);

        self::assertSame(
            '',
            $this->subject->getDenialReasonForListing(1),
        );
    }

    #[Test]
    public function getDenialReasonForListingWithoutLoggedInUserReturnsPluginNeedsLogin(): void
    {
        self::assertSame(
            'pluginNeedsLogin',
            $this->subject->getDenialReasonForListing(1),
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

    #[Test]
    public function canActivateEventWithBackendAdminWithoutTokenReturnsTrue(): void
    {
        $eventUid = $this->insertHiddenEvent();
        $this->loginBackendUser(true);

        self::assertTrue(
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
    public function generateModerationTokenReturnsTokenForHiddenEvent(): void
    {
        $eventUid = $this->insertHiddenEvent();

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

    #[Test]
    public function moderationTokenStaysValidAfterEventWasModified(): void
    {
        $eventUid = $this->insertHiddenEvent();
        $moderationToken = $this->subject->generateModerationToken($eventUid);

        $this->getConnectionPool()
            ->getConnectionForTable('tx_events2_domain_model_event')
            ->update('tx_events2_domain_model_event', ['tstamp' => time() + 60], ['uid' => $eventUid]);

        self::assertSame(
            $moderationToken,
            $this->subject->getValidModerationToken($this->buildExtbaseRequest($moderationToken), $eventUid),
        );
    }

    #[Test]
    public function getValidModerationTokenReturnsValidToken(): void
    {
        $eventUid = $this->insertHiddenEvent();
        $moderationToken = $this->subject->generateModerationToken($eventUid);

        self::assertSame(
            $moderationToken,
            $this->subject->getValidModerationToken($this->buildExtbaseRequest($moderationToken), $eventUid),
        );
    }

    #[Test]
    public function getValidModerationTokenWithoutTokenArgumentReturnsEmptyString(): void
    {
        $eventUid = $this->insertHiddenEvent();

        self::assertSame(
            '',
            $this->subject->getValidModerationToken($this->buildExtbaseRequest(null), $eventUid),
        );
    }

    public static function invalidModerationTokenDataProvider(): array
    {
        return [
            'empty token' => [''],
            'invalid token' => ['invalid'],
            'array token' => [['foo' => 'bar']],
        ];
    }

    #[Test]
    #[DataProvider('invalidModerationTokenDataProvider')]
    public function getValidModerationTokenWithInvalidTokenReturnsEmptyString(mixed $moderationToken): void
    {
        $eventUid = $this->insertHiddenEvent();

        self::assertSame(
            '',
            $this->subject->getValidModerationToken($this->buildExtbaseRequest($moderationToken), $eventUid),
        );
    }

    #[Test]
    public function getValidModerationTokenWithTokenOfForeignEventReturnsEmptyString(): void
    {
        $foreignEventUid = $this->insertHiddenEvent();
        $eventUid = $this->insertHiddenEvent();
        $moderationToken = $this->subject->generateModerationToken($foreignEventUid);

        self::assertSame(
            '',
            $this->subject->getValidModerationToken($this->buildExtbaseRequest($moderationToken), $eventUid),
        );
    }

    #[Test]
    public function getValidModerationTokenForDeletedEventReturnsEmptyString(): void
    {
        $eventUid = $this->insertHiddenEvent();
        $moderationToken = $this->subject->generateModerationToken($eventUid);

        $this->getConnectionPool()
            ->getConnectionForTable('tx_events2_domain_model_event')
            ->update('tx_events2_domain_model_event', ['deleted' => 1], ['uid' => $eventUid]);

        self::assertSame(
            '',
            $this->subject->getValidModerationToken($this->buildExtbaseRequest($moderationToken), $eventUid),
        );
    }

    #[Test]
    public function getUserGroupFromTypoScriptReadsSettingOfExtension(): void
    {
        self::assertSame(
            5,
            $this->subject->getUserGroupFromTypoScript($this->buildRequest([
                'tx_events2.' => ['settings.' => ['userGroup' => '5']],
            ])),
        );
    }

    #[Test]
    public function getUserGroupFromTypoScriptPrefersSettingOfPlugin(): void
    {
        self::assertSame(
            7,
            $this->subject->getUserGroupFromTypoScript($this->buildRequest([
                'tx_events2.' => ['settings.' => ['userGroup' => '5']],
                'tx_events2_management.' => ['settings.' => ['userGroup' => '7']],
            ])),
        );
    }

    #[Test]
    public function getUserGroupFromTypoScriptWithoutTypoScriptReturnsZero(): void
    {
        self::assertSame(
            0,
            $this->subject->getUserGroupFromTypoScript(new ServerRequest('https://www.example.com/')),
        );
    }

    #[Test]
    public function getUserGroupFromTypoScriptWithoutSetupArrayReturnsZero(): void
    {
        self::assertSame(
            0,
            $this->subject->getUserGroupFromTypoScript(
                (new ServerRequest('https://www.example.com/'))
                    ->withAttribute('frontend.typoscript', new FrontendTypoScript(new RootNode(), [], [], [])),
            ),
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

    private function buildExtbaseRequest(mixed $moderationToken): Request
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerExtensionName('Events2');
        $extbaseRequestParameters->setPluginName('Management');
        $extbaseRequestParameters->setControllerName('Management');
        $extbaseRequestParameters->setControllerActionName('activate');
        if ($moderationToken !== null) {
            $extbaseRequestParameters->setArgument('moderationToken', $moderationToken);
        }

        return new Request(
            (new ServerRequest('https://www.example.com/', 'POST'))
                ->withAttribute('extbase', $extbaseRequestParameters),
        );
    }

    /**
     * @param array<string, mixed> $pluginSetup
     */
    private function buildRequest(array $pluginSetup): ServerRequest
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray(['plugin.' => $pluginSetup]);

        return (new ServerRequest('https://www.example.com/'))
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
    }
}
