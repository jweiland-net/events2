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
use JWeiland\Events2\Controller\LocationController;
use JWeiland\Events2\Domain\Model\Location;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Fluid\View\TemplateView;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class LocationControllerTest extends UnitTestCase
{
    /**
     * @var LocationController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $subject;

    /**
     * @var TemplateView|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $view;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->view = $this->getMock(TemplateView::class, [], [], '', false);

        $this->subject = $this->getAccessibleMock(LocationController::class, ['dummy']);
        $this->subject->_set('view', $this->view);
    }

    /**
     * taer down.
     */
    public function tearDown()
    {
        unset($this->subject);
        unset($this->view);
    }

    /**
     * @test
     */
    public function showActionAssignsLocationToView()
    {
        $location = new Location();

        $this->view
            ->expects($this->once())
            ->method('assign')
            ->with(
                $this->equalTo('location'),
                $this->equalTo($location)
            );

        $this->subject->showAction($location);
    }
}
