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
use JWeiland\Events2\Controller\DayController;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\TemplateView;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class DayControllerTest extends UnitTestCase
{
    /**
     * @var DayController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
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
        $this->subject = $this->getAccessibleMock(DayController::class, ['dummy']);
        $this->dayRepository = $this->getMock(DayRepository::class, [], [], '', false);
        $this->eventRepository = $this->getMock(EventRepository::class, [], [], '', false);
        $this->view = $this->getMock(TemplateView::class, [], [], '', false);
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
    public function showActionWithTimestampCallsAssign()
    {
        $event = 32415;
        $timestamp = 1234567890;

        /** @var Day|\PHPUnit_Framework_MockObject_MockObject $day */
        $day = $this->getMock(Day::class, ['setEvent']);
        $day
            ->expects($this->never())
            ->method('setEvent');

        $this->dayRepository
            ->expects($this->once())
            ->method('findOneByTimestamp')
            ->with(
                $this->equalTo($event),
                $this->equalTo($timestamp)
            )
            ->willReturn($day);

        $this->view
            ->expects($this->once())
            ->method('assign')
            ->with(
                $this->equalTo('day'),
                $this->equalTo($day)
            );

        $this->subject->injectDayRepository($this->dayRepository);
        $this->subject->_set('view', $this->view);

        $this->subject->showAction($event, $timestamp);
    }

    /**
     * @test
     */
    public function showActionWithoutTimestampGeneratesEmptyDay()
    {
        $event = 32415;

        /** @var Event|\PHPUnit_Framework_MockObject_MockObject $day */
        $eventObject = $this->getMock(Event::class);

        /** @var Day|\PHPUnit_Framework_MockObject_MockObject $day */
        $day = $this->getMock(Day::class, ['setEvent']);
        $day
            ->expects($this->once())
            ->method('setEvent')
            ->with($this->equalTo($eventObject))
            ->willReturn(null);

        /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $objectManager */
        $objectManager = $this->getMock(ObjectManager::class, ['get'], [], '', false);
        $objectManager
            ->expects($this->at(1))
            ->method('get')
            ->with('JWeiland\\Events2\\Domain\\Model\\Day')
            ->willReturn($day);

        $this->eventRepository
            ->expects($this->once())
            ->method('findByIdentifier')
            ->with($this->equalTo($event))
            ->willReturn($eventObject);

        $this->dayRepository
            ->expects($this->once())
            ->method('findOneByTimestamp')
            ->with(
                $this->equalTo($event),
                $this->equalTo(0)
            )
            ->willReturn(null);

        $this->view
            ->expects($this->once())
            ->method('assign')
            ->with(
                $this->equalTo('day'),
                $this->equalTo($day)
            );

        $this->subject->injectObjectManager($objectManager);
        $this->subject->injectDayRepository($this->dayRepository);
        $this->subject->injectEventRepository($this->eventRepository);
        $this->subject->_set('view', $this->view);

        $this->subject->showAction($event);
    }

    /**
     * @test
     *
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function showByTimestampWithoutTimestampThrowsException()
    {
        $this->subject->showByTimestampAction();
    }

    /**
     * @test
     */
    public function showByTimestampWithTimestampCallsAssign()
    {
        $timestamp = 1234567890;

        $this->dayRepository
            ->expects($this->once())
            ->method('findByTimestamp')
            ->with($this->equalTo($timestamp))
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

        $this->subject->showByTimestampAction($timestamp);
    }

    /**
     * @test
     */
    public function showByTimestampWithoutTimestampCallsFindByTimestampWith0()
    {
        $this->dayRepository
            ->expects($this->once())
            ->method('findByTimestamp')
            ->with($this->equalTo(0))
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

        $this->subject->showByTimestampAction('abc');
    }
}
