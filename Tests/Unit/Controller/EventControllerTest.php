<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Controller;

use JWeiland\Events2\Controller\EventController;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Search;
use JWeiland\Events2\Domain\Repository\CategoryRepository;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Domain\Repository\LocationRepository;
use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 */
class EventControllerTest extends UnitTestCase
{
    /**
     * @var EventController|\PHPUnit_Framework_MockObject_MockObject|AccessibleMockObjectInterface
     */
    protected $subject;

    /**
     * @var DayRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dayRepository;

    /**
     * @var EventRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventRepository;

    /**
     * @var LocationRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $locationRepository;

    /**
     * @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryRepository;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var ControllerContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $controllerContext;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var TemplateView|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $view;

    /**
     * set up.
     */
    public function setUp()
    {
        $tsfeProphecy = $this->prophesize(TypoScriptFrontendController::class);
        $GLOBALS['TSFE'] = $tsfeProphecy->reveal();
        $this->request = $this->getMockBuilder(Request::class)->getMock();

        $this->controllerContext = $this->getMockBuilder(ControllerContext::class)->setMethods(['dummy'])->getMock();
        $this->controllerContext->setRequest($this->request);

        $this->dayRepository = $this
            ->getMockBuilder(DayRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventRepository = $this
            ->getMockBuilder(EventRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->locationRepository = $this
            ->getMockBuilder(LocationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryRepository = $this
            ->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = $this
            ->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->view = $this
            ->getMockBuilder(TemplateView::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = $this->getAccessibleMock(EventController::class, ['addFlashMessage']);
        $this->subject->injectDayRepository($this->dayRepository);
        $this->subject->injectEventRepository($this->eventRepository);
        $this->subject->injectLocationRepository($this->locationRepository);
        $this->subject->injectCategoryRepository($this->categoryRepository);
        $this->subject->injectObjectManager($this->objectManager);
        $this->subject->_set('controllerContext', $this->controllerContext);
        $this->subject->_set('view', $this->view);
    }

    /**
     * taer down.
     */
    public function tearDown()
    {
        unset($this->subject);
        unset($this->dayRepository);
        unset($this->eventRepository);
        unset($this->view);
        unset($GLOBALS['TSFE']);
    }

    /**
     * @test
     */
    public function injectConfigurationManagerOverridesTypoScriptWithFlexFormSettings()
    {
        $typoScript = [
            'settings' => [
                'pidOfDetailPage' => '217',
                'pidOfListPage' => '217',
                'pidOfSearchPage' => '217',
                'pidOfLocationPage' => '217',
                'rootCategory' => '12'
            ]
        ];
        $flexFormSettings = [
            'pidOfDetailPage' => '0',
            'pidOfListPage' => '363',
            'pidOfSearchPage' => '217',
            'pidOfLocationPage' => '217',
            'rootCategory' => '12'
        ];

        $expectedResult = [
            'pidOfDetailPage' => '217',
            'pidOfListPage' => '363',
            'pidOfSearchPage' => '217',
            'pidOfLocationPage' => '217',
            'rootCategory' => '12'
        ];

        /** @var ConfigurationManagerInterface|ObjectProphecy $configurationManager */
        $configurationManager = $this->prophesize(ConfigurationManager::class);
        $configurationManager
            ->getConfiguration(
                Argument::exact(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK),
                Argument::exact('events2'),
                Argument::exact('events2_event')
            )
            ->shouldBeCalled()
            ->willReturn($typoScript);
        $configurationManager
            ->getConfiguration(
                Argument::exact(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS)
            )
            ->shouldBeCalled()
            ->willReturn($flexFormSettings);

        $this->subject->injectConfigurationManager($configurationManager->reveal());

        self::assertSame(
            $expectedResult,
            $this->subject->_get('settings')
        );
    }

    /**
     * @test
     */
    public function listSearchResultsActionSearchesForEventsAndAssignsThemToView()
    {
        $search = new Search();
        /** @var QuerySettingsInterface|ObjectProphecy $querySettingsProphecy */
        $querySettingsProphecy = $this->prophesize(Typo3QuerySettings::class);
        /** @var QueryInterface|ObjectProphecy $queryProphecy */
        $queryProphecy = $this->prophesize(Query::class);
        $queryProphecy
            ->getQuerySettings()
            ->shouldBeCalled()
            ->willReturn($querySettingsProphecy->reveal());
        /** @var QueryResultInterface|ObjectProphecy $queryResultProphecy */
        $queryResultProphecy = $this->prophesize(QueryResult::class);
        $queryResultProphecy
            ->getQuery()
            ->shouldBeCalled()
            ->willReturn($queryProphecy->reveal());

        $this->dayRepository
            ->expects(self::once())
            ->method('searchEvents')
            ->with(self::equalTo($search))
            ->willReturn($queryResultProphecy->reveal());

        $this->view
            ->expects(self::once())
            ->method('assign')
            ->with(
                self::equalTo('days'),
                self::equalTo($queryResultProphecy->reveal())
            );

        $this->subject->injectDayRepository($this->dayRepository);
        $this->subject->_set('view', $this->view);

        $this->subject->listSearchResultsAction($search);
    }

    /**
     * @test
     */
    public function listMyEventsActionFindEventsAndAssignsThemToView()
    {
        /** @var QuerySettingsInterface|ObjectProphecy $querySettingsProphecy */
        $querySettingsProphecy = $this->prophesize(Typo3QuerySettings::class);
        /** @var QueryInterface|ObjectProphecy $queryProphecy */
        $queryProphecy = $this->prophesize(Query::class);
        $queryProphecy
            ->getQuerySettings()
            ->shouldBeCalled()
            ->willReturn($querySettingsProphecy->reveal());
        /** @var QueryResultInterface|ObjectProphecy $queryResultProphecy */
        $queryResultProphecy = $this->prophesize(QueryResult::class);
        $queryResultProphecy
            ->getQuery()
            ->shouldBeCalled()
            ->willReturn($queryProphecy->reveal());

        $feUser = new \stdClass();
        $feUser->user = $user = [
            'uid' => 123,
        ];
        $GLOBALS['TSFE']->fe_user = $feUser;

        $this->eventRepository
            ->expects(self::once())
            ->method('findMyEvents')
            ->willReturn($queryResultProphecy->reveal());

        $this->view
            ->expects(self::once())
            ->method('assign')
            ->with(
                self::equalTo('events'),
                self::equalTo($queryResultProphecy->reveal())
            );

        $this->subject->injectEventRepository($this->eventRepository);
        $this->subject->_set('view', $this->view);

        $this->subject->listMyEventsAction();
    }

    /**
     * @test
     */
    public function newActionAddFlashMessageWhenCategoriesAreEmpty()
    {
        $event = new Event();
        /** @var QueryResultInterface|ObjectProphecy $queryResultProphecy */
        $queryResultProphecy = $this->prophesize(QueryResult::class);
        $queryResultProphecy
            ->count()
            ->shouldBeCalled()
            ->willReturn(0);
        $categories = $queryResultProphecy->reveal();

        $this->request
            ->expects(self::once())
            ->method('hasArgument')
            ->with('event');
        $this->request
            ->expects(self::never())
            ->method('getArgument');

        $this->objectManager
            ->expects(self::once())
            ->method('get')
            ->with(self::equalTo(Event::class))
            ->willReturn($event);

        $this->categoryRepository
            ->expects(self::once())
            ->method('getCategories')
            ->with(
                self::equalTo('23,24')
            )
            ->willReturn($categories);

        $this->subject
            ->expects(self::once())
            ->method('addFlashMessage')
            ->with(self::equalTo('Dear Admin: You have forgotten to define some allowed categories in plugin configuration'));

        $this->subject->_set('settings', [
            'selectableCategoriesForNewEvents' => '23,24'
        ]);

        $this->subject->newAction();
    }

    /**
     * @test
     */
    public function newActionFillsTemplateVariables()
    {
        $event = new Event();
        /** @var QueryResultInterface|ObjectProphecy $queryResultProphecy */
        $queryResultProphecy = $this->prophesize(QueryResult::class);
        $queryResultProphecy
            ->count()
            ->shouldBeCalled()
            ->willReturn(2);
        $categories = $queryResultProphecy->reveal();

        $this->request
            ->expects(self::once())
            ->method('hasArgument')
            ->with('event');
        $this->request
            ->expects(self::never())
            ->method('getArgument');

        $this->objectManager
            ->expects(self::once())
            ->method('get')
            ->with(self::equalTo(Event::class))
            ->willReturn($event);

        $this->locationRepository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([]);

        $this->categoryRepository
            ->expects(self::once())
            ->method('getCategories')
            ->with(
                self::equalTo('23,24')
            )
            ->willReturn($categories);

        $this->view
            ->expects(self::at(0))
            ->method('assign')
            ->with(
                self::equalTo('event'),
                self::equalTo($event)
            );
        $this->view
            ->expects(self::at(1))
            ->method('assign')
            ->with(
                self::equalTo('locations'),
                self::equalTo([])
            );
        $this->view
            ->expects(self::at(2))
            ->method('assign')
            ->with(
                self::equalTo('selectableCategories'),
                self::equalTo($categories)
            );

        $this->subject->_set('settings', [
            'selectableCategoriesForNewEvents' => '23,24'
        ]);

        $this->subject->newAction();
    }

    /**
     * @test
     */
    public function newActionWithoutImagesCallsDeleteUploadedFiles()
    {
        /** @var QueryResultInterface|ObjectProphecy $queryResultProphecy */
        $queryResultProphecy = $this->prophesize(QueryResult::class);
        $queryResultProphecy
            ->count()
            ->shouldBeCalled()
            ->willReturn(2);
        $categories = $queryResultProphecy->reveal();

        /** @var Event|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(Event::class)->getMock();
        $event
            ->expects(self::once())
            ->method('getImages')
            ->willReturn([]);

        $this->categoryRepository
            ->expects(self::once())
            ->method('getCategories')
            ->willReturn($categories);

        $this->request
            ->expects(self::once())
            ->method('hasArgument')
            ->with('event')
            ->willReturn(true);
        $this->request
            ->expects(self::once())
            ->method('getArgument')
            ->with('event')
            ->willReturn($event);

        $this->subject->newAction();
    }

    /**
     * @test
     */
    public function newActionWithImagesCallsDeleteUploadedFiles()
    {
        /** @var QueryResultInterface|ObjectProphecy $queryResultProphecy */
        $queryResultProphecy = $this->prophesize(QueryResult::class);
        $queryResultProphecy
            ->count()
            ->shouldBeCalled()
            ->willReturn(2);
        $categories = $queryResultProphecy->reveal();

        /** @var FileReference|\PHPUnit_Framework_MockObject_MockObject $originalResource */
        $originalResource = $this
            ->getMockBuilder(FileReference::class)
            ->disableOriginalConstructor()
            ->getMock();
        $originalResource
            ->expects(self::once())
            ->method('delete');

        /** @var \TYPO3\CMS\Extbase\Domain\Model\FileReference|\PHPUnit_Framework_MockObject_MockObject $uploadedFile */
        $uploadedFile = $this->getMockBuilder(\TYPO3\CMS\Extbase\Domain\Model\FileReference::class)->getMock();
        $uploadedFile
            ->expects(self::once())
            ->method('getOriginalResource')
            ->willReturn($originalResource);

        $images = new ObjectStorage();
        $images->attach($uploadedFile);

        $event = new Event();
        $event->setImages($images);

        $this->categoryRepository
            ->expects(self::once())
            ->method('getCategories')
            ->willReturn($categories);

        $this->request
            ->expects(self::once())
            ->method('hasArgument')
            ->with('event')
            ->willReturn(true);
        $this->request
            ->expects(self::once())
            ->method('getArgument')
            ->with('event')
            ->willReturn($event);

        $this->subject->newAction();
    }
}
