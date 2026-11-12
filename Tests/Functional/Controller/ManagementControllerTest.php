<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Controller;

use JWeiland\Events2\Service\ManagementAccessService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\CacheHashTrait;
use JWeiland\Events2\Tests\Functional\Traits\CreatePostStreamBodyTrait;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use JWeiland\Events2\Tests\Functional\Traits\SiteBasedTestTrait;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 */
class ManagementControllerTest extends FunctionalTestCase
{
    use CacheHashTrait;
    use CreatePostStreamBodyTrait;
    use InsertEventTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => [
            'id' => 0,
            'title' => 'English',
            'locale' => 'en_US.UTF8',
            'iso' => 'en',
        ],
    ];

    protected array $coreExtensionsToLoad = [
        'fluid_styled_content',
        'form',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'jweiland/events2',
        __DIR__ . '/../Fixtures/Extensions/site_package',
    ];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'events2' => [
                'emailFromAddress' => 'projects@jweiland.net',
                'emailFromName' => 'Stefan Froemken',
                'emailToAddress' => 'hosting@jweiland.net',
                'emailToName' => 'Stefan Froemken',
            ],
        ],
        'MAIL' => [
            'transport' => 'null',
        ],
        'SYS' => [
            'phpTimeZone' => Events2Constants::PHP_TIMEZONE,
        ],
    ];

    protected array $additionalFoldersToCreate = [
        'fileadmin/Extensions/events2',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Events2PageTree.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/fe_users.csv');

        $this->writeSiteConfiguration(
            'events2-controller-test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ],
            [],
            ['jweiland/sitepackage'],
        );
    }

    #[Test]
    public function listMyEventsWithoutLoggedInUserWillShowLoginMessage(): void
    {
        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_MANAGEMENT),
        )->getBody();

        self::assertStringContainsString(
            'This page is restricted. Please log in to access the Events Management area.',
            $content,
        );
        self::assertStringNotContainsString(
            'An error occurred while trying to call',
            $content,
        );
    }

    #[Test]
    public function listMyEventsWithUserOfWrongGroupWillShowRestrictionMessage(): void
    {
        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_MANAGEMENT),
            (new InternalRequestContext())
                ->withFrontendUserId(3),
        )->getBody();

        self::assertStringContainsString(
            'You\'re not allowed to create event records',
            $content,
        );
        self::assertStringNotContainsString(
            'An error occurred while trying to call',
            $content,
        );
    }

    #[Test]
    public function listMyEventsWithUserWithoutOrganizerWillShowMissingOrganizerMessage(): void
    {
        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_MANAGEMENT),
            (new InternalRequestContext())
                ->withFrontendUserId(2),
        )->getBody();

        self::assertStringContainsString(
            'You\'re allowed to create event records, but your user record has no relation to an organizer record.',
            $content,
        );
        self::assertStringNotContainsString(
            'An error occurred while trying to call',
            $content,
        );
    }

    #[Test]
    public function listMyEventsWillShowEventsOfCurrentUser(): void
    {
        $this->insertEvent(
            title: 'Event without organizer will not be shown in general',
            eventBegin: new \DateTimeImmutable('yesterday midnight'),
        );
        $this->insertEvent(
            title: 'Event with user defined organizer',
            eventBegin: new \DateTimeImmutable('today midnight'),
            organizer: 'jweiland.net',
        );
        $this->insertEvent(
            title: 'Event with non user defined organizer',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            organizer: 'TYPO3',
        );
        $this->createDayRelations();

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_MANAGEMENT),
            (new InternalRequestContext())
                ->withFrontendUserId(1),
        )->getBody();

        self::assertStringContainsString(
            'Event with user defined organizer',
            $content,
        );
        self::assertStringNotContainsString(
            'Event without organizer will not be shown in general',
            $content,
        );
        self::assertStringNotContainsString(
            'Event with non user defined organizer',
            $content,
        );
    }

    #[Test]
    public function performWillShowExtForm(): void
    {
        $parameters = [
            'tx_events2_management' => [
                'controller' => 'Management',
                'action' => 'perform',
            ],
        ];

        $parameters['cHash'] = $this->generateCacheHash($parameters, Events2Constants::PAGE_MANAGEMENT);

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_MANAGEMENT)
                ->withQueryParams($parameters),
            (new InternalRequestContext())
                ->withFrontendUserId(1),
        )->getBody();

        self::assertStringContainsString(
            'Single event',
            $content,
        );
        self::assertStringContainsString(
            'Multiday event',
            $content,
        );
        self::assertStringContainsString(
            'Recurring event',
            $content,
        );
        self::assertMatchesRegularExpression(
            '#<small id="[^"]+-choose-event-type-desc" class="form-text text-muted">#',
            $content,
        );
        self::assertStringContainsString('class="form-check-input"', $content);
        self::assertStringNotContainsString('role="tooltip"', $content);
        self::assertStringNotContainsString('<legend class="visually-hidden">', $content);
    }

    #[Test]
    public function performWithoutLoggedInUserWillNotShowExtForm(): void
    {
        $content = (string)$this->executeGetRequest('perform', 0, '')->getBody();

        self::assertStringContainsString(
            'This page is restricted. Please log in to access the Events Management area.',
            $content,
        );
        self::assertStringNotContainsString(
            'Recurring event',
            $content,
        );
    }

    /**
     * The edit view resolves all relations of the event. It has to work
     * on every DBMS, see PrefillForEditUsageHookTest for the values.
     */
    #[Test]
    public function performWithOwnEventRendersEditForm(): void
    {
        $eventUid = $this->insertEvent(
            title: 'Own event with relations',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            timeBegin: '19:00',
            ticketLink: 'https://tickets.example.com',
            videoLink: 'https://youtu.be/abc',
            additionalFields: ['hidden' => 1, 'event_type' => 'duration'],
            organizer: 'jweiland.net',
            location: 'Kino',
            categories: [1],
        );

        $content = (string)$this->executeGetRequest('perform', $eventUid, '', 1)->getBody();

        self::assertStringContainsString('tx_events2_management%5Bevent%5D=' . $eventUid, $content);
        self::assertMatchesRegularExpression('/value="duration"[^>]*checked/', $content);
    }

    /**
     * Not existing and foreign events result in the same message, so
     * nobody can find out, which hidden events exist.
     */
    #[Test]
    public function performWithForeignEventDoesNotShowEventData(): void
    {
        // Creates the organizer with UID 1 of frontend user 1
        $this->insertHiddenEventOfFrontendUser();
        $eventUid = $this->insertEvent(
            title: 'Hidden event of other organizer',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: ['hidden' => 1],
            organizer: 'TYPO3',
        );

        $content = (string)$this->executeGetRequest('perform', $eventUid, '', 1)->getBody();

        self::assertStringContainsString(
            'You are not allowed to edit events that do not correspond to your assigned organizer.',
            $content,
        );
        self::assertStringNotContainsString('Hidden event of other organizer', $content);
        self::assertStringNotContainsString('Recurring event', $content);
    }

    #[Test]
    public function performWithUserOfWrongGroupWillNotShowExtForm(): void
    {
        $content = (string)$this->executeGetRequest('perform', 0, '', 3)->getBody();

        self::assertStringContainsString('You\'re not allowed to create event records', $content);
        self::assertStringNotContainsString('Recurring event', $content);
    }

    /**
     * The token protects the state changing request against CSRF. Logged-in
     * frontend users of the user group must not activate events without it.
     */
    #[Test]
    public function activateViaPostWithInvalidTokenKeepsEventHidden(): void
    {
        $eventUid = $this->insertHiddenEventOfFrontendUser();

        $content = (string)$this->executePostRequest('activate', $eventUid, 'invalid', 1)->getBody();

        self::assertStringContainsString(
            'The activation link is invalid or the event does not exist anymore.',
            $content,
        );
        self::assertTrue($this->isEventHidden($eventUid));
    }

    /**
     * The Extbase form and its "create" action were removed. As the plugin
     * calls the default action for unknown actions, nothing is stored even
     * for allowed frontend users.
     */
    #[Test]
    public function removedCreateActionWillNotStoreEvent(): void
    {
        $content = (string)$this->executeCreateRequest(1)->getBody();

        self::assertSame(
            0,
            $this->countEvents(),
        );
        self::assertStringNotContainsString(
            'tx_events2_management[event][title]',
            $content,
        );
    }

    #[Test]
    public function confirmActivationWithValidTokenShowsConfirmationForm(): void
    {
        $eventUid = $this->insertHiddenEventOfFrontendUser();
        $moderationToken = $this->get(ManagementAccessService::class)->generateModerationToken($eventUid);

        $content = (string)$this->executeGetRequest('confirmActivation', $eventUid, $moderationToken)->getBody();

        self::assertStringContainsString('Activate event', $content);
        self::assertStringContainsString('Hidden event of frontend user', $content);
        self::assertStringContainsString('value="' . $moderationToken . '"', $content);
        self::assertStringContainsString('method="post"', $content);
    }

    #[Test]
    public function confirmActivationByOwnerWithoutTokenIsDenied(): void
    {
        $eventUid = $this->insertHiddenEventOfFrontendUser();

        $content = (string)$this->executeGetRequest('confirmActivation', $eventUid, '', 1)->getBody();

        self::assertStringContainsString(
            'The activation link is invalid or the event does not exist anymore.',
            $content,
        );
        self::assertStringNotContainsString('Hidden event of frontend user', $content);
    }

    /**
     * Extbase sends the redirect to the confirmation page with header(),
     * which is not part of the response of a frontend sub request.
     */
    #[Test]
    public function activateViaGetWithValidTokenKeepsEventHidden(): void
    {
        $eventUid = $this->insertHiddenEventOfFrontendUser();
        $moderationToken = $this->get(ManagementAccessService::class)->generateModerationToken($eventUid);

        $content = (string)$this->executeGetRequest('activate', $eventUid, $moderationToken)->getBody();

        self::assertStringNotContainsString('A record was activated', $content);
        self::assertTrue($this->isEventHidden($eventUid));
    }

    #[Test]
    public function activateViaGetByOwnerWithoutTokenKeepsEventHidden(): void
    {
        $eventUid = $this->insertHiddenEventOfFrontendUser();

        $content = (string)$this->executeGetRequest('activate', $eventUid, '', 1)->getBody();

        self::assertStringContainsString(
            'The activation link is invalid or the event does not exist anymore.',
            $content,
        );
        self::assertTrue($this->isEventHidden($eventUid));
    }

    #[Test]
    public function activateViaPostByOwnerWithoutTokenKeepsEventHidden(): void
    {
        $eventUid = $this->insertHiddenEventOfFrontendUser();

        $content = (string)$this->executePostRequest('activate', $eventUid, '', 1)->getBody();

        self::assertStringContainsString(
            'The activation link is invalid or the event does not exist anymore.',
            $content,
        );
        self::assertTrue($this->isEventHidden($eventUid));
    }

    #[Test]
    public function activateViaPostWithTokenOfForeignEventKeepsEventHidden(): void
    {
        $eventUid = $this->insertHiddenEventOfFrontendUser();
        $foreignEventUid = $this->insertHiddenEventOfFrontendUser();
        $moderationToken = $this->get(ManagementAccessService::class)->generateModerationToken($foreignEventUid);

        $this->executePostRequest('activate', $eventUid, $moderationToken);

        self::assertTrue($this->isEventHidden($eventUid));
        self::assertTrue($this->isEventHidden($foreignEventUid));
    }

    #[Test]
    public function activateViaPostWithValidTokenActivatesEvent(): void
    {
        $eventUid = $this->insertHiddenEventOfFrontendUser();
        $moderationToken = $this->get(ManagementAccessService::class)->generateModerationToken($eventUid);

        $this->executePostRequest('activate', $eventUid, $moderationToken);

        self::assertFalse($this->isEventHidden($eventUid));
    }

    private function insertHiddenEventOfFrontendUser(): int
    {
        // The first organizer gets UID 1, which is assigned to frontend user 1
        return $this->insertEvent(
            title: 'Hidden event of frontend user',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: ['hidden' => 1],
            organizer: 'jweiland.net',
        );
    }

    private function executeGetRequest(
        string $actionName,
        int $eventUid,
        string $moderationToken,
        int $frontendUserId = 0,
    ): ResponseInterface {
        $parameters = [
            'tx_events2_management' => $this->getPluginArguments($actionName, $eventUid, $moderationToken),
        ];
        $parameters['cHash'] = $this->generateCacheHash($parameters, Events2Constants::PAGE_MANAGEMENT);

        return $this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_MANAGEMENT)
                ->withQueryParams($parameters),
            (new InternalRequestContext())
                ->withFrontendUserId($frontendUserId),
        );
    }

    private function executePostRequest(
        string $actionName,
        int $eventUid,
        string $moderationToken,
        int $frontendUserId = 0,
    ): ResponseInterface {
        $body = $this->createBodyFromArray([
            'tx_events2_management' => $this->getPluginArguments($actionName, $eventUid, $moderationToken),
        ]);

        return $this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_MANAGEMENT)
                ->withMethod('POST')
                ->withAddedHeader('Content-type', 'application/x-www-form-urlencoded')
                ->withBody($body),
            (new InternalRequestContext())
                ->withFrontendUserId($frontendUserId),
        );
    }

    private function executeCreateRequest(int $frontendUserId): ResponseInterface
    {
        $body = $this->createBodyFromArray([
            'tx_events2_management' => [
                'controller' => 'Management',
                'action' => 'create',
                'event' => [
                    'title' => 'Event of anonymous visitor',
                    'eventType' => 'single',
                    'eventBegin' => (new \DateTimeImmutable('tomorrow midnight'))->format('d.m.Y'),
                ],
            ],
        ]);

        return $this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_MANAGEMENT)
                ->withMethod('POST')
                ->withAddedHeader('Content-type', 'application/x-www-form-urlencoded')
                ->withBody($body),
            (new InternalRequestContext())
                ->withFrontendUserId($frontendUserId),
        );
    }

    private function countEvents(): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_event');
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder
            ->count('*')
            ->from('tx_events2_domain_model_event')
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @return array<string, string|int>
     */
    private function getPluginArguments(string $actionName, int $eventUid, string $moderationToken): array
    {
        $arguments = [
            'controller' => 'Management',
            'action' => $actionName,
        ];
        if ($eventUid > 0) {
            $arguments['event'] = $eventUid;
        }
        if ($moderationToken !== '') {
            $arguments['moderationToken'] = $moderationToken;
        }

        return $arguments;
    }

    private function isEventHidden(int $eventUid): bool
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_event');
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder
            ->select('hidden')
            ->from('tx_events2_domain_model_event')
            ->where($queryBuilder->expr()->eq('uid', $eventUid))
            ->executeQuery()
            ->fetchOne() === 1;
    }
}
