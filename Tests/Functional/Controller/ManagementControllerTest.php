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
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use JWeiland\Events2\Tests\Functional\Traits\SiteBasedTestTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 */
class ManagementControllerTest extends FunctionalTestCase
{
    use CacheHashTrait;
    use InsertEventTrait;
    use SiteBasedTestTrait;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'fluid_styled_content',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
        __DIR__ . '/../Fixtures/Extensions/site_package',
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => [
            'id' => 0,
            'title' => 'English',
            'locale' => 'en_US.UTF8',
            'iso' => 'en',
        ],
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
}
