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
use PHPUnit\Framework\Attributes\Test;
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
            $this->subject->getDenialReasonForFrontendUser(0),
        );
    }

    #[Test]
    public function getDenialReasonWithoutLoggedInUserReturnsPluginNeedsLogin(): void
    {
        self::assertSame(
            'pluginNeedsLogin',
            $this->subject->getDenialReasonForFrontendUser(1),
        );
    }

    #[Test]
    public function getDenialReasonWithUserOfOtherGroupReturnsNotAllowedToCreate(): void
    {
        $this->loginFrontendUser(1, [2]);

        self::assertSame(
            'notAllowedToCreate',
            $this->subject->getDenialReasonForFrontendUser(1),
        );
    }

    #[Test]
    public function getDenialReasonWithUserWithoutOrganizerReturnsMissingOrganizer(): void
    {
        $this->loginFrontendUser(0);

        self::assertSame(
            'missingOrganizerForCreate',
            $this->subject->getDenialReasonForFrontendUser(1),
        );
    }

    #[Test]
    public function isFrontendUserAllowedWithUserOfGroupWithOrganizerReturnsTrue(): void
    {
        $this->loginFrontendUser(1);

        self::assertTrue(
            $this->subject->isFrontendUserAllowed(1),
        );
    }

    #[Test]
    public function isBackendAdminWithAdminReturnsTrue(): void
    {
        $this->loginBackendUser(true);

        self::assertTrue(
            $this->subject->isBackendAdmin(),
        );
    }

    #[Test]
    public function isBackendAdminWithEditorReturnsFalse(): void
    {
        $this->loginBackendUser(false);

        self::assertFalse(
            $this->subject->isBackendAdmin(),
        );
    }

    #[Test]
    public function isEventOfFrontendUserWithOwnHiddenEventReturnsTrue(): void
    {
        // The first organizer gets UID 1
        $eventUid = $this->insertEvent(
            title: 'Own event',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: ['hidden' => 1],
            organizer: 'jweiland.net',
        );
        $this->loginFrontendUser(1);

        self::assertTrue(
            $this->subject->isEventOfFrontendUser($eventUid),
        );
    }

    #[Test]
    public function isEventOfFrontendUserWithForeignEventReturnsFalse(): void
    {
        $eventUid = $this->insertEvent(
            title: 'Foreign event',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            organizer: 'jweiland.net',
        );
        $this->loginFrontendUser(2);

        self::assertFalse(
            $this->subject->isEventOfFrontendUser($eventUid),
        );
    }

    #[Test]
    public function isEventOfFrontendUserWithEventWithoutOrganizerReturnsFalse(): void
    {
        $eventUid = $this->insertEvent(
            title: 'Event without organizer',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
        );
        $this->loginFrontendUser(1);

        self::assertFalse(
            $this->subject->isEventOfFrontendUser($eventUid),
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
