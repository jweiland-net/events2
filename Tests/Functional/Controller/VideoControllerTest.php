<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Controller;

use JWeiland\Events2\Controller\VideoController;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Test case.
 */
class VideoControllerTest extends FunctionalTestCase
{
    /**
     * @var VideoController
     */
    protected $subject;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2'
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importDataSet('ntf://Database/pages.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_events2_domain_model_event.xml');
        $this->setUpFrontendRootPage(1, [__DIR__ . '/../Fixtures/TypoScript/plugin.typoscript']);

        $this->request = new Request();
        if (method_exists($this->request, 'setControllerAliasToClassNameMapping')) {
            $this->request->setControllerAliasToClassNameMapping([
                'Video' => VideoController::class
            ]);
        }
        $this->request->setControllerExtensionName('Events2');
        $this->request->setPluginName('Events');
        $this->request->setControllerName('Video');

        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->subject = $objectManager->get(VideoController::class);
    }

    public function tearDown(): void
    {
        unset(
            $this->subject,
            $this->request
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function processRequestWithShowActionWillNotRenderAnyVideo(): void
    {
        $databaseConnection = $this->getDatabaseConnection();
        $databaseConnection->updateArray(
            'tx_events2_domain_model_event',
            [
                'uid' => '1'
            ],
            [
                'video_link' => 0
            ]
        );
        $this->request->setControllerActionName('show');
        $this->request->setArgument('event', 1);

        $response = new Response();

        $this->subject->processRequest($this->request, $response);
        $content = trim($response->getContent());

        self::assertSame(
            '',
            $content
        );
    }

    /**
     * @test
     */
    public function processRequestWithShowActionWillConvertYouTubeHashToUrl(): void
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_events2_domain_model_link.xml');

        $this->request->setControllerActionName('show');
        $this->request->setArgument('event', 1);

        $response = new Response();

        $this->subject->processRequest($this->request, $response);
        $content = trim($response->getContent());

        self::assertStringContainsString(
            'Header: YouTube',
            $content
        );
        self::assertStringContainsString(
            'YouTube URL: //www.youtube.com/embed/5Xqo_SPiHlY',
            $content
        );
    }
}
