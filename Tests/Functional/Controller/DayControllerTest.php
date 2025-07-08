<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Controller;

use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 */
class DayControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        __DIR__ . '/../Fixtures/Extensions/site_package',
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => [
            'id' => 0,
            'title' => 'English',
            'locale' => 'en_US.UTF8',
            'iso' => 'en'
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->workspace = 0;

        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_organizer');
        $connection->insert(
            'tx_events2_domain_model_organizer',
            [
                'pid' => 11,
                'organizer' => 'Stefan',
            ],
        );

        $date = new \DateTimeImmutable('midnight');

        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_event');
        $connection->insert(
            'tx_events2_domain_model_event',
            [
                'pid' => 11,
                'event_type' => 'single',
                'event_begin' => (int)$date->format('U'),
                'title' => 'Today',
            ],
        );

        $date = new \DateTimeImmutable('tomorrow midnight');

        $connection->insert(
            'tx_events2_domain_model_event',
            [
                'pid' => 11,
                'event_type' => 'single',
                'event_begin' => (int)$date->format('U'),
                'title' => 'Tomorrow',
                'organizers' => 1,
            ],
        );

        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_event_organizer_mm');
        $connection->insert(
            'tx_events2_event_organizer_mm',
            [
                'uid_local' => 2,
                'uid_foreign' => 1,
            ],
        );

        $dayRelationService = GeneralUtility::makeInstance(DayRelationService::class);

        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_event');
        $queryResult = $connection->select(
            ['*'],
            'tx_events2_domain_model_event',
            [
                'pid' => 11,
            ],
        );

        while ($eventRecord = $queryResult->fetchAssociative()) {
            $dayRelationService->createDayRelations((int)$eventRecord['uid']);
        }
    }

    #[Test]
    public function listActionWillListAllEvents(): void
    {
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

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(2)
        );

        $content = (string)$response->getBody();

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
    public function listActionWillListEventsWithOrganizer(): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(2)
        );

        $content = (string)$response->getBody();

        self::assertStringContainsString(
            'Event Title 2: Tomorrow',
            $content,
        );
    }

    public static function listWithFilledFilterDataProvider(): array
    {
        return [
            'ListType: list' => ['list'],
            'ListType: list latest' => ['listLatest'],
            'ListType: list today' => ['listToday'],
            'ListType: list this week' => ['listWeek'],
            'ListType: list range' => ['listRange'],
        ];
    }

    #[Test]
    #[DataProvider('listWithFilledFilterDataProvider')]
    public function variousListTypesWillAssignFilterToView(string $listType): void
    {
        $today = new \DateTimeImmutable('today midnight');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(2)
        );

        $content = (string)$response->getBody();

        self::assertStringContainsString(
            'Event Title 1: Today',
            $content,
        );
    }

    #[Test]
    public function showActionWillShowEvent(): void
    {
        $today = new \DateTimeImmutable('today midnight');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(2)
        );

        $content = (string)$response->getBody();

        self::assertStringContainsString(
            'Event Title 1: Today',
            $content,
        );
    }
}
