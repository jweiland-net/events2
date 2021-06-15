<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Controller;

use JWeiland\Events2\Controller\LocationController;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Test case.
 */
class LocationControllerTest extends FunctionalTestCase
{
    /**
     * @var LocationController
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
        'typo3conf/ext/events2',
        'typo3conf/ext/static_info_tables'
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importDataSet('ntf://Database/pages.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_events2_domain_model_location.xml');
        $this->setUpFrontendRootPage(1, [__DIR__ . '/../Fixtures/TypoScript/plugin.typoscript']);

        $this->request = new Request();
        if (method_exists($this->request, 'setControllerAliasToClassNameMapping')) {
            $this->request->setControllerAliasToClassNameMapping([
                'Location' => LocationController::class
            ]);
        }
        $this->request->setControllerExtensionName('Events2');
        $this->request->setPluginName('Events');
        $this->request->setControllerName('Location');

        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->subject = $objectManager->get(LocationController::class);
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
    public function processRequestWithShowActionWillAssignLocationToView(): void
    {
        $this->request->setControllerActionName('show');
        $this->request->setArgument('location', 1);

        $response = new Response();

        $this->subject->processRequest($this->request, $response);
        $content = $response->getContent();

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
