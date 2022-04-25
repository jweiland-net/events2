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
use JWeiland\Events2\Tests\Functional\AbstractFunctionalTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;

/**
 * Test case.
 */
class DayControllerTest extends AbstractFunctionalTestCase
{
    use ProphecyTrait;

    protected ServerRequest $serverRequest;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2',
        'typo3conf/ext/static_info_tables'
    ];

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
        'fluid_styled_content'
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet('ntf://Database/pages.xml');
        $this->setUpFrontendRootPage(1, [__DIR__ . '/../Fixtures/TypoScript/setup.typoscript']);

        $this->serverRequest = $this->getServerRequestForFrontendMode();

        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_organizer',
            [
                'pid' => 1,
                'organizer' => 'Stefan',
            ]
        );

        $date = new \DateTimeImmutable('midnight');
        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_event',
            [
                'pid' => 1,
                'event_type' => 'single',
                'event_begin' => (int)$date->format('U'),
                'title' => 'Today',
            ]
        );

        $date = new \DateTimeImmutable('tomorrow midnight');
        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_event',
            [
                'pid' => 1,
                'event_type' => 'single',
                'event_begin' => (int)$date->format('U'),
                'title' => 'Tomorrow',
                'organizers' => 1
            ]
        );

        $this->getDatabaseConnection()->insertArray(
            'tx_events2_event_organizer_mm',
            [
                'uid_local' => 2,
                'uid_foreign' => 1
            ]
        );

        // ServerRequest is needed for following
        $GLOBALS['TYPO3_REQUEST'] = $this->serverRequest;
        $dayRelationService = GeneralUtility::makeInstance(DayRelationService::class);
        $statement = $this->getDatabaseConnection()->select('*', 'tx_events2_domain_model_event', 'pid=1');
        while ($eventRecord = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $dayRelationService->createDayRelations($eventRecord['uid']);
        }
    }

    protected function tearDown(): void
    {
        unset(
            $this->request,
            $GLOBALS['TSFE']
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function bootstrapListActionWillListAllEvents(): void
    {
        $this->startUpTSFE($this->serverRequest);

        $extbaseBootstrap = GeneralUtility::makeInstance(Bootstrap::class);
        $content = $extbaseBootstrap->run(
            '',
            [
                'extensionName' => 'Events2',
                'pluginName' => 'List',
                'format' => 'txt',
            ]
        );

        self::assertStringContainsString(
            'Event Title 1: Today',
            $content
        );
        self::assertStringContainsString(
            'Event Title 2: Tomorrow',
            $content
        );
    }

    /**
     * @test
     */
    public function bootstrapListActionWillListEventsWithOrganizer(): void
    {
        $this->startUpTSFE(
            $this->serverRequest,
            1,
            '0',
            [
                'tx_events2_list' => [
                    'filter' => [
                        'organizer' => '1'
                    ]
                ]
            ]
        );

        $extbaseBootstrap = GeneralUtility::makeInstance(Bootstrap::class);
        $content = $extbaseBootstrap->run(
            '',
            [
                'extensionName' => 'Events2',
                'pluginName' => 'List',
                'format' => 'txt'
            ]
        );

        self::assertStringContainsString(
            'Event Title 2: Tomorrow',
            $content
        );
    }

    /**
     * @return array<string, array<string|\JWeiland\Events2\Domain\Model\Filter>>
     */
    public function listWithFilledFilterDataProvider(): array
    {
        return [
            'ListType: list' => ['list'],
            'ListType: list latest' => ['listLatest'],
            'ListType: list today' => ['listToday'],
            'ListType: list this week' => ['listWeek'],
            'ListType: list range' => ['listRange'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider listWithFilledFilterDataProvider
     */
    public function bootstrapWithVariousListTypesWillAssignFilterToView(string $listType): void
    {
        $today = new \DateTimeImmutable('today midnight');

        $this->startUpTSFE(
            $this->serverRequest,
            1,
            '0',
            [
                'tx_events2_list' => [
                    'filter' => [
                        'timestamp' => $today->format('U')
                    ]
                ]
            ]
        );

        $extbaseBootstrap = GeneralUtility::makeInstance(Bootstrap::class);
        $content = $extbaseBootstrap->run(
            '',
            [
                'extensionName' => 'Events2',
                'pluginName' => 'List',
                'format' => 'txt',
                'settings' => [
                    'listType' => $listType
                ]
            ]
        );

        self::assertStringContainsString(
            'Event Title 1: Today',
            $content
        );
    }

    /**
     * @test
     */
    public function bootstrapShowActionWillShowEvent(): void
    {
        $today = new \DateTimeImmutable('today midnight');

        $this->startUpTSFE(
            $this->serverRequest,
            1,
            '0',
            [
                'tx_events2_list' => [
                    'event' => '1',
                    'timestamp' => $today->format('U')
                ]
            ]
        );

        $extbaseBootstrap = GeneralUtility::makeInstance(Bootstrap::class);
        $content = $extbaseBootstrap->run(
            '',
            [
                'extensionName' => 'Events2',
                'pluginName' => 'List',
                'format' => 'txt',
            ]
        );

        self::assertStringContainsString(
            'Event Title 1: Today',
            $content
        );
    }
}
