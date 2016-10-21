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
use JWeiland\Events2\Domain\Repository\DayRepository;
use TYPO3\CMS\Core\Tests\UnitTestCase;
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
     * @var TemplateView|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $view;
    
    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(DayController::class, array('dummy'));
        $this->dayRepository = $this->getMock(DayRepository::class, array(), array(), '', false);
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
