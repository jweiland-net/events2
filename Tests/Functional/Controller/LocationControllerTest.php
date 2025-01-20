<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Controller;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 */
class LocationControllerTest extends FunctionalTestCase
{
    protected ServerRequestInterface $serverRequest;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    protected function setUp(): void
    {
        self::markTestIncomplete('LocationControllerTest not updated until right now');

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
            $GLOBALS['TSFE'],
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
                ],
            ],
        );

        $extbaseBootstrap = GeneralUtility::makeInstance(Bootstrap::class);
        $content = $extbaseBootstrap->run(
            '',
            [
                'extensionName' => 'Events2',
                'pluginName' => 'List',
                'format' => 'txt',
            ],
        );

        self::assertStringContainsString(
            'Kino',
            $content,
        );
        self::assertStringContainsString(
            'Cinemastreet 42',
            $content,
        );
        self::assertStringContainsString(
            '12345 Everywhere',
            $content,
        );
    }
}
