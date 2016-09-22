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
use JWeiland\Events2\Controller\CalendarController;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class CalendarControllerTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Controller\CalendarController
     */
    protected $subject;

    /**
     * @var DatabaseConnection
     */
    protected $dbProphecy;

    public function setUp()
    {
        $this->subject = new CalendarController();
        $this->dbProphecy = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $this->dbProphecy->reveal();
    }

    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function showActionCreateCalendarWithCurrentDate()
    {
        /** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface|\PHPUnit_Framework_MockObject_MockObject $configurationManager */
        $configurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');

        /** @var \TYPO3\CMS\Fluid\View\TemplateView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock('TYPO3\\CMS\\Fluid\\View\\TemplateView', array('assign'));
        $view->expects($this->at(0))->method('assign')->with($this->equalTo('storagePids'));
        $view->expects($this->at(1))->method('assign')->with($this->equalTo('pidOfListPage'));
        $view->expects($this->at(2))->method('assign')->with($this->equalTo('siteUrl'));
        $view->expects($this->at(3))->method('assign')->with($this->equalTo('siteId'));

        /** @var \JWeiland\Events2\Controller\CalendarController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\CalendarController', array('getMonthAndYearFromUserSession', 'getDayFromUrl'));
        $subject->expects($this->once())->method('getMonthAndYearFromUserSession')->will($this->returnValue(array()));
        $subject->expects($this->once())->method('getDayFromUrl')->will($this->returnValue(null));
        $subject->_set('view', $view);
        $subject->injectConfigurationManager($configurationManager);
        $subject->showAction();
    }
}
