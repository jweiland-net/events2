<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\EventListener;

use JWeiland\Events2\Event\PreProcessControllerActionEvent;
use JWeiland\Events2\EventListener\RestrictAccessEventListener;
use JWeiland\Events2\Service\EventModerationService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\FrontendUserTrait;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test
 *
 * Owners of an event without moderation token are denied in
 * ManagementControllerTest, as the organizer check of the listener needs
 * a complete frontend request.
 */
class RestrictAccessEventListenerTest extends FunctionalTestCase
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

    private int $eventUid;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventUid = $this->insertEvent(
            'Hidden event',
            new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: ['hidden' => 1],
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);

        parent::tearDown();
    }

    public static function moderationTokenActionDataProvider(): array
    {
        return [
            'confirmActivation action' => ['confirmActivation'],
            'activate action' => ['activate'],
        ];
    }

    #[Test]
    #[DataProvider('moderationTokenActionDataProvider')]
    public function moderationTokenActionWithoutTokenIsDenied(string $actionName): void
    {
        $controllerActionEvent = $this->dispatch($actionName, $this->eventUid, null);

        self::assertSame(
            'error',
            $controllerActionEvent->getActionName(),
        );
        self::assertSame(
            0,
            $controllerActionEvent->getArguments()->count(),
        );
    }

    #[Test]
    #[DataProvider('moderationTokenActionDataProvider')]
    public function moderationTokenActionWithValidTokenIsAllowed(string $actionName): void
    {
        $controllerActionEvent = $this->dispatch(
            $actionName,
            $this->eventUid,
            $this->get(EventModerationService::class)->generateModerationToken($this->eventUid),
        );

        self::assertSame(
            $actionName,
            $controllerActionEvent->getActionName(),
        );
    }

    #[Test]
    #[DataProvider('moderationTokenActionDataProvider')]
    public function moderationTokenActionWithInvalidTokenIsDenied(string $actionName): void
    {
        $controllerActionEvent = $this->dispatch($actionName, $this->eventUid, 'invalid');

        self::assertSame(
            'error',
            $controllerActionEvent->getActionName(),
        );
    }

    #[Test]
    #[DataProvider('moderationTokenActionDataProvider')]
    public function moderationTokenActionWithTokenOfForeignEventIsDenied(string $actionName): void
    {
        $foreignEventUid = $this->insertEvent(
            'Foreign event',
            new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: ['hidden' => 1],
        );

        $controllerActionEvent = $this->dispatch(
            $actionName,
            $this->eventUid,
            $this->get(EventModerationService::class)->generateModerationToken($foreignEventUid),
        );

        self::assertSame(
            'error',
            $controllerActionEvent->getActionName(),
        );
    }

    #[Test]
    #[DataProvider('moderationTokenActionDataProvider')]
    public function moderationTokenActionWithEventArrayArgumentIsDenied(string $actionName): void
    {
        $controllerActionEvent = $this->dispatch(
            $actionName,
            ['__identity' => $this->eventUid, 'title' => 'Manipulated'],
            $this->get(EventModerationService::class)->generateModerationToken($this->eventUid),
        );

        self::assertSame(
            'error',
            $controllerActionEvent->getActionName(),
        );
    }

    #[Test]
    #[DataProvider('moderationTokenActionDataProvider')]
    public function moderationTokenActionWithLoggedInFrontendUserOfUserGroupIsDenied(string $actionName): void
    {
        $frontendUser = new FrontendUserAuthentication();
        $frontendUser->user = [
            'uid' => 1,
            'usergroup' => '1',
            'tx_events2_organizer' => 1,
        ];

        $this->get(Context::class)->setAspect('frontend.user', new UserAspect($frontendUser, [0, -2, 1]));

        $controllerActionEvent = $this->dispatch($actionName, $this->eventUid, null);

        self::assertSame(
            'error',
            $controllerActionEvent->getActionName(),
        );
    }

    #[Test]
    #[DataProvider('moderationTokenActionDataProvider')]
    public function moderationTokenActionWithBackendAdminIsAllowed(string $actionName): void
    {
        $this->setBackendUser(true);

        $controllerActionEvent = $this->dispatch($actionName, $this->eventUid, null);

        self::assertSame(
            $actionName,
            $controllerActionEvent->getActionName(),
        );
    }

    #[Test]
    #[DataProvider('moderationTokenActionDataProvider')]
    public function moderationTokenActionWithBackendEditorIsDenied(string $actionName): void
    {
        $this->setBackendUser(false);

        $controllerActionEvent = $this->dispatch($actionName, $this->eventUid, null);

        self::assertSame(
            'error',
            $controllerActionEvent->getActionName(),
        );
    }

    #[Test]
    public function otherActionWithoutLoginIsDenied(): void
    {
        $controllerActionEvent = $this->dispatch('listMyEvents', null, null);

        self::assertSame(
            'error',
            $controllerActionEvent->getActionName(),
        );
    }

    #[Test]
    public function otherActionWithValidTokenButWithoutLoginIsDenied(): void
    {
        $controllerActionEvent = $this->dispatch(
            'perform',
            $this->eventUid,
            $this->get(EventModerationService::class)->generateModerationToken($this->eventUid),
        );

        self::assertSame(
            'error',
            $controllerActionEvent->getActionName(),
        );
    }

    #[Test]
    public function otherActionWithBackendAdminIsAllowed(): void
    {
        $this->setBackendUser(true);

        $controllerActionEvent = $this->dispatch('listMyEvents', null, null);

        self::assertSame(
            'listMyEvents',
            $controllerActionEvent->getActionName(),
        );
    }

    public static function createActionDataProvider(): array
    {
        return [
            'perform action without event' => ['perform'],
        ];
    }

    #[Test]
    #[DataProvider('createActionDataProvider')]
    public function createActionWithoutLoginIsDenied(string $actionName): void
    {
        $controllerActionEvent = $this->dispatch($actionName, null, null);

        self::assertSame(
            'error',
            $controllerActionEvent->getActionName(),
        );
        self::assertSame(
            0,
            $controllerActionEvent->getArguments()->count(),
        );
    }

    #[Test]
    #[DataProvider('createActionDataProvider')]
    public function createActionWithFrontendUserOfUserGroupIsAllowed(string $actionName): void
    {
        $this->loginFrontendUser(1);

        $controllerActionEvent = $this->dispatch($actionName, null, null);

        self::assertSame(
            $actionName,
            $controllerActionEvent->getActionName(),
        );
    }

    /**
     * A backend administrator has no organizer. The new event would be
     * stored without organizer.
     */
    #[Test]
    #[DataProvider('createActionDataProvider')]
    public function createActionWithBackendAdminWithoutFrontendUserIsDenied(string $actionName): void
    {
        $this->setBackendUser(true);

        $controllerActionEvent = $this->dispatch($actionName, null, null);

        self::assertSame(
            'error',
            $controllerActionEvent->getActionName(),
        );
    }

    #[Test]
    public function performActionWithEventAndBackendAdminIsAllowed(): void
    {
        $this->setBackendUser(true);

        $controllerActionEvent = $this->dispatch('perform', $this->eventUid, null);

        self::assertSame(
            'perform',
            $controllerActionEvent->getActionName(),
        );
    }

    private function dispatch(
        string $actionName,
        int|array|null $event,
        ?string $moderationToken,
    ): PreProcessControllerActionEvent {
        $request = $this->buildRequest($actionName, $event, $moderationToken);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);

        $controllerActionEvent = new PreProcessControllerActionEvent(
            $request,
            new Arguments(),
            ['userGroup' => 1],
        );

        $this->get(RestrictAccessEventListener::class)($controllerActionEvent);

        return $controllerActionEvent;
    }

    private function buildRequest(string $actionName, int|array|null $event, ?string $moderationToken): Request
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerExtensionName('Events2');
        $extbaseRequestParameters->setPluginName('Management');
        $extbaseRequestParameters->setControllerName('Management');
        $extbaseRequestParameters->setControllerActionName($actionName);
        if ($event !== null) {
            $extbaseRequestParameters->setArgument('event', $event);
        }
        if ($moderationToken !== null) {
            $extbaseRequestParameters->setArgument('moderationToken', $moderationToken);
        }

        // ExtensionService needs the TypoScript to resolve the plugin namespace for flash messages
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);

        return new Request(
            (new ServerRequest('https://www.example.com/', 'POST'))
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
                ->withAttribute('frontend.typoscript', $frontendTypoScript)
                ->withAttribute('extbase', $extbaseRequestParameters),
        );
    }

    private function setBackendUser(bool $isAdmin): void
    {
        $backendUser = new BackendUserAuthentication();
        $backendUser->user = [
            'uid' => 1,
            'admin' => (int)$isAdmin,
        ];

        $this->get(Context::class)->setAspect('backend.user', new UserAspect($backendUser));
    }
}
