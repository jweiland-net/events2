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
class VideoControllerTest extends FunctionalTestCase
{
    protected ServerRequestInterface $serverRequest;

    protected array $testExtensionsToLoad = [
        'jweiland/events2',
        'sjbr/static-info-tables',
    ];

    protected function setUp(): void
    {
        self::markTestIncomplete('VideoControllerTest not updated until right now');

        parent::setUp();

        $this->importDataSet('ntf://Database/pages.xml');
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
    public function bootstrapShowActionWillNotRenderVideoLink(): void
    {
        $date = new \DateTimeImmutable('midnight');
        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_event',
            [
                'pid' => 1,
                'event_type' => 'single',
                'event_begin' => (int)$date->format('U'),
                'title' => 'Today',
            ],
        );

        $this->startUpTSFE(
            $this->serverRequest,
            1,
            '0',
            [
                'tx_events2_list' => [
                    'controller' => 'Video',
                    'action' => 'show',
                    'event' => '1',
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
            '',
            trim($content),
        );
    }

    /**
     * @test
     */
    public function bootstrapShowActionWillRenderVideoLink(): void
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_events2_domain_model_link.xml');

        $date = new \DateTimeImmutable('midnight');
        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_event',
            [
                'pid' => 1,
                'event_type' => 'single',
                'event_begin' => (int)$date->format('U'),
                'title' => 'Today',
                'video_link' => 1,
            ],
        );

        $this->startUpTSFE(
            $this->serverRequest,
            1,
            '0',
            [
                'tx_events2_list' => [
                    'controller' => 'Video',
                    'action' => 'show',
                    'event' => '1',
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
            'Header: YouTube',
            $content,
        );
        self::assertStringContainsString(
            'YouTube URL: //www.youtube.com/embed/5Xqo_SPiHlY',
            $content,
        );
    }
}
