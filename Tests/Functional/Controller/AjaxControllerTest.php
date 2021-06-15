<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Controller;

use JWeiland\Events2\Controller\AjaxController;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Test case.
 */
class AjaxControllerTest extends FunctionalTestCase
{
    /**
     * @var AjaxController
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
        $this->setUpFrontendRootPage(1, [__DIR__ . '/../Fixtures/TypoScript/plugin.typoscript']);

        $this->request = new Request();
        if (method_exists($this->request, 'setControllerAliasToClassNameMapping')) {
            $this->request->setControllerAliasToClassNameMapping([
                'Ajax' => AjaxController::class
            ]);
        }
        $this->request->setControllerExtensionName('Events2');
        $this->request->setPluginName('Events');
        $this->request->setControllerName('Ajax');

        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->subject = $objectManager->get(AjaxController::class);
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
    public function callAjaxObjectActionWithEmptyObjectNameResultsInEmptyString(): void
    {
        $this->request->setControllerActionName('callAjaxObject');
        $this->request->setArgument('objectName', '');

        $response = new Response();

        $this->subject->processRequest($this->request, $response);
        $content = $response->getContent();

        self::assertEmpty(
            $content
        );
    }

    /**
     * @test
     */
    public function callAjaxObjectActionWithInvalidObjectNameResultsInEmptyString(): void
    {
        $this->request->setControllerActionName('callAjaxObject');
        $this->request->setArgument('objectName', 'WrongObjectName');

        $response = new Response();

        $this->subject->processRequest($this->request, $response);
        $content = $response->getContent();

        self::assertEmpty(
            $content
        );
    }

    /**
     * @test
     */
    public function callAjaxObjectActionWithLowerCasedObjectNameWillBeConvertedToUcFirst(): void
    {
        $this->request->setControllerActionName('callAjaxObject');
        $this->request->setArgument('objectName', 'findSubCategories');

        $response = new Response();

        $this->subject->processRequest($this->request, $response);
        $content = $response->getContent();

        self::assertSame(
            '{}',
            $content
        );
    }

    /**
     * @test
     */
    public function callAjaxObjectActionWithValidObjectNameAndArgumentsResultsWithJsonOutput(): void
    {
        $this->request->setControllerActionName('callAjaxObject');
        $this->request->setArgument('objectName', 'FindSubCategories');
        $this->request->setArgument(
            'arguments',
            [
                'category' => 123
            ]
        );

        $response = new Response();

        $this->subject->processRequest($this->request, $response);
        $content = $response->getContent();

        self::assertSame(
            '{}',
            $content
        );
    }
}
