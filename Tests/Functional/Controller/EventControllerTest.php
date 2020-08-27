<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Controller;

use JWeiland\Events2\Controller\EventController;
use JWeiland\Events2\Domain\Model\Search;
use JWeiland\Events2\Domain\Repository\CategoryRepository;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

/**
 * Test case.
 */
class EventControllerTest extends FunctionalTestCase
{
    /**
     * @var EventController
     */
    protected $subject;

    /**
     * @var DayRepository|ObjectProphecy
     */
    protected $dayRepositoryProphecy;

    /**
     * @var EventRepository|ObjectProphecy
     */
    protected $eventRepositoryProphecy;

    /**
     * @var CategoryRepository|ObjectProphecy
     */
    protected $categoryRepositoryProphecy;

    /**
     * @var QueryResult|ObjectProphecy
     */
    protected $queryResultProphecy;

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

    public function setUp()
    {
        parent::setUp();
        $this->setUpBackendUserFromFixture(1);
        $this->importDataSet('ntf://Database/pages.xml');
        $this->setUpFrontendRootPage(1, [__DIR__ . '/../Fixtures/TypoScript/plugin.typoscript']);

        $this->queryResultProphecy = $this->prophesize(QueryResult::class);
        $this->queryResultProphecy
            ->getQuery()
            ->willReturn($this->prophesize(Query::class)->reveal());

        $this->request = new Request();
        if (method_exists($this->request, 'setControllerAliasToClassNameMapping')) {
            $this->request->setControllerAliasToClassNameMapping([
                'Event' => EventController::class
            ]);
        }
        $this->request->setControllerExtensionName('Events2');
        $this->request->setPluginName('Events');
        $this->request->setControllerName('Event');

        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->subject = $objectManager->get(EventController::class);
    }

    public function tearDown()
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
    public function processRequestWithListSearchResultsWillSearchForEvents()
    {
        $this->dayRepositoryProphecy = $this->prophesize(DayRepository::class);
        $this->dayRepositoryProphecy
            ->setSettings(Argument::any())
            ->shouldBeCalled();

        $search = new Search();
        $this->dayRepositoryProphecy
            ->searchEvents($search)
            ->shouldBeCalled()
            ->willReturn($this->queryResultProphecy->reveal());
        $this->subject->injectDayRepository($this->dayRepositoryProphecy->reveal());

        $this->request->setControllerActionName('listSearchResults');
        $this->request->setArgument('search', $search);

        $response = new Response();

        $this->subject->processRequest($this->request, $response);
    }

    /**
     * @test
     */
    public function processRequestWithListMyEventsWillCallFindMyEvents()
    {
        $this->eventRepositoryProphecy = $this->prophesize(EventRepository::class);
        $this->eventRepositoryProphecy
            ->setSettings(Argument::any())
            ->shouldBeCalled();

        $this->eventRepositoryProphecy
            ->findMyEvents()
            ->shouldBeCalled()
            ->willReturn($this->queryResultProphecy->reveal());
        $this->subject->injectEventRepository($this->eventRepositoryProphecy->reveal());

        $this->request->setControllerActionName('listMyEvents');

        $response = new Response();

        $this->subject->processRequest($this->request, $response);
    }

    /**
     * @test
     */
    public function processRequestWithNewActionWillCollectSelectableCategories()
    {
        $this->queryResultProphecy
            ->count()
            ->shouldBeCalled()
            ->willReturn(3);

        $this->categoryRepositoryProphecy = $this->prophesize(CategoryRepository::class);
        $this->categoryRepositoryProphecy
            ->getCategories('1,12,123')
            ->shouldBeCalled()
            ->willReturn($this->queryResultProphecy->reveal());
        $this->subject->injectCategoryRepository($this->categoryRepositoryProphecy->reveal());

        $this->request->setControllerActionName('new');

        $response = new Response();

        $this->subject->processRequest($this->request, $response);
    }
}
