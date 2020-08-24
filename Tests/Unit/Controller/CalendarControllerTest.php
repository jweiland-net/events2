<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Controller;

use JWeiland\Events2\Controller\CalendarController;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Repository\DayRepository;
use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 */
class CalendarControllerTest extends UnitTestCase
{
    /**
     * @var CalendarController|\PHPUnit_Framework_MockObject_MockObject|AccessibleMockObjectInterface
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
     * @var ConfigurationManager|\Prophecy\Prophecy\ObjectProphecy
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
     * @var array
     */
    protected $settings = [
        'pidOfListPage' => 123
    ];

    public function setUp()
    {
        $this->pageRenderer = $this->getMockBuilder(PageRenderer::class)->getMock();
        $this->dayRepository = $this
            ->getMockBuilder(DayRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configurationManager = $this->prophesize(ConfigurationManager::class);
        $this->configurationManager
            ->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
                'events2',
                'events2_event'
            )
            ->shouldBeCalled()
            ->willReturn([
                'settings' => $this->settings
            ]);
        $this->configurationManager
            ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS)
            ->shouldBeCalled()
            ->willReturn($this->settings);
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
        $this->subject->_set('pageRenderer', $this->pageRenderer);
        $this->subject->_set('view', $this->view);
        $this->subject->_set('settings', $this->settings);
        $this->subject->injectDayRepository($this->dayRepository);
        $this->subject->injectConfigurationManager($this->configurationManager->reveal());
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
            ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK)
            ->shouldBeCalled()
            ->willReturn($persistenceSettings);

        $this->frontendUserAuthentication
            ->expects(self::once())
            ->method('getKey')
            ->with(
                self::equalTo('ses'),
                self::equalTo('events2MonthAndYearForCalendar')
            )
            ->willReturn(null);

        $this->view
            ->expects(self::once())
            ->method('assignMultiple')
            ->with($expectedParameter);

        $this->subject
            ->expects(self::once())
            ->method('getTypo3SiteUrl')
            ->willReturn('http://www.example.com');
        $this->subject
            ->expects(self::once())
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
            ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK)
            ->shouldBeCalled($persistenceSettings)
            ->willReturn($persistenceSettings);

        $this->view
            ->expects(self::once())
            ->method('assignMultiple')
            ->with($expectedParameter);

        $this->subject
            ->expects(self::once())
            ->method('getTypo3SiteUrl')
            ->willReturn('http://www.example.com');
        $this->subject
            ->expects(self::once())
            ->method('getTypoScriptFrontendController')
            ->willReturn($this->typoScriptFrontendController);
        $this->subject
            ->expects(self::once())
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
            ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK)
            ->shouldBeCalled()
            ->willReturn($persistenceSettings);

        $this->frontendUserAuthentication
            ->expects(self::once())
            ->method('getKey')
            ->with(
                self::equalTo('ses'),
                self::equalTo('events2MonthAndYearForCalendar')
            )
            ->willReturn([
                'month' => '03',
                'year' => '2016'
            ]);

        $this->view
            ->expects(self::once())
            ->method('assignMultiple')
            ->with($expectedParameter);

        $this->subject
            ->expects(self::once())
            ->method('getTypo3SiteUrl')
            ->willReturn('http://www.example.com');
        $this->subject
            ->expects(self::once())
            ->method('getTypoScriptFrontendController')
            ->willReturn($this->typoScriptFrontendController);

        $this->subject->showAction();
    }
}
