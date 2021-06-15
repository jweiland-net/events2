<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Controller;

use JWeiland\Events2\Controller\SearchController;
use JWeiland\Events2\Domain\Model\Search;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Test case.
 */
class SearchControllerTest extends FunctionalTestCase
{
    /**
     * @var SearchController
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
        $this->setUpBackendUserFromFixture(1);
        $this->importDataSet('ntf://Database/pages.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_events2_domain_model_event.xml');
        $this->setUpFrontendRootPage(1, [__DIR__ . '/../Fixtures/TypoScript/plugin.typoscript']);

        $this->request = new Request();
        if (method_exists($this->request, 'setControllerAliasToClassNameMapping')) {
            $this->request->setControllerAliasToClassNameMapping([
                'Search' => SearchController::class
            ]);
        }
        $this->request->setControllerExtensionName('Events2');
        $this->request->setPluginName('Search');
        $this->request->setControllerName('Search');

        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->subject = $objectManager->get(SearchController::class);
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
    public function processRequestWithShowActionWillAssignEmptySearchObject(): void
    {
        $this->request->setControllerActionName('show');

        $response = new Response();

        $this->subject->processRequest($this->request, $response);
        $content = trim($response->getContent());

        self::assertStringContainsString(
            'Search:',
            $content
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
        $search = new Search();
        $search->setSearch('Test');
        $search->setFreeEntry(true);
        $this->request->setControllerActionName('show');
        $this->request->setArgument('search', $search);

        $response = new Response();

        $this->subject->processRequest($this->request, $response);
        $content = trim($response->getContent());

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
