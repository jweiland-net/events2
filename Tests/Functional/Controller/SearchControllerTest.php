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
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;

/**
 * Test case.
 */
class SearchControllerTest extends AbstractFunctionalTestCase
{
    use ProphecyTrait;

    protected ServerRequest $serverRequest;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2',
        'typo3conf/ext/static_info_tables',
    ];

    protected function setUp(): void
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        if (version_compare($typo3Version->getBranch(), '11', '<')) {
            self::markTestSkipped(
                'Because of missing Context class in TYPO3 10 this test has to be skipped.'
            );
        }

        parent::setUp();

        $this->importDataSet('ntf://Database/pages.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/sys_category.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/sys_category_record_mm.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_events2_domain_model_event.xml');
        $this->setUpFrontendRootPage(1, [__DIR__ . '/../Fixtures/TypoScript/setup.typoscript']);

        $this->serverRequest = $this->getServerRequestForFrontendMode();

        // ServerRequest is needed for following
        $GLOBALS['TYPO3_REQUEST'] = $this->serverRequest;
        $dayRelationService = GeneralUtility::makeInstance(DayRelationService::class);
        $statement = $this->getDatabaseConnection()->select('*', 'tx_events2_domain_model_event', 'pid=1');
        while ($eventRecord = $statement->fetchAssociative()) {
            $dayRelationService->createDayRelations($eventRecord['uid']);
        }
    }

    protected function tearDown(): void
    {
        unset(
            $this->serverRequest,
            $GLOBALS['TSFE']
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function processRequestWithShowActionWillAssignEmptySearchObject(): void
    {
        $this->startUpTSFE($this->serverRequest);

        $extbaseBootstrap = GeneralUtility::makeInstance(Bootstrap::class);
        $content = $extbaseBootstrap->run(
            '',
            [
                'extensionName' => 'Events2',
                'pluginName' => 'SearchForm',
                'format' => 'txt',
                'settings' => [
                    'rootCategory' => '1',
                    'mainCategories' => '2,3',
                ],
            ]
        );

        self::assertStringContainsString(
            'Free entry: no',
            $content
        );
    }

    /**
     * @test
     */
    public function processRequestWithShowActionWillUpdateFormValues(): void
    {
        $this->startUpTSFE(
            $this->serverRequest,
            1,
            '0',
            [
                'tx_events2_searchform' => [
                    'search' => [
                        'search' => 'Test',
                        'freeEntry' => '1',
                    ],
                ],
            ]
        );

        $extbaseBootstrap = GeneralUtility::makeInstance(Bootstrap::class);
        $content = $extbaseBootstrap->run(
            '',
            [
                'extensionName' => 'Events2',
                'pluginName' => 'SearchForm',
                'format' => 'txt',
                'settings' => [
                    'rootCategory' => '1',
                    'mainCategories' => '2,3',
                ],
            ]
        );

        self::assertStringContainsString(
            'Search: Test',
            $content
        );
        self::assertStringContainsString(
            'Free entry: yes',
            $content
        );
    }
}
