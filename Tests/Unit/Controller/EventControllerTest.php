<?php

namespace JWeiland\Events2\Tests\Unit\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Stefan Froemken <projects@jweiland.net>, jweiland.net
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use JWeiland\Events2\Controller\EventController;
use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Search;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Fluid\View\TemplateView;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class EventControllerTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Controller\CalendarController
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = new EventController();
    }

    /**
     * taer down.
     */
    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function injectConfigurationManagerOverridesFlexFormSettingsIfEmpty()
    {
        $typoScript = array(
            'settings' => array(
                'pidOfDetailPage' => '217',
                'pidOfListPage' => '217',
                'pidOfSearchPage' => '217',
                'pidOfLocationPage' => '217',
                'rootCategory' => '12'
            )
        );
        $flexFormSettings = array(
            'pidOfDetailPage' => '0',
            'pidOfListPage' => '363',
            'pidOfSearchPage' => '217',
            'pidOfLocationPage' => '217',
            'rootCategory' => '12'
        );

        /** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $configurationManager */
        $configurationManager = $this->getMock(ConfigurationManager::class, array('getConfiguration'), array(), '', false);
        $configurationManager->expects($this->at(0))->method('getConfiguration')->with(
            $this->identicalTo(ConfigurationManager::CONFIGURATION_TYPE_FRAMEWORK),
            $this->identicalTo('events2'),
            $this->identicalTo('events2_event')
        )->willReturn($typoScript);
        $configurationManager->expects($this->at(1))->method('getConfiguration')->with(
            $this->identicalTo(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS)
        )->willReturn($flexFormSettings);

        /** @var EventController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(EventController::class, array('dummy'), array(), '', false);
        $subject->injectConfigurationManager($configurationManager);

        $expectedResult = array(
            'pidOfDetailPage' => '217',
            'pidOfListPage' => '363',
            'pidOfSearchPage' => '217',
            'pidOfLocationPage' => '217',
            'rootCategory' => '12'
        );

        $this->assertSame(
            $expectedResult,
            $subject->_get('settings')
        );
    }

    /**
     * @test
     */
    public function listActionFindEventsAndAssignsThemToView()
    {
        $filter = new Filter();
        $events = new \SplObjectStorage();
        $events->attach(new Event());
        $categories = '1,5,12';
        $mergeEvents = '1';
        $settings = array(
            'categories' => $categories,
            'mergeEvents' => $mergeEvents,
        );

        /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $eventRepository */
        $objectManager = $this->getMock(ObjectManager::class, array('get'), array(), '', false);
        $objectManager->expects($this->once())->method('get')->with(
            $this->identicalTo(Filter::class)
        )->willReturn($filter);
        /** @var \JWeiland\Events2\Domain\Repository\EventRepository|\PHPUnit_Framework_MockObject_MockObject $eventRepository */
        $eventRepository = $this->getMock('JWeiland\\Events2\\Domain\\Repository\\EventRepository', array(), array(), '', false);
        $eventRepository->expects($this->once())->method('findEvents')->with(
            $this->identicalTo('list'),
            $this->identicalTo($filter)
        )->willReturn($events);
        /** @var \TYPO3\CMS\Fluid\View\TemplateView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock(TemplateView::class, array('assign'), array(), '', false);
        $view->expects($this->at(0))->method('assign')->with(
            $this->identicalTo('filter'),
            $this->identicalTo($filter)
        );
        $view->expects($this->at(1))->method('assign')->with(
            $this->identicalTo('events'),
            $this->identicalTo($events)
        );
        /** @var \JWeiland\Events2\Controller\EventController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $eventController */
        $eventController = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\EventController', array('dummy'));
        $eventController->_set('eventRepository', $eventRepository);
        $eventController->_set('objectManager', $objectManager);
        $eventController->_set('settings', $settings);
        $eventController->_set('view', $view);

        $eventController->listAction();
    }

    /**
     * @test
     */
    public function listLatestActionFindEventsAndAssignsThemToView()
    {
        $filter = new Filter();
        $events = new \SplObjectStorage();
        $events->attach(new Event());
        $categories = '1,5,12';
        $mergeEvents = '0';
        $settings = array(
            'categories' => $categories,
            'mergeEvents' => $mergeEvents,
        );

        /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $eventRepository */
        $objectManager = $this->getMock(ObjectManager::class, array('get'), array(), '', false);
        $objectManager->expects($this->once())->method('get')->with(
            $this->identicalTo(Filter::class)
        )->willReturn($filter);
        /** @var \JWeiland\Events2\Domain\Repository\EventRepository|\PHPUnit_Framework_MockObject_MockObject $eventRepository */
        $eventRepository = $this->getMock('JWeiland\\Events2\\Domain\\Repository\\EventRepository', array(), array(), '', false);
        $eventRepository->expects($this->once())->method('findEvents')->with(
            $this->identicalTo('latest')
        )->willReturn($events);
        /** @var \TYPO3\CMS\Fluid\View\TemplateView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock(TemplateView::class, array('assign'), array(), '', false);
        $view->expects($this->at(0))->method('assign')->with(
            $this->identicalTo('filter'),
            $this->identicalTo($filter)
        );
        $view->expects($this->at(1))->method('assign')->with(
            $this->identicalTo('events'),
            $this->identicalTo($events)
        );
        /** @var \JWeiland\Events2\Controller\EventController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $eventController */
        $eventController = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\EventController', array('dummy'));
        $eventController->_set('eventRepository', $eventRepository);
        $eventController->_set('objectManager', $objectManager);
        $eventController->_set('settings', $settings);
        $eventController->_set('view', $view);

        $eventController->listLatestAction();
    }

    /**
     * @test
     */
    public function listTodayActionFindEventsAndAssignsThemToView()
    {
        $filter = new Filter();
        $events = new \SplObjectStorage();
        $events->attach(new Event());
        $categories = '1,5,12';
        $mergeEvents = '0';
        $settings = array(
            'categories' => $categories,
            'mergeEvents' => $mergeEvents,
        );

        /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $eventRepository */
        $objectManager = $this->getMock(ObjectManager::class, array('get'), array(), '', false);
        $objectManager->expects($this->once())->method('get')->with(
            $this->identicalTo(Filter::class)
        )->willReturn($filter);
        /** @var \JWeiland\Events2\Domain\Repository\EventRepository|\PHPUnit_Framework_MockObject_MockObject $eventRepository */
        $eventRepository = $this->getMock('JWeiland\\Events2\\Domain\\Repository\\EventRepository', array(), array(), '', false);
        $eventRepository->expects($this->once())->method('findEvents')->with(
            $this->identicalTo('today')
        )->willReturn($events);
        /** @var \TYPO3\CMS\Fluid\View\TemplateView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock(TemplateView::class, array('assign'), array(), '', false);
        $view->expects($this->at(0))->method('assign')->with(
            $this->identicalTo('filter'),
            $this->identicalTo($filter)
        );
        $view->expects($this->at(1))->method('assign')->with(
            $this->identicalTo('events'),
            $this->identicalTo($events)
        );
        /** @var \JWeiland\Events2\Controller\EventController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $eventController */
        $eventController = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\EventController', array('dummy'));
        $eventController->_set('eventRepository', $eventRepository);
        $eventController->_set('objectManager', $objectManager);
        $eventController->_set('settings', $settings);
        $eventController->_set('view', $view);

        $eventController->listTodayAction();
    }

    /**
     * @test
     */
    public function listThisWeekActionFindEventsAndAssignsThemToView()
    {
        $filter = new Filter();
        $events = new \SplObjectStorage();
        $events->attach(new Event());
        $categories = '1,5,12';
        $mergeEvents = '0';
        $settings = array(
            'categories' => $categories,
            'mergeEvents' => $mergeEvents,
        );

        /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $eventRepository */
        $objectManager = $this->getMock(ObjectManager::class, array('get'), array(), '', false);
        $objectManager->expects($this->once())->method('get')->with(
            $this->identicalTo(Filter::class)
        )->willReturn($filter);
        /** @var \JWeiland\Events2\Domain\Repository\EventRepository|\PHPUnit_Framework_MockObject_MockObject $eventRepository */
        $eventRepository = $this->getMock('JWeiland\\Events2\\Domain\\Repository\\EventRepository', array(), array(), '', false);
        $eventRepository->expects($this->once())->method('findEvents')->with(
            $this->identicalTo('thisWeek')
        )->willReturn($events);
        /** @var \TYPO3\CMS\Fluid\View\TemplateView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock(TemplateView::class, array('assign'), array(), '', false);
        $view->expects($this->at(0))->method('assign')->with(
            $this->identicalTo('filter'),
            $this->identicalTo($filter)
        );
        $view->expects($this->at(1))->method('assign')->with(
            $this->identicalTo('events'),
            $this->identicalTo($events)
        );
        /** @var \JWeiland\Events2\Controller\EventController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $eventController */
        $eventController = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\EventController', array('dummy'));
        $eventController->_set('eventRepository', $eventRepository);
        $eventController->_set('objectManager', $objectManager);
        $eventController->_set('settings', $settings);
        $eventController->_set('view', $view);

        $eventController->listThisWeekAction();
    }

    /**
     * @test
     */
    public function listRangeActionFindEventsAndAssignsThemToView()
    {
        $filter = new Filter();
        $events = new \SplObjectStorage();
        $events->attach(new Event());
        $categories = '1,5,12';
        $mergeEvents = '0';
        $settings = array(
            'categories' => $categories,
            'mergeEvents' => $mergeEvents,
        );

        /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $eventRepository */
        $objectManager = $this->getMock(ObjectManager::class, array('get'), array(), '', false);
        $objectManager->expects($this->once())->method('get')->with(
            $this->identicalTo(Filter::class)
        )->willReturn($filter);
        /** @var \JWeiland\Events2\Domain\Repository\EventRepository|\PHPUnit_Framework_MockObject_MockObject $eventRepository */
        $eventRepository = $this->getMock('JWeiland\\Events2\\Domain\\Repository\\EventRepository', array(), array(), '', false);
        $eventRepository->expects($this->once())->method('findEvents')->with(
            $this->identicalTo('range')
        )->willReturn($events);
        /** @var \TYPO3\CMS\Fluid\View\TemplateView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock(TemplateView::class, array('assign'), array(), '', false);
        $view->expects($this->at(0))->method('assign')->with(
            $this->identicalTo('filter'),
            $this->identicalTo($filter)
        );
        $view->expects($this->at(1))->method('assign')->with(
            $this->identicalTo('events'),
            $this->identicalTo($events)
        );
        /** @var \JWeiland\Events2\Controller\EventController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $eventController */
        $eventController = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\EventController', array('dummy'));
        $eventController->_set('eventRepository', $eventRepository);
        $eventController->_set('objectManager', $objectManager);
        $eventController->_set('settings', $settings);
        $eventController->_set('view', $view);

        $eventController->listRangeAction();
    }

    /**
     * @test
     */
    public function listSearchResultsActionSearchesForEventsAndAssignsThemToView()
    {
        $events = new \SplObjectStorage();
        $events->attach(new Event());
        $searchResult = new Search();

        /** @var \JWeiland\Events2\Domain\Repository\EventRepository|\PHPUnit_Framework_MockObject_MockObject $eventRepository */
        $eventRepository = $this->getMock('JWeiland\\Events2\\Domain\\Repository\\EventRepository', array(), array(), '', false);
        $eventRepository->expects($this->once())->method('searchEvents')->with(
            $this->identicalTo($searchResult)
        )->willReturn($events);
        /** @var \TYPO3\CMS\Fluid\View\TemplateView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock(TemplateView::class, array('assign'), array(), '', false);
        $view->expects($this->once())->method('assign')->with(
            $this->identicalTo('events'),
            $this->identicalTo($events)
        );
        /** @var \JWeiland\Events2\Controller\EventController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $eventController */
        $eventController = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\EventController', array('dummy'));
        $eventController->_set('eventRepository', $eventRepository);
        $eventController->_set('view', $view);

        $eventController->listSearchResultsAction($searchResult);
    }

    /**
     * @test
     */
    public function listMyEventsActionFindEventsAndAssignsThemToView()
    {
        $filter = new Filter();
        $tsfeBackup = $GLOBALS['TSFE'];
        $feUser = new \stdClass();
        $feUser->user = $user = array(
            'uid' => 123,
        );
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->fe_user = $feUser;
        $events = new \SplObjectStorage();
        $events->attach(new Event());

        /** @var \JWeiland\Events2\Domain\Repository\EventRepository|\PHPUnit_Framework_MockObject_MockObject $eventRepository */
        $eventRepository = $this->getMock('JWeiland\\Events2\\Domain\\Repository\\EventRepository', array(), array(), '', false);
        $eventRepository->expects($this->once())->method('findMyEvents')->with(
            $this->identicalTo(123)
        )->willReturn($events);
        /** @var \TYPO3\CMS\Fluid\View\TemplateView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock(TemplateView::class, array('assign'), array(), '', false);
        $view->expects($this->once())->method('assign')->with(
            $this->identicalTo('events'),
            $this->identicalTo($events)
        );
        /** @var \JWeiland\Events2\Controller\EventController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $eventController */
        $eventController = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\EventController', array('dummy'));
        $eventController->_set('eventRepository', $eventRepository);
        $eventController->_set('view', $view);

        $eventController->listMyEventsAction();
        $GLOBALS['TSFE'] = $tsfeBackup;
    }

    /**
     * @test
     *
     * @expectedException \Exception
     */
    public function showActionWithNoDayObjectResultsInException()
    {
        $eventUid = 123;
        $event = new Event();

        /** @var \JWeiland\Events2\Domain\Repository\EventRepository|\PHPUnit_Framework_MockObject_MockObject $eventRepository */
        $eventRepository = $this->getMock('JWeiland\\Events2\\Domain\\Repository\\EventRepository', array(), array(), '', false);
        $eventRepository->expects($this->once())->method('findByIdentifier')->with($this->identicalTo($eventUid))->willReturn($event);
        /** @var \JWeiland\Events2\Domain\Repository\DayRepository|\PHPUnit_Framework_MockObject_MockObject $dayRepository */
        $dayRepository = $this->getMock('JWeiland\\Events2\\Domain\\Repository\\DayRepository', array(), array(), '', false);
        $dayRepository->expects($this->once())->method('getNextDayForEvent')->with($this->identicalTo($event))->willReturn(null);
        $dayRepository->expects($this->once())->method('getLastDayForEvent')->with($this->identicalTo($event))->willReturn(null);
        /** @var \JWeiland\Events2\Controller\EventController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $eventController */
        $eventController = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\EventController', array('dummy'));
        $eventController->_set('eventRepository', $eventRepository);
        $eventController->_set('dayRepository', $dayRepository);

        $eventController->showAction($eventUid);
    }

    /**
     * @test
     */
    public function showActionWithNoDayObjectResultsInEventWithLastDay()
    {
        $eventUid = 123;
        $event = new Event();
        $day = new Day();
        $day->setDay(new \DateTime());

        /** @var \JWeiland\Events2\Domain\Repository\EventRepository|\PHPUnit_Framework_MockObject_MockObject $eventRepository */
        $eventRepository = $this->getMock('JWeiland\\Events2\\Domain\\Repository\\EventRepository', array(), array(), '', false);
        $eventRepository->expects($this->once())->method('findByIdentifier')->with($this->identicalTo($eventUid))->willReturn($event);
        /** @var \JWeiland\Events2\Domain\Repository\DayRepository|\PHPUnit_Framework_MockObject_MockObject $dayRepository */
        $dayRepository = $this->getMock('JWeiland\\Events2\\Domain\\Repository\\DayRepository', array(), array(), '', false);
        $dayRepository->expects($this->once())->method('getNextDayForEvent')->with($this->identicalTo($event))->willReturn(null);
        $dayRepository->expects($this->once())->method('getLastDayForEvent')->with($this->identicalTo($event))->willReturn($day);

        $event->setDay($day);
        /** @var \TYPO3\CMS\Fluid\View\TemplateView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock(TemplateView::class, array('assign'), array(), '', false);
        $view->expects($this->once())->method('assign')->with(
            $this->identicalTo('event'),
            $this->identicalTo($event)
        );
        /** @var \JWeiland\Events2\Controller\EventController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $eventController */
        $eventController = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\EventController', array('dummy'));
        $eventController->_set('eventRepository', $eventRepository);
        $eventController->_set('dayRepository', $dayRepository);
        $eventController->_set('view', $view);

        $eventController->showAction($eventUid);
    }

    /**
     * @test
     */
    public function showActionWithNoDayObjectResultsInEventWithNextDay()
    {
        $eventUid = 123;
        $event = new Event();
        $day = new Day();
        $day->setDay(new \DateTime());

        /** @var \JWeiland\Events2\Domain\Repository\EventRepository|\PHPUnit_Framework_MockObject_MockObject $eventRepository */
        $eventRepository = $this->getMock('JWeiland\\Events2\\Domain\\Repository\\EventRepository', array(), array(), '', false);
        $eventRepository->expects($this->once())->method('findByIdentifier')->with($this->identicalTo($eventUid))->willReturn($event);
        /** @var \JWeiland\Events2\Domain\Repository\DayRepository|\PHPUnit_Framework_MockObject_MockObject $dayRepository */
        $dayRepository = $this->getMock('JWeiland\\Events2\\Domain\\Repository\\DayRepository', array(), array(), '', false);
        $dayRepository->expects($this->once())->method('getNextDayForEvent')->with($this->identicalTo($event))->willReturn($day);
        $dayRepository->expects($this->never())->method('getLastDayForEvent');

        $event->setDay($day);
        /** @var \TYPO3\CMS\Fluid\View\TemplateView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock(TemplateView::class, array('assign'), array(), '', false);
        $view->expects($this->once())->method('assign')->with(
            $this->identicalTo('event'),
            $this->identicalTo($event)
        );

        /** @var \JWeiland\Events2\Controller\EventController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $eventController */
        $eventController = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\EventController', array('dummy'));
        $eventController->_set('eventRepository', $eventRepository);
        $eventController->_set('dayRepository', $dayRepository);
        $eventController->_set('view', $view);

        $eventController->showAction($eventUid);
    }

    /**
     * @test
     *
     * @expectedException \Exception
     */
    public function showActionWithGivenDayResultsInException()
    {
        $eventUid = 123;
        $dayUid = 321;
        $event = new Event();
        $day = new Day();
        $day->setDay(new \DateTime());

        /** @var \JWeiland\Events2\Domain\Repository\EventRepository|\PHPUnit_Framework_MockObject_MockObject $eventRepository */
        $eventRepository = $this->getMock('JWeiland\\Events2\\Domain\\Repository\\EventRepository', array(), array(), '', false);
        $eventRepository->expects($this->once())->method('findByIdentifier')->with($this->identicalTo($eventUid))->willReturn($event);
        /** @var \JWeiland\Events2\Domain\Repository\DayRepository|\PHPUnit_Framework_MockObject_MockObject $dayRepository */
        $dayRepository = $this->getMock('JWeiland\\Events2\\Domain\\Repository\\DayRepository', array(), array(), '', false);
        $dayRepository->expects($this->once())->method('findByIdentifier')->with($this->identicalTo($dayUid))->willReturn(null);
        $dayRepository->expects($this->once())->method('getNextDayForEvent')->with($this->identicalTo($event))->willReturn(null);
        $dayRepository->expects($this->once())->method('getLastDayForEvent')->with($this->identicalTo($event))->willReturn(null);

        /** @var \JWeiland\Events2\Controller\EventController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $eventController */
        $eventController = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\EventController', array('dummy'));
        $eventController->_set('eventRepository', $eventRepository);
        $eventController->_set('dayRepository', $dayRepository);

        $eventController->showAction($eventUid, $dayUid);
    }

    /**
     * @test
     */
    public function showActionWithGivenDayResultsInEventWithDay()
    {
        $eventUid = 123;
        $dayUid = 321;
        $event = new Event();
        $day = new Day();
        $day->setDay(new \DateTime());

        /** @var \JWeiland\Events2\Domain\Repository\EventRepository|\PHPUnit_Framework_MockObject_MockObject $eventRepository */
        $eventRepository = $this->getMock('JWeiland\\Events2\\Domain\\Repository\\EventRepository', array(), array(), '', false);
        $eventRepository->expects($this->once())->method('findByIdentifier')->with($this->identicalTo($eventUid))->willReturn($event);
        /** @var \JWeiland\Events2\Domain\Repository\DayRepository|\PHPUnit_Framework_MockObject_MockObject $dayRepository */
        $dayRepository = $this->getMock('JWeiland\\Events2\\Domain\\Repository\\DayRepository', array(), array(), '', false);
        $dayRepository->expects($this->once())->method('findByIdentifier')->with($this->identicalTo($dayUid))->willReturn($day);
        $dayRepository->expects($this->never())->method('getNextDayForEvent');
        $dayRepository->expects($this->never())->method('getLastDayForEvent');

        $event->setDay($day);
        /** @var \TYPO3\CMS\Fluid\View\TemplateView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock(TemplateView::class, array('assign'), array(), '', false);
        $view->expects($this->once())->method('assign')->with(
            $this->identicalTo('event'),
            $this->identicalTo($event)
        );

        /** @var \JWeiland\Events2\Controller\EventController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $eventController */
        $eventController = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\EventController', array('dummy'));
        $eventController->_set('eventRepository', $eventRepository);
        $eventController->_set('dayRepository', $dayRepository);
        $eventController->_set('view', $view);

        $eventController->showAction($eventUid, $dayUid);
    }

    /**
     * @test
     */
    public function newActionDeletesUploadedFilesOnValidationErrors()
    {
        /** @var \TYPO3\CMS\Core\Resource\FileReference|\PHPUnit_Framework_MockObject_MockObject $fileReference */
        $fileReference = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileReference', array(), array(), '', false);
        $fileReference->expects($this->exactly(2))->method('delete');
        /** @var \JWeiland\Events2\Domain\Model\FileReference|\PHPUnit_Framework_MockObject_MockObject $image1 */
        $image1 = $this->getMock('JWeiland\\Events2\\Domain\\Model\\FileReference', array('getOriginalResource'));
        $image1->expects($this->once())->method('getOriginalResource')->willReturn($fileReference);
        $image1->setTitle('Image1');
        /** @var \JWeiland\Events2\Domain\Model\FileReference|\PHPUnit_Framework_MockObject_MockObject $image2 */
        $image2 = $this->getMock('JWeiland\\Events2\\Domain\\Model\\FileReference', array('getOriginalResource'));
        $image2->expects($this->once())->method('getOriginalResource')->willReturn($fileReference);
        $image2->setTitle('Image2');
        $images = new ObjectStorage();
        $images->attach($image1);
        $images->attach($image2);

        $event = new Event();
        $event->setTitle('TestEvent');
        $event->setImages($images);

        $arguments = array(
            'event' => $event,
        );

        $locations = new \SplObjectStorage();
        $locations->attach(new Location());
        $categories = new \SplObjectStorage();
        $categories->attach(new Category());

        /** @var \TYPO3\CMS\Extbase\Mvc\Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Request', array('dummy'));
        $request->setArguments($arguments);

        /** @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext|\PHPUnit_Framework_MockObject_MockObject $controllerContext */
        $controllerContext = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ControllerContext', array('dummy'));
        $controllerContext->setRequest($request);

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager|\PHPUnit_Framework_MockObject_MockObject $objectManager */
        $objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $objectManager->expects($this->once())->method('get')->with($this->identicalTo('JWeiland\\Events2\\Domain\\Model\\Event'))->willReturn($event);

        /** @var \JWeiland\Events2\Domain\Repository\LocationRepository|\PHPUnit_Framework_MockObject_MockObject $locationRepository */
        $locationRepository = $this->getMock('JWeiland\\Events2\\Domain\\Repository\\LocationRepository', array(), array(), '', false);
        $locationRepository->expects($this->once())->method('findAll')->willReturn($locations);

        /** @var \JWeiland\Events2\Domain\Repository\CategoryRepository|\PHPUnit_Framework_MockObject_MockObject $categoryRepository */
        $categoryRepository = $this->getMock('JWeiland\\Events2\\Domain\\Repository\\CategoryRepository', array(), array(), '', false);
        $categoryRepository->expects($this->once())->method('getCategories')->willReturn($categories);

        /** @var \TYPO3\CMS\Fluid\View\TemplateView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock(TemplateView::class, array('assign'), array(), '', false);
        $view->expects($this->at(0))->method('assign')->with($this->identicalTo('event'), $this->identicalTo($event));
        $view->expects($this->at(1))->method('assign')->with($this->identicalTo('locations'), $this->identicalTo($locations));
        $view->expects($this->at(2))->method('assign')->with($this->identicalTo('selectableCategories'), $this->identicalTo($categories));

        /** @var \JWeiland\Events2\Controller\EventController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $eventController */
        $eventController = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\EventController', array('dummy'));
        $eventController->_set('controllerContext', $controllerContext);
        $eventController->_set('objectManager', $objectManager);
        $eventController->_set('locationRepository', $locationRepository);
        $eventController->_set('categoryRepository', $categoryRepository);
        $eventController->_set('view', $view);

        $eventController->newAction();
    }
}
