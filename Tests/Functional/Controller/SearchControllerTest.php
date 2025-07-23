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
use JWeiland\Events2\Tests\Functional\Traits\CreatePostStreamBodyTrait;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use JWeiland\Events2\Tests\Functional\Traits\SiteBasedTestTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 */
class SearchControllerTest extends FunctionalTestCase
{
    use CreatePostStreamBodyTrait;
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

        date_default_timezone_set('Europe/Berlin');

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Events2PageTree.csv');

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
    public function showWillShowSearchForm(): void
    {
        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_SEARCH_FORM),
        )->getBody();

        self::assertStringContainsString(
            'method="get" name="search" action="/events2-searchresults"',
            $content,
        );
        self::assertStringContainsString(
            'searchEvent',
            $content,
        );
        self::assertStringContainsString(
            'searchMainCategory',
            $content,
        );
        self::assertStringContainsString(
            'searchSubCategory',
            $content,
        );
        self::assertStringContainsString(
            'searchEventBegin',
            $content,
        );
        self::assertStringContainsString(
            'searchEventEnd',
            $content,
        );
        self::assertStringContainsString(
            'searchLocation',
            $content,
        );
        self::assertStringContainsString(
            'searchFreeEntry',
            $content,
        );
        self::assertStringContainsString(
            '<input type="submit" class="btn btn-default" value="Search"/>',
            $content,
        );
        self::assertStringContainsString(
            '<option value="2">Audi</option>',
            $content,
        );
        self::assertStringContainsString(
            '<option value="3">BMW</option>',
            $content,
        );
        self::assertStringNotContainsString(
            '<option value="1">Cars</option>',
            $content,
        );
    }

    #[Test]
    public function showWillShowLocations(): void
    {
        $this->insertEvent(
            title: 'Event with Location: Stuttgart',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            location: 'Stuttgart',
        );
        $this->insertEvent(
            title: 'Event with Location: Filderstadt',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            location: 'Filderstadt',
        );
        $this->createDayRelations();

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_SEARCH_FORM),
        )->getBody();

        self::assertStringContainsString(
            '<option value="1">Stuttgart</option>',
            $content,
        );
        self::assertStringContainsString(
            '<option value="2">Filderstadt</option>',
            $content,
        );
    }

    #[Test]
    public function listSearchResultsWillShowNoResults(): void
    {
        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_SEARCH_RESULTS),
        )->getBody();

        self::assertStringContainsString(
            'No events found',
            $content,
        );
    }

    #[Test]
    public function listSearchResultsWithoutEventsWillShowNoResults(): void
    {
        $body = $this->createBodyFromArray([
            'tx_events2_searchresults' => [
                'search' => [],
            ],
        ]);

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_SEARCH_RESULTS)
                ->withMethod('POST')
                ->withAddedHeader('Content-type', 'application/x-www-form-urlencoded')
                ->withBody($body),
        )->getBody();

        self::assertStringContainsString(
            'No events found',
            $content,
        );
    }

    #[Test]
    public function listSearchResultsWillShowFutureResultsByDefault(): void
    {
        $this->insertEvent(
            title: 'Event Yesterday',
            eventBegin: new \DateTimeImmutable('yesterday midnight'),
        );
        $this->insertEvent(
            title: 'Event Tomorrow',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
        );
        $this->createDayRelations();

        $body = $this->createBodyFromArray([
            'tx_events2_searchresults' => [
                'search' => [
                    'search' => 'event',
                ],
            ],
        ]);

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_SEARCH_RESULTS)
                ->withMethod('POST')
                ->withAddedHeader('Content-type', 'application/x-www-form-urlencoded')
                ->withBody($body),
        )->getBody();

        self::assertStringContainsString(
            'Event Tomorrow',
            $content,
        );
        self::assertStringNotContainsString(
            'Event Yesterday',
            $content,
        );
    }

    #[Test]
    public function listSearchResultsWillShowOldResults(): void
    {
        $this->insertEvent(
            title: 'Event Yesterday',
            eventBegin: new \DateTimeImmutable('yesterday midnight'),
        );
        $this->insertEvent(
            title: 'Event Tomorrow',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
        );
        $this->createDayRelations();

        $body = $this->createBodyFromArray([
            'tx_events2_searchresults' => [
                'search' => [
                    'search' => 'event',
                    'eventBegin' => (new \DateTimeImmutable('-1 week midnight'))->format('d.m.Y'),
                ],
            ],
        ]);

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_SEARCH_RESULTS)
                ->withMethod('POST')
                ->withAddedHeader('Content-type', 'application/x-www-form-urlencoded')
                ->withBody($body),
        )->getBody();

        self::assertStringContainsString(
            'Event Tomorrow',
            $content,
        );
        self::assertStringContainsString(
            'Event Yesterday',
            $content,
        );
    }

    #[Test]
    public function listSearchResultsWillShowResultsOfLocation(): void
    {
        $this->insertEvent(
            title: 'Event Stuttgart',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            location: 'Stuttgart',
        );
        $this->insertEvent(
            title: 'Event Filderstadt',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            location: 'Filderstadt',
        );
        $this->createDayRelations();

        $body = $this->createBodyFromArray([
            'tx_events2_searchresults' => [
                'search' => [
                    'search' => 'event',
                    'location' => 2,
                ],
            ],
        ]);

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_SEARCH_RESULTS)
                ->withMethod('POST')
                ->withAddedHeader('Content-type', 'application/x-www-form-urlencoded')
                ->withBody($body),
        )->getBody();

        self::assertStringContainsString(
            'Event Filderstadt',
            $content,
        );
        self::assertStringNotContainsString(
            'Event Stuttgart',
            $content,
        );
    }

    #[Test]
    public function listSearchResultsWillShowResultsOfCategory(): void
    {
        $this->insertEvent(
            title: 'Event Audi',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            categories: [2],
        );
        $this->insertEvent(
            title: 'Event BMW',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            categories: [3],
        );
        $this->createDayRelations();

        $body = $this->createBodyFromArray([
            'tx_events2_searchresults' => [
                'search' => [
                    'search' => 'event',
                    'mainCategory' => 2,
                ],
            ],
        ]);

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_SEARCH_RESULTS)
                ->withMethod('POST')
                ->withAddedHeader('Content-type', 'application/x-www-form-urlencoded')
                ->withBody($body),
        )->getBody();

        self::assertStringContainsString(
            'Event Audi',
            $content,
        );
        self::assertStringNotContainsString(
            'Event BMW',
            $content,
        );
    }

    #[Test]
    public function listSearchResultsWillShowResultsWithFreeEntry(): void
    {
        $this->insertEvent(
            title: 'Event Free',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: [
                'free_entry' => 1,
            ],
        );
        $this->insertEvent(
            title: 'Event Paid',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: [
                'free_entry' => 0,
            ],
        );
        $this->createDayRelations();

        $body = $this->createBodyFromArray([
            'tx_events2_searchresults' => [
                'search' => [
                    'search' => 'event',
                    'freeEntry' => 1,
                ],
            ],
        ]);

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_SEARCH_RESULTS)
                ->withMethod('POST')
                ->withAddedHeader('Content-type', 'application/x-www-form-urlencoded')
                ->withBody($body),
        )->getBody();

        self::assertStringContainsString(
            'Event Free',
            $content,
        );
        self::assertStringNotContainsString(
            'Event Paid',
            $content,
        );
    }
}
