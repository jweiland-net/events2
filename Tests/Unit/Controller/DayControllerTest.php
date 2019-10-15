<?php

namespace JWeiland\Events2\Tests\Unit\Controller;

/*
 * This file is part of the events2 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use JWeiland\Events2\Controller\DayController;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Service\JsonLdService;
use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 */
class DayControllerTest extends UnitTestCase
{
    /**
     * @var DayController|\PHPUnit_Framework_MockObject_MockObject|AccessibleMockObjectInterface
     */
    protected $subject;

    /**
     * @var DayRepository|ObjectProphecy
     */
    protected $dayRepository;

    /**
     * @var EventRepository|ObjectProphecy
     */
    protected $eventRepository;

    /**
     * @var ObjectManager|ObjectProphecy
     */
    protected $objectManager;

    /**
     * @var ConfigurationManager|ObjectProphecy
     */
    protected $configurationManagerProphecy;

    /**
     * @var ControllerContext|ObjectProphecy
     */
    protected $controllerContextProphecy;

    /**
     * @var TemplateView|ObjectProphecy
     */
    protected $view;

    /**
     * set up.
     */
    public function setUp()
    {
        $tsfeProphecy = $this->prophesize(TypoScriptFrontendController::class);
        $GLOBALS['TSFE'] = $tsfeProphecy->reveal();
        $this->objectManager = $this->prophesize(ObjectManager::class);
        $this->view = $this->prophesize(TemplateView::class);
        $this->dayRepository = $this->prophesize(DayRepository::class);
        $this->eventRepository = $this->prophesize(EventRepository::class);
        $this->configurationManagerProphecy = $this->prophesize(ConfigurationManager::class);
        $this->controllerContextProphecy = $this->prophesize(ControllerContext::class);

        $settings = [
            'latest' => [
                'amountOfRecordsToShow' => 7
            ]
        ];

        $this->subject = $this->getAccessibleMock(DayController::class, ['dummy']);
        $this->subject->_set('settings', $settings);
        $this->subject->_set('objectManager', $this->objectManager->reveal());
        $this->subject->_set('view', $this->view->reveal());
        $this->subject->_set('dayRepository', $this->dayRepository->reveal());
        $this->subject->_set('eventRepository', $this->eventRepository->reveal());
        $this->subject->_set('configurationManager', $this->configurationManagerProphecy->reveal());
        $this->subject->_set('controllerContext', $this->controllerContextProphecy->reveal());
    }

    /**
     * taer down.
     */
    public function tearDown()
    {
        unset($this->subject);
        unset($GLOBALS['TSFE']);
    }

    /**
     * @test
     */
    public function listActionFindEventsAndAssignsThemToView()
    {
        $filter = new Filter();
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

        $this->dayRepository->findEvents(
            Argument::exact('list'),
            Argument::exact($filter)
        )->shouldBeCalled()->willReturn($queryResultProphecy->reveal());
        $this->view->assign(
            Argument::exact('filter'),
            Argument::exact($filter)
        )->shouldBeCalled();
        $this->view->assign(
            Argument::exact('days'),
            Argument::exact($queryResultProphecy->reveal())
        )->shouldBeCalled();

        $this->subject->listAction($filter);
    }

    /**
     * @test
     */
    public function listLatestActionFindEventsAndAssignsThemToView()
    {
        $filter = new Filter();
        $queryResult = new QueryResult(new Query(Day::class));
        $settings = $this->subject->_get('settings');
        $settings['mergeRecurringEvents'] = 0;
        $this->subject->_set('settings', $settings);

        $this->dayRepository->findEvents(
            Argument::exact('latest'),
            Argument::exact($filter),
            Argument::exact(7)
        )->shouldBeCalled()->willReturn($queryResult);
        $this->view->assign(
            Argument::exact('filter'),
            Argument::exact($filter)
        )->shouldBeCalled();
        $this->view->assign(
            Argument::exact('days'),
            Argument::exact($queryResult)
        )->shouldBeCalled();

        $this->subject->listLatestAction($filter);
    }

