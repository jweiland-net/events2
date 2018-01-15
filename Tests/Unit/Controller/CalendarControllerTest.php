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
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Repository\DayRepository;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class CalendarControllerTest extends UnitTestCase
{
    /**
     * @var CalendarController|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface
     */
    protected $subject;

    /**
     * @var PageRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $pageRenderer;

    /**
     * @var DayRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dayRepository;

    /**
     * @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configurationManager;

    /**
     * @var TypoScriptFrontendController|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $typoScriptFrontendController;

    /**
     * @var FrontendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendUserAuthentication;

    /**
     * @var TemplateView|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $view;

    /**
     * @var DatabaseConnection|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $dbProphecy;

    /**
     * @var array
     */
    protected $settings = [
        'pidOfListPage' => 123
    ];

    public function setUp()
    {
        $this->dbProphecy = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $this->dbProphecy->reveal();

        $this->pageRenderer = $this->getMockBuilder(PageRenderer::class)->getMock();
        $this->dayRepository = $this
            ->getMockBuilder(DayRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configurationManager = $this->getMockBuilder(ConfigurationManager::class)->getMock();
        $this->typoScriptFrontendController = $this
            ->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->frontendUserAuthentication = $this->getMockBuilder(FrontendUserAuthentication::class)->getMock();
        $this->view = $this
            ->getMockBuilder(TemplateView::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->typoScriptFrontendController->fe_user = $this->frontendUserAuthentication;

        $this->subject = $this->getAccessibleMock(
            CalendarController::class,
            [
                'getTypo3SiteUrl',
                'getTypoScriptFrontendController',
                'getDayFromUrl'
            ]
        );
        $this->subject->injectPageRenderer($this->pageRenderer);
        $this->subject->injectDayRepository($this->dayRepository);
        $this->subject->injectConfigurationManager($this->configurationManager);
        $this->subject->_set('view', $this->view);
        $this->subject->_set('settings', $this->settings);
    }

    public function tearDown()
    {
        unset($this->dbProphecy);
        unset($this->pageRenderer);
        unset($this->dayRepository);
        unset($this->configurationManager);
        unset($this->view);
    }

    /**
     * @test
     */
    public function showActionWithoutDayInformationWillCreateCalendarWithCurrentDate()
    {
        $persistenceSettings = [
            'persistence' => [
                'storagePid' => 321
            ]
        ];
        $expectedParameter = [
            'environment' => [
                'settings' => $this->settings,
                'storagePids' => 321,
                'pidOfListPage' => 123,
                'siteUrl' => 'http://www.example.com',
                'siteId' => null,
                'day' => date('d'),
                'month' => date('m'),
                'year' => date('Y')
            ]
        ];

        $this->configurationManager
            ->expects($this->once())
            ->method('getConfiguration')
            ->with(
                $this->equalTo(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK))
            ->willReturn($persistenceSettings);

        $this->frontendUserAuthentication
            ->expects($this->once())
            ->method('getKey')
            ->with(
                $this->equalTo('ses'),
                $this->equalTo('events2MonthAndYearForCalendar')
            )
            ->willReturn(null);

        $this->view
            ->expects($this->once())
            ->method('assignMultiple')
            ->with($expectedParameter);

        $this->subject
            ->expects($this->once())
            ->method('getTypo3SiteUrl')
            ->willReturn('http://www.example.com');
        $this->subject
            ->expects($this->once())
            ->method('getTypoScriptFrontendController')
            ->willReturn($this->typoScriptFrontendController);

        $this->subject->showAction();
    }

    /**
     * @test
     */
    public function showActionWithDayFromUrlWillCreateCalendar()
    {
        $day = new Day();
        $day->setDay(\DateTime::createFromFormat('d.m.Y H:i:s', '06.10.2016 00:00:00'));

        $persistenceSettings = [
            'persistence' => [
                'storagePid' => 321
            ]
        ];
        $expectedParameter = [
            'environment' => [
                'settings' => $this->settings,
                'storagePids' => 321,
                'pidOfListPage' => 123,
                'siteUrl' => 'http://www.example.com',
                'siteId' => null,
                'day' => '06',
                'month' => '10',
                'year' => '2016'
            ]
        ];

        $this->configurationManager
            ->expects($this->once())
            ->method('getConfiguration')
            ->with(
                $this->equalTo(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK))
            ->willReturn($persistenceSettings);

        $this->view
            ->expects($this->once())
            ->method('assignMultiple')
            ->with($expectedParameter);

        $this->subject
            ->expects($this->once())
            ->method('getTypo3SiteUrl')
            ->willReturn('http://www.example.com');
        $this->subject
            ->expects($this->once())
            ->method('getTypoScriptFrontendController')
            ->willReturn($this->typoScriptFrontendController);
        $this->subject
            ->expects($this->once())
            ->method('getDayFromUrl')
            ->willReturn($day);

        $this->subject->showAction();
    }

    /**
     * @test
     */
    public function showActionWithDayFromSessionWillCreateCalendar()
    {
        $persistenceSettings = [
            'persistence' => [
                'storagePid' => 321
            ]
        ];
        $expectedParameter = [
            'environment' => [
                'settings' => $this->settings,
                'storagePids' => 321,
                'pidOfListPage' => 123,
                'siteUrl' => 'http://www.example.com',
                'siteId' => null,
                'day' => '01',
                'month' => '03',
                'year' => '2016'
            ]
        ];

        $this->configurationManager
            ->expects($this->once())
            ->method('getConfiguration')
            ->with(
                $this->equalTo(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK))
            ->willReturn($persistenceSettings);

        $this->frontendUserAuthentication
            ->expects($this->once())
            ->method('getKey')
            ->with(
                $this->equalTo('ses'),
                $this->equalTo('events2MonthAndYearForCalendar')
            )
            ->willReturn([
                'month' => '03',
                'year' => '2016'
            ]);

        $this->view
            ->expects($this->once())
            ->method('assignMultiple')
            ->with($expectedParameter);

        $this->subject
            ->expects($this->once())
            ->method('getTypo3SiteUrl')
            ->willReturn('http://www.example.com');
        $this->subject
            ->expects($this->once())
            ->method('getTypoScriptFrontendController')
            ->willReturn($this->typoScriptFrontendController);

        $this->subject->showAction();
    }
}
