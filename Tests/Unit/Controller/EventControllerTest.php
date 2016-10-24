<?php

namespace JWeiland\Events2\Tests\Unit\Controller;

/*
 * This file is part of the TYPO3 CMS project.
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
use JWeiland\Events2\Controller\EventController;
use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Search;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
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
     * @var EventController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
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
     * @var TemplateView|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $view;
    
    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(EventController::class, array('dummy'));
        $this->dayRepository = $this->getMock(DayRepository::class, array(), array(), '', false);
        $this->eventRepository = $this->getMock(EventRepository::class, array(), array(), '', false);
        $this->view = $this->getMock(TemplateView::class, array(), array(), '', false);
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
        
        $this->dayRepository
            ->expects($this->once())
            ->method('findEvents')
            ->with(
                $this->equalTo('list'),
                $this->equalTo($filter)
            )
            ->willReturn(array());
        $this->view
            ->expects($this->at(0))
            ->method('assign')
            ->with(
                $this->equalTo('filter'),
                $this->equalTo($filter)
            );
        $this->view
            ->expects($this->at(1))
            ->method('assign')
            ->with(
                $this->equalTo('days'),
                $this->equalTo(array())
            );
    
        $this->subject->injectDayRepository($this->dayRepository);
        $this->subject->_set('view', $this->view);

        $this->subject->listAction($filter);
    }

    /**
     * @test
     */
    public function listLatestActionFindEventsAndAssignsThemToView()
    {
        $filter = new Filter();
    
        $this->dayRepository
            ->expects($this->once())
            ->method('findEvents')
            ->with(
                $this->equalTo('latest'),
                $this->equalTo($filter)
            )
            ->willReturn(array());
        $this->view
            ->expects($this->at(0))
            ->method('assign')
            ->with(
                $this->equalTo('filter'),
                $this->equalTo($filter)
            );
        $this->view
            ->expects($this->at(1))
            ->method('assign')
            ->with(
                $this->equalTo('days'),
                $this->equalTo(array())
            );
    
        $this->subject->injectDayRepository($this->dayRepository);
        $this->subject->_set('view', $this->view);
    
        $this->subject->listLatestAction($filter);
    }

    /**
     * @test
     */
    public function listTodayActionFindEventsAndAssignsThemToView()
    {
        $filter = new Filter();
    
        $this->dayRepository
            ->expects($this->once())
            ->method('findEvents')
            ->with(
                $this->equalTo('today'),
                $this->equalTo($filter)
            )
            ->willReturn(array());
        $this->view
            ->expects($this->at(0))
            ->method('assign')
            ->with(
                $this->equalTo('filter'),
                $this->equalTo($filter)
            );
        $this->view
            ->expects($this->at(1))
            ->method('assign')
            ->with(
                $this->equalTo('days'),
                $this->equalTo(array())
            );
    
        $this->subject->injectDayRepository($this->dayRepository);
        $this->subject->_set('view', $this->view);
    
        $this->subject->listTodayAction($filter);
    }

    /**
     * @test
     */
    public function listThisWeekActionFindEventsAndAssignsThemToView()
    {
        $filter = new Filter();
    
        $this->dayRepository
            ->expects($this->once())
            ->method('findEvents')
            ->with(
                $this->equalTo('thisWeek'),
                $this->equalTo($filter)
            )
            ->willReturn(array());
        $this->view
            ->expects($this->at(0))
            ->method('assign')
            ->with(
                $this->equalTo('filter'),
                $this->equalTo($filter)
            );
        $this->view
            ->expects($this->at(1))
            ->method('assign')
            ->with(
                $this->equalTo('days'),
                $this->equalTo(array())
            );
    
        $this->subject->injectDayRepository($this->dayRepository);
        $this->subject->_set('view', $this->view);
    
        $this->subject->listThisWeekAction($filter);
    }

    /**
     * @test
     */
    public function listRangeActionFindEventsAndAssignsThemToView()
    {
        $filter = new Filter();
    
        $this->dayRepository
            ->expects($this->once())
            ->method('findEvents')
            ->with(
                $this->equalTo('range'),
                $this->equalTo($filter)
            )
            ->willReturn(array());
        $this->view
            ->expects($this->at(0))
            ->method('assign')
            ->with(
                $this->equalTo('filter'),
                $this->equalTo($filter)
            );
        $this->view
            ->expects($this->at(1))
            ->method('assign')
            ->with(
                $this->equalTo('days'),
                $this->equalTo(array())
            );
    
        $this->subject->injectDayRepository($this->dayRepository);
        $this->subject->_set('view', $this->view);
    
        $this->subject->listRangeAction($filter);
    }

    /**
     * @test
     */
    public function listSearchResultsActionSearchesForEventsAndAssignsThemToView()
    {
        $search = new Search();
        
        $this->dayRepository
            ->expects($this->once())
            ->method('searchEvents')
            ->with($this->equalTo($search))
            ->willReturn(array());
        
        $this->view
            ->expects($this->once())
            ->method('assign')
            ->with(
                $this->equalTo('days'),
                $this->equalTo(array())
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
        $tsfeBackup = $GLOBALS['TSFE'];
        $feUser = new \stdClass();
        $feUser->user = $user = array(
            'uid' => 123,
        );
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->fe_user = $feUser;
        
        $this->eventRepository
            ->expects($this->once())
            ->method('findMyEvents')
            ->with($this->equalTo(123))
            ->willReturn(array());
        
        $this->view
            ->expects($this->once())
            ->method('assign')
            ->with(
                $this->equalTo('events'),
                $this->equalTo(array())
            );
        
        $this->subject->injectEventRepository($this->eventRepository);
        $this->subject->_set('view', $this->view);
        
        $this->subject->listMyEventsAction();
        $GLOBALS['TSFE'] = $tsfeBackup;
    }

    /**
     * @test
     */
    public function newActionDeletesUploadedFilesOnValidationErrors()
    {
        /** @var FileReference|\PHPUnit_Framework_MockObject_MockObject $fileReference */
        $fileReference = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileReference', array(), array(), '', false);
        $fileReference->expects($this->exactly(2))->method('delete');
        /** @var FileReference|\PHPUnit_Framework_MockObject_MockObject $image1 */
        $image1 = $this->getMock('JWeiland\\Events2\\Domain\\Model\\FileReference', array('getOriginalResource'));
        $image1->expects($this->once())->method('getOriginalResource')->willReturn($fileReference);
        /** @var FileReference|\PHPUnit_Framework_MockObject_MockObject $image2 */
        $image2 = $this->getMock('JWeiland\\Events2\\Domain\\Model\\FileReference', array('getOriginalResource'));
        $image2->expects($this->once())->method('getOriginalResource')->willReturn($fileReference);
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