    /**
     * @test
     */
    public function listTodayActionFindEventsAndAssignsThemToView()
    {
        $filter = new Filter();
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

        $this->dayRepository->findEvents(
            Argument::exact('today'),
            Argument::exact($filter)
        )->shouldBeCalled()->willReturn($queryResultProphecy->reveal());
        $this->view->assign(
            Argument::exact('filter'),
            Argument::exact($filter)
        )->shouldBeCalled();
        $this->view->assign(
            Argument::exact('days'),
            Argument::exact($queryResultProphecy->reveal())
        )->shouldBeCalled();

        $this->subject->listTodayAction($filter);
    }

    /**
     * @test
     */
    public function listThisWeekActionFindEventsAndAssignsThemToView()
    {
        $filter = new Filter();
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

        $this->dayRepository->findEvents(
            Argument::exact('thisWeek'),
            Argument::exact($filter)
        )->shouldBeCalled()->willReturn($queryResultProphecy->reveal());
        $this->view->assign(
            Argument::exact('filter'),
            Argument::exact($filter)
        )->shouldBeCalled();
        $this->view->assign(
            Argument::exact('days'),
            Argument::exact($queryResultProphecy->reveal())
        )->shouldBeCalled();

        $this->subject->listThisWeekAction($filter);
    }

    /**
     * @test
     */
    public function listRangeActionFindEventsAndAssignsThemToView()
    {
        $filter = new Filter();
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

        $this->dayRepository->findEvents(
            Argument::exact('range'),
            Argument::exact($filter)
        )->shouldBeCalled()->willReturn($queryResultProphecy->reveal());
        $this->view->assign(
            Argument::exact('filter'),
            Argument::exact($filter)
        )->shouldBeCalled();
        $this->view->assign(
            Argument::exact('days'),
            Argument::exact($queryResultProphecy->reveal())
        )->shouldBeCalled();

        $this->subject->listRangeAction($filter);
    }

    /**
     * @test
     */
    public function showActionWithTimestampCallsAssign()
    {
        $event = 32415;
        $timestamp = 1234567890;
        $day = new Day();
        $day->setEvent(new Event());

        $this->dayRepository->findDayByEventAndTimestamp(
            Argument::exact($event),
            Argument::exact($timestamp)
        )->shouldBeCalled()->willReturn($day);
        $this->eventRepository
            ->findByIdentifier(Argument::cetera())
            ->shouldNotBeCalled();
        $this->view->assign(
            Argument::exact('day'),
            Argument::exact($day)
        )->shouldBeCalled();

        /** @var JsonLdService|ObjectProphecy $jsonLdServiceProphecy */
        $jsonLdServiceProphecy = $this->prophesize(JsonLdService::class);
        GeneralUtility::addInstance(JsonLdService::class, $jsonLdServiceProphecy->reveal());

        $this->subject->showAction($event, $timestamp);
    }

    /**
     * @test
     */
    public function showActionWithoutTimestampGeneratesEmptyDay()
    {
        $eventUid = 32415;
        $event = new Event();
        $day = new Day();
        $day->setEvent($event);

        $this->dayRepository
            ->findDayByEventAndTimestamp(
                Argument::exact($eventUid),
                Argument::exact(0)
            )
            ->shouldBeCalled()
            ->willReturn($day);
        $this->view->assign(
            Argument::exact('day'),
            Argument::exact($day)
        )->shouldBeCalled();

        /** @var JsonLdService|ObjectProphecy $jsonLdServiceProphecy */
        $jsonLdServiceProphecy = $this->prophesize(JsonLdService::class);
        GeneralUtility::addInstance(JsonLdService::class, $jsonLdServiceProphecy->reveal());

        $this->subject->showAction($eventUid);
    }

    /**
     * @test
     */
    public function showByTimestampWithTimestampCallsAssign()
    {
        $timestamp = 1234567890;
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
            ->findByTimestamp(Argument::exact($timestamp))
            ->shouldBeCalled()
            ->willReturn($queryResultProphecy->reveal());

        $this->view->assign(
            Argument::exact('days'),
            Argument::exact($queryResultProphecy->reveal())
        );

        $this->subject->showByTimestampAction($timestamp);
    }
}
