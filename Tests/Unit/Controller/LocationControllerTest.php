<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Controller;

use JWeiland\Events2\Controller\LocationController;
use JWeiland\Events2\Domain\Model\Location;
use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Fluid\View\TemplateView;

/**
 * Test case.
 */
class LocationControllerTest extends UnitTestCase
{
    /**
     * @var LocationController|\PHPUnit_Framework_MockObject_MockObject|AccessibleMockObjectInterface
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
        $this->view = $this
            ->getMockBuilder(TemplateView::class)
            ->disableOriginalConstructor()
            ->getMock();

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
            ->expects(self::once())
            ->method('assign')
            ->with(
                self::equalTo('location'),
                self::equalTo($location)
            );

        $this->subject->showAction($location);
    }
}
