<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Controller;

use JWeiland\Events2\Controller\DayController;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Repository\DayRepository;
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
class DayControllerTest extends FunctionalTestCase
{
    /**
     * @var DayController
     */
    protected $subject;

    /**
     * @var DayRepository|ObjectProphecy
     */
    protected $dayRepositoryProphecy;

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
        $this->importDataSet('ntf://Database/pages.xml');
        $this->setUpFrontendRootPage(1, [__DIR__ . '/../Fixtures/TypoScript/plugin.typoscript']);

        $this->dayRepositoryProphecy = $this->prophesize(DayRepository::class);
        $this->dayRepositoryProphecy
            ->setSettings(Argument::any())
            ->shouldBeCalled();

        $this->queryResultProphecy = $this->prophesize(QueryResult::class);
        $this->queryResultProphecy
            ->getQuery()
            ->willReturn($this->prophesize(Query::class)->reveal());

        $this->request = new Request();
        if (method_exists($this->request, 'setControllerAliasToClassNameMapping')) {
            $this->request->setControllerAliasToClassNameMapping([
                'Day' => DayController::class
            ]);
        }
        $this->request->setControllerExtensionName('Events2');
        $this->request->setPluginName('Events');
        $this->request->setControllerName('Day');

        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->subject = $objectManager->get(DayController::class);
    }

    public function tearDown()
    {
        unset(
            $this->subject,
            $this->request
        );

        parent::tearDown();
    }

    public function listWithEmptyFilterDataProvider()
    {
        $filter = new Filter();

        return [
            'Action: list' => ['list', 'list', $filter, null],
            'Action: list latest' => ['listLatest', 'latest', $filter, 2],
            'Action: list today' => ['listToday', 'today', $filter, null],
            'Action: list this week' => ['listThisWeek', 'thisWeek', $filter, null],
            'Action: list range' => ['listRange', 'range', $filter, null],
        ];
    }

    /**
     * @test
     *
     * @dataProvider listWithEmptyFilterDataProvider
     */
    public function processRequestWithListActionWillValidateAndAssignFilterToView(
        $action,
        $type,
        $filter,
        $amountOfRecords
    ) {
        $this->dayRepositoryProphecy
            ->findEvents($type, $filter, $amountOfRecords)
            ->shouldBeCalled()
            ->willReturn($this->queryResultProphecy->reveal());
        $this->subject->injectDayRepository($this->dayRepositoryProphecy->reveal());
        $this->request->setControllerActionName($action);

        $this->subject->processRequest($this->request, new Response());
    }

    public function listWithFilledFilterDataProvider()
    {
        $filter = new Filter();
        $filter->setOrganizer(8);

        return [
            'Action: list' => ['list', 'list', $filter, null],
            'Action: list latest' => ['listLatest', 'latest', $filter, 2],
            'Action: list today' => ['listToday', 'today', $filter, null],
            'Action: list this week' => ['listThisWeek', 'thisWeek', $filter, null],
            'Action: list range' => ['listRange', 'range', $filter, null],
        ];
    }

    /**
     * @test
     *
     * @dataProvider listWithFilledFilterDataProvider
     */
    public function processRequestWithListActionWillAssignFilterToView(
        $action,
        $type,
        $filter,
        $amountOfRecords
    ) {
        $this->dayRepositoryProphecy
            ->findEvents($type, $filter, $amountOfRecords)
            ->shouldBeCalled()
            ->willReturn($this->queryResultProphecy->reveal());
        $this->subject->injectDayRepository($this->dayRepositoryProphecy->reveal());
        $this->request->setControllerActionName($action);
        $this->request->setArgument('filter', $filter);

        $response = new Response();

        $this->subject->processRequest($this->request, $response);
        $content = $response->getContent();

        self::assertStringContainsString(
            'Organizer: 8',
            $content
        );
    }

    /**
     * @test
     */
    public function processRequestWithShowActionWillAssignDayToView()
    {
        $event = new Event();
        $event->setTitle('Test Event');
        $date = new \DateTime();
        $day = new Day();
        $day->setDay($date);
        $day->setDayTime($date);
        $day->setSameDayTime($date);
        $day->setSortDayTime($date);
        $day->setEvent($event);

        $this->dayRepositoryProphecy
            ->findDayByEventAndTimestamp(1, 0)
            ->shouldBeCalled()
            ->willReturn($day);
        $this->subject->injectDayRepository($this->dayRepositoryProphecy->reveal());
        $this->request->setControllerActionName('show');
        $this->request->setArgument('event', 1);

        $response = new Response();

        $this->subject->processRequest($this->request, $response);
        $content = $response->getContent();

        self::assertStringContainsString(
            'Title: Test Event',
            $content
        );
    }

    /**
     * @test
     */
    public function processRequestWithShowByTimestampActionWillCallFindByTimestamp()
    {
        $this->dayRepositoryProphecy
            ->findByTimestamp(Argument::any())
            ->shouldBeCalled()
            ->willReturn($this->queryResultProphecy->reveal());
        $this->subject->injectDayRepository($this->dayRepositoryProphecy->reveal());
        $this->request->setControllerActionName('showByTimestamp');
        $this->request->setArgument('timestamp', 1234567890);

        $response = new Response();

        $this->subject->processRequest($this->request, $response);
    }
}
