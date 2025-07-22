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

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->workspace = 0;

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
    public function listMyEventsWillShowRestriction(): void
    {
        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_MANAGEMENT),
            (new InternalRequestContext())
                ->withFrontendUserId(3)
        )->getBody();

        self::assertStringContainsString(
            'You\'re not allowed to create event records',
            $content,
        );
    }

    #[Test]
    public function listMyEventsWillShowNoEventsFound(): void
    {
        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_MANAGEMENT),
            (new InternalRequestContext())
                ->withFrontendUserId(2)
        )->getBody();

        self::assertStringContainsString(
            'You\'re allowed to create event records, but your user record has no relation to an organizer record.',
            $content,
        );
    }

    #[Test]
    public function listMyEventsWillShowEventsOfCurrentUser(): void
    {
        $tomorrowMidnight = new \DateTimeImmutable('tomorrow midnight');

        $this->insertEvent(
            title: 'Event Title Tomorrow',
            eventBegin: $tomorrowMidnight,
            location: 'Marketplace',
        );
        $this->createDayRelations();

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_MANAGEMENT)
        )->getBody();

        self::assertStringContainsString(
            'Marketplace',
            $content,
        );
    }
}
