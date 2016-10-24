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
use JWeiland\Events2\Domain\Model\Day;
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
     * @var DatabaseConnection
     */
    protected $dbProphecy;

    public function setUp()
    {
        $this->dbProphecy = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $this->dbProphecy->reveal();
    }

    public function tearDown()
    {
    }

    /**
     * @test
     */
    public function showActionWithNoDayInformationWillCreateCalendarWithCurrentDate()
    {
        $settings = array(
            'pidOfListPage' => 123
        );
        $persistenceSettings = array(
            'persistence' => array(
                'storagePid' => 321
            )
        );
        $expectedParameter = array(
            'environment' => array(
                'settings' => $settings,
                'storagePids' => 321,
                'pidOfListPage' => 123,
                'siteUrl' => '',
                'siteId' => null,
                'day' => date('d'),
                'month' => date('m'),
                'year' => date('Y')
            )
        );
        
        /** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface|\PHPUnit_Framework_MockObject_MockObject $configurationManager */
        $configurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $configurationManager->expects($this->at(0))->method('getConfiguration')->willReturn($settings);
        $configurationManager->expects($this->at(1))->method('getConfiguration')->willReturn($persistenceSettings);

        /** @var \TYPO3\CMS\Fluid\View\TemplateView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock('TYPO3\\CMS\\Fluid\\View\\TemplateView');
        $view
            ->expects($this->once())
            ->method('assignMultiple')
            ->with($expectedParameter);

        /** @var \JWeiland\Events2\Controller\CalendarController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\CalendarController', array('getMonthAndYearFromUserSession', 'getDayFromUrl', 'getTypo3SiteUrl'));
        $subject->expects($this->once())->method('getMonthAndYearFromUserSession')->willReturn(array());
        $subject->expects($this->once())->method('getDayFromUrl')->willReturn(null);
        $subject->expects($this->once())->method('getTypo3SiteUrl')->willReturn('');
        $subject->_set('view', $view);
        $subject->injectConfigurationManager($configurationManager);
        $subject->showAction();
    }
    
    /**
     * @test
     */
    public function showActionWithDayFromUrlWillCreateCalendar()
    {
        $settings = array(
            'pidOfListPage' => 123
        );
        $persistenceSettings = array(
            'persistence' => array(
                'storagePid' => 321
            )
        );
        $expectedParameter = array(
            'environment' => array(
                'settings' => $settings,
                'storagePids' => 321,
                'pidOfListPage' => 123,
                'siteUrl' => '',
                'siteId' => null,
                'day' => '26',
                'month' => '05',
                'year' => '2014'
            )
        );
        
        /** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface|\PHPUnit_Framework_MockObject_MockObject $configurationManager */
        $configurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $configurationManager->expects($this->at(0))->method('getConfiguration')->willReturn($settings);
        $configurationManager->expects($this->at(1))->method('getConfiguration')->willReturn($persistenceSettings);
        
        /** @var \TYPO3\CMS\Fluid\View\TemplateView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock('TYPO3\\CMS\\Fluid\\View\\TemplateView');
        $view
            ->expects($this->once())
            ->method('assignMultiple')
            ->with($expectedParameter);
        
        $day = new Day();
        $day->setDay(\DateTime::createFromFormat('d.m.Y', '26.05.2014'));
        
        /** @var \JWeiland\Events2\Controller\CalendarController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\CalendarController', array('getMonthAndYearFromUserSession', 'getDayFromUrl', 'getTypo3SiteUrl'));
        $subject->expects($this->once())->method('getMonthAndYearFromUserSession')->willReturn(array());
        $subject->expects($this->once())->method('getDayFromUrl')->willReturn($day);
        $subject->expects($this->once())->method('getTypo3SiteUrl')->willReturn('');
        $subject->_set('view', $view);
        $subject->injectConfigurationManager($configurationManager);
        $subject->showAction();
    }
    
    /**
     * @test
     */
    public function showActionDayFromSessionWillCreateCalendar()
    {
        $settings = array(
            'pidOfListPage' => 123
        );
        $persistenceSettings = array(
            'persistence' => array(
                'storagePid' => 321
            )
        );
        $expectedParameter = array(
            'environment' => array(
                'settings' => $settings,
                'storagePids' => 321,
                'pidOfListPage' => 123,
                'siteUrl' => '',
                'siteId' => null,
                'day' => '01',
                'month' => '04',
                'year' => '2015'
            )
        );
        
        /** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface|\PHPUnit_Framework_MockObject_MockObject $configurationManager */
        $configurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $configurationManager->expects($this->at(0))->method('getConfiguration')->willReturn($settings);
        $configurationManager->expects($this->at(1))->method('getConfiguration')->willReturn($persistenceSettings);
        
        /** @var \TYPO3\CMS\Fluid\View\TemplateView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMock('TYPO3\\CMS\\Fluid\\View\\TemplateView');
        $view
            ->expects($this->once())
            ->method('assignMultiple')
            ->with($expectedParameter);
        
        /** @var \JWeiland\Events2\Controller\CalendarController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\CalendarController', array('getMonthAndYearFromUserSession', 'getDayFromUrl', 'getTypo3SiteUrl'));
        $subject->expects($this->once())->method('getMonthAndYearFromUserSession')->willReturn(array(
            'month' => 4,
            'year' => 2015
        ));
        $subject->expects($this->once())->method('getDayFromUrl')->willReturn(null);
        $subject->expects($this->once())->method('getTypo3SiteUrl')->willReturn('');
        $subject->_set('view', $view);
        $subject->injectConfigurationManager($configurationManager);
        $subject->showAction();
    }
}
