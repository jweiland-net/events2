<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Controller;

use JWeiland\Events2\Tests\Functional\AbstractFunctionalTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;

/**
 * Test case.
 */
class LocationControllerTest extends AbstractFunctionalTestCase
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

    protected function setUp(): void
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        if (version_compare($typo3Version->getBranch(), '11', '<')) {
            $this->markTestSkipped(
                'Because of missing Context class in TYPO3 10 this test has to be skipped.'
            );
        }

        parent::setUp();

        $this->importDataSet('ntf://Database/pages.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_events2_domain_model_location.xml');
        $this->setUpFrontendRootPage(1, [__DIR__ . '/../Fixtures/TypoScript/setup.typoscript']);

        $this->serverRequest = $this->getServerRequestForFrontendMode();
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
    public function processRequestWithShowActionWillAssignLocationToView(): void
    {
        $this->startUpTSFE(
            $this->serverRequest,
            1,
            '0',
            [
                'tx_events2_list' => [
                    'controller' => 'Location',
                    'action' => 'show',
                    'location' => '1',
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
            'Kino',
            $content
        );
        self::assertStringContainsString(
            'Cinemastreet 42',
            $content
        );
        self::assertStringContainsString(
            '12345 Everywhere',
            $content
        );
    }
}
