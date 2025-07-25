<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Controller;

use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\CacheHashTrait;
use JWeiland\Events2\Tests\Functional\Traits\CreatePostStreamBodyTrait;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use JWeiland\Events2\Tests\Functional\Traits\SiteBasedTestTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 */
class DayControllerTest extends FunctionalTestCase
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
        'extensionmanager',
        'fluid_styled_content',
        'form',
        'reactions',
        'workspaces',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
        __DIR__ . '/../Fixtures/Extensions/site_package',
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'phpTimeZone' => Events2Constants::PHP_TIMEZONE,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Events2PageTree.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_groups.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_workspace.csv');

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
    public function listActionWithTypeListWillListAllEvents(): void
    {
        $this->insertEvent(
            title: 'Event Title 1: Today',
            eventBegin: new \DateTimeImmutable('today midnight'),
        );
        $this->insertEvent(
            title: 'Event Title 2: Tomorrow',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
        );
        $this->createDayRelations();

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(Events2Constants::PAGE_LIST),
        )->getBody();

        self::assertStringContainsString(
            'Event Title 1: Today',
            $content,
        );
        self::assertStringContainsString(
            'Event Title 2: Tomorrow',
            $content,
        );
    }

    #[Test]
    public function listActionWithTypeListLatestWillListSevenEvents(): void
    {
        $eventBegin = new \DateTimeImmutable('today midnight');
        for ($i = 1; $i <= 10; $i++) {
            $this->insertEvent(
                title: 'Event Title ' . $i,
                eventBegin: $eventBegin,
            );
            $eventBegin = $eventBegin->modify('+1 day');
        }
        $this->createDayRelations();

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(Events2Constants::PAGE_LIST_LATEST),
        )->getBody();

        for ($i = 1; $i <= 7; $i++) {
            self::assertStringContainsString(
                'Event Title ' . $i,
                $content,
            );
        }

        for ($i = 8; $i <= 10; $i++) {
            self::assertStringNotContainsString(
                'Event Title ' . $i,
                $content,
            );
        }
    }

    #[Test]
    public function listActionWithTypeListTodayWillListTodayEvent(): void
    {
        $this->insertEvent(
            title: 'Event Title Yesterday',
            eventBegin: new \DateTimeImmutable('yesterday midnight'),
        );
        $this->insertEvent(
            title: 'Event Title Today',
            eventBegin: new \DateTimeImmutable('today midnight'),
        );
        $this->insertEvent(
            title: 'Event Title Tomorrow',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
        );
        $this->createDayRelations();

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(Events2Constants::PAGE_LIST_TODAY),
        )->getBody();

        self::assertStringContainsString(
            'Event Title Today',
            $content,
        );
        self::assertStringNotContainsString(
            'Event Title Yesterday',
            $content,
        );
        self::assertStringNotContainsString(
            'Event Title Tomorrow',
            $content,
        );
    }

    #[Test]
    public function listActionWithTypeListThisWeekWillListThisWeeksEvents(): void
    {
        $this->insertEvent(
            title: 'Event Title last week',
            eventBegin: new \DateTimeImmutable('monday last week midnight'),
        );
        $this->insertEvent(
            title: 'Event Title Tuesday',
            eventBegin: new \DateTimeImmutable('tuesday this week midnight'),
        );
        $this->insertEvent(
            title: 'Event Title Friday',
            eventBegin: new \DateTimeImmutable('friday this week midnight'),
        );
        $this->insertEvent(
            title: 'Event Title next week',
            eventBegin: new \DateTimeImmutable('wednesday next week midnight'),
        );
        $this->createDayRelations();

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(Events2Constants::PAGE_LIST_THIS_WEEK),
        )->getBody();

        self::assertStringContainsString(
            'Event Title Tuesday',
            $content,
        );
        self::assertStringContainsString(
            'Event Title Friday',
            $content,
        );
        self::assertStringNotContainsString(
            'Event Title last week',
            $content,
        );
        self::assertStringNotContainsString(
            'Event Title next week',
            $content,
        );
    }

    #[Test]
    public function listActionWithTypeListNextFourWeeksWillListEventsOfNextFourWeeks(): void
    {
        $this->insertEvent(
            title: 'Event Title last 2 months',
            eventBegin: new \DateTimeImmutable('-2 months midnight'),
        );
        $this->insertEvent(
            title: 'Event Title next week',
            eventBegin: new \DateTimeImmutable('+1 week midnight'),
        );
        $this->insertEvent(
            title: 'Event Title next two weeks',
            eventBegin: new \DateTimeImmutable('+2 weeks midnight'),
        );
        $this->insertEvent(
            title: 'Event Title next 2 months',
            eventBegin: new \DateTimeImmutable('+2 months midnight'),
        );
        $this->createDayRelations();

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(Events2Constants::PAGE_LIST_NEXT_4_WEEKS),
        )->getBody();

        self::assertStringContainsString(
            'Event Title next week',
            $content,
        );
        self::assertStringContainsString(
            'Event Title next two weeks',
            $content,
        );
        self::assertStringNotContainsString(
            'Event Title last 2 months',
            $content,
        );
        self::assertStringNotContainsString(
            'Event Title next 2 months',
            $content,
        );
    }

    #[Test]
    public function listActionWithTypeListWillOnlyShowEventsWithSelectedOrganizer(): void
    {
        $this->insertEvent(
            title: 'Event Title without organizer',
            eventBegin: new \DateTimeImmutable('today midnight'),
        );
        $this->insertEvent(
            title: 'Event Title with organizer',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            organizer: 'jweiland.net',
        );
        $this->createDayRelations();

        $body = $this->createBodyFromArray([
            'tx_events2_list' => [
                'filter' => [
                    'organizer' => 1,
                ],
            ],
        ]);

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_LIST)
                ->withMethod('POST')
                ->withAddedHeader('Content-type', 'application/x-www-form-urlencoded')
                ->withBody($body),
        )->getBody();

        self::assertStringContainsString(
            'Event Title with organizer',
            $content,
        );
        self::assertStringNotContainsString(
            'Event Title without organizer',
            $content,
        );
    }

    #[Test]
    public function listActionWithTypeListWillOnlyShowEventsOfGivenTimestamp(): void
    {
        $todayMidnight = new \DateTimeImmutable('today midnight');

        $this->insertEvent(
            title: 'Event Title Yesterday',
            eventBegin: new \DateTimeImmutable('yesterday midnight'),
        );
        $this->insertEvent(
            title: 'Event Title Today 08:00',
            eventBegin: $todayMidnight,
            timeBegin: '08:00',
        );
        $this->insertEvent(
            title: 'Event Title Today 16:00',
            eventBegin: $todayMidnight,
            timeBegin: '16:00',
        );
        $this->insertEvent(
            title: 'Event Title Tomorrow',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
        );
        $this->createDayRelations();

        $body = $this->createBodyFromArray([
            'tx_events2_list' => [
                'filter' => [
                    'timestamp' => $todayMidnight->format('U'),
                ],
            ],
        ]);

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_LIST)
                ->withMethod('POST')
                ->withAddedHeader('Content-type', 'application/x-www-form-urlencoded')
                ->withBody($body),
        )->getBody();

        self::assertStringContainsString(
            'Event Title Today 08:00',
            $content,
        );
        self::assertStringContainsString(
            'Event Title Today 16:00',
            $content,
        );
        self::assertStringNotContainsString(
            'Event Title Yesterday',
            $content,
        );
        self::assertStringNotContainsString(
            'Event Title Tomorrow',
            $content,
        );
    }

    #[Test]
    public function listActionWithReplacedSingleEventWillListVersionedEvents(): void
    {
        $eventUid = $this->insertEvent(
            title: 'Event in LIVE workspace',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
        );
        $this->createDayRelations(eventUids: [1]);

        $this->insertEvent(
            title: 'Event in EDIT workspace',
            eventBegin: new \DateTimeImmutable('+1 week midnight'),
            additionalFields: [
                't3ver_wsid' => 1,
                't3ver_state' => VersionState::DEFAULT_STATE->value,
                't3ver_oid' => $eventUid,
            ],
        );
        $this->createDayRelations(workspace: 1, eventUids: [2]);

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_LIST),
            (new InternalRequestContext())
                ->withBackendUserId(2)
                ->withWorkspaceId(1),
        )->getBody();

        self::assertStringContainsString(
            'Event in EDIT workspace',
            $content,
        );
        self::assertStringNotContainsString(
            'Event in LIVE workspace',
            $content,
        );
    }

    #[Test]
    public function listActionWithReplacedRecurringEventWillListVersionedEvents(): void
    {
        $eventBegin = new \DateTimeImmutable('today midnight');
        $nextWeek = $eventBegin->modify('+7 days');
        $recurringEnd = $eventBegin->modify('+14 days');

        $eventUid = $this->insertEvent(
            title: 'Recurring Event in LIVE workspace',
            eventBegin: $eventBegin,
            additionalFields: [
                'event_type' => 'recurring',
                'recurring_end' => (int)$recurringEnd->format('U'),
                'xth' => 0,
                'weekday' => 0,
                'each_weeks' => 1,
                'each_months' => 0,
            ],
        );
        $this->createDayRelations(eventUids: [1]);

        $inOneMonth = new \DateTimeImmutable('+1 month midnight');
        $this->insertEvent(
            title: 'Single Event in EDIT workspace',
            eventBegin: $inOneMonth,
            additionalFields: [
                't3ver_wsid' => 1,
                't3ver_state' => VersionState::DEFAULT_STATE->value,
                't3ver_oid' => $eventUid,
            ],
        );
        $this->createDayRelations(workspace: 1, eventUids: [2]);

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_LIST),
            (new InternalRequestContext())
                ->withBackendUserId(2)
                ->withWorkspaceId(1),
        )->getBody();

        self::assertStringContainsString(
            'Single Event in EDIT workspace',
            $content,
        );
        self::assertStringContainsString(
            $inOneMonth->format('D d. M Y'),
            $content,
        );
        self::assertStringNotContainsString(
            'Recurring Event in LIVE workspace',
            $content,
        );
        self::assertStringNotContainsString(
            $eventBegin->format('D d. M Y'),
            $content,
        );
        self::assertStringNotContainsString(
            $nextWeek->format('D d. M Y'),
            $content,
        );
        self::assertStringNotContainsString(
            $recurringEnd->format('D d. M Y'),
            $content,
        );
    }

    #[Test]
    public function showActionShowsEvent(): void
    {
        $tomorrowMidnight = new \DateTimeImmutable('tomorrow midnight');

        $this->insertEvent(
            title: 'Event Title Tomorrow',
            eventBegin: $tomorrowMidnight,
            timeBegin: '08:15',
            organizer: 'jweiland.net',
        );
        $this->createDayRelations();

        $parameters = [
            'tx_events2_show' => [
                'event' => 1,
                'timestamp' => $tomorrowMidnight->format('U'),
            ],
        ];

        $parameters['cHash'] = $this->generateCacheHash($parameters, Events2Constants::PAGE_SHOW);

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_SHOW)
                ->withQueryParams($parameters),
        )->getBody();

        self::assertStringContainsString(
            'Event Title Tomorrow',
            $content,
        );
        self::assertStringContainsString(
            '08:15',
            $content,
        );
        self::assertStringContainsString(
            'jweiland.net',
            $content,
        );
    }
}
