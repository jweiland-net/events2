<?php
namespace JWeiland\Events2\Tests\Unit\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Stefan Froemken <projects@jweiland.net>, jweiland.net
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
use JWeiland\Events2\Controller\CalendarController;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case.
 *
 * @subpackage events2
 * @author Stefan Froemken <projects@jweiland.net>
 */
class CalendarControllerTest extends UnitTestCase {

	/**
	 * @var \JWeiland\Events2\Controller\CalendarController
	 */
	protected $subject;

	public function setUp() {
		$this->subject = new CalendarController();
	}

	public function tearDown() {
		unset($this->subject);
	}

	/**
	 * @test
	 */
	public function initializeActionAddsJavaScriptForJQueryUi() {
		$settings = array(
			'includeJQueryUiLibrary' => 1,
			'includeJQueryLibrary' => 0,
			'includeJQueryUiCss' => 0,
		);
		/** @var \TYPO3\CMS\Core\Page\PageRenderer|\PHPUnit_Framework_MockObject_MockObject $pageRenderer */
		$pageRenderer = $this->getMock('TYPO3\\CMS\\Core\\Page\\PageRenderer', array('addJsLibrary'));
		$pageRenderer->expects($this->at(0))->method('addJsLibrary')->with($this->equalTo('maps2JQuery30'));
		$pageRenderer->expects($this->at(1))->method('addJsLibrary')->with($this->equalTo('maps2JQuery20'));

		/** @var \JWeiland\Events2\Controller\CalendarController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\CalendarController', array('dummy'));
		$subject->_set('settings', $settings);
		$subject->injectPageRenderer($pageRenderer);
		$subject->initializeAction();
	}

	/**
	 * @test
	 */
	public function initializeActionAddsJavaScriptForJQuery() {
		$settings = array(
			'includeJQueryUiLibrary' => 0,
			'includeJQueryLibrary' => 1,
			'includeJQueryUiCss' => 0,
		);
		/** @var \TYPO3\CMS\Core\Page\PageRenderer|\PHPUnit_Framework_MockObject_MockObject $pageRenderer */
		$pageRenderer = $this->getMock('TYPO3\\CMS\\Core\\Page\\PageRenderer', array('addJsLibrary'));
		$pageRenderer->expects($this->at(0))->method('addJsLibrary')->with($this->equalTo('maps2JQuery10'));

		/** @var \JWeiland\Events2\Controller\CalendarController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\CalendarController', array('dummy'));
		$subject->_set('settings', $settings);
		$subject->injectPageRenderer($pageRenderer);
		$subject->initializeAction();
	}

	/**
	 * @test
	 */
	public function initializeActionAddsCssForJQueryUi() {
		$settings = array(
			'includeJQueryUiLibrary' => 0,
			'includeJQueryLibrary' => 0,
			'includeJQueryUiCss' => 1,
		);
		/** @var \TYPO3\CMS\Core\Page\PageRenderer|\PHPUnit_Framework_MockObject_MockObject $pageRenderer */
		$pageRenderer = $this->getMock('TYPO3\\CMS\\Core\\Page\\PageRenderer', array('addCssFile'));
		$pageRenderer->expects($this->at(0))->method('addCssFile')->with($this->stringContains('jquery-ui'));

		/** @var \JWeiland\Events2\Controller\CalendarController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('JWeiland\\Events2\\Controller\\CalendarController', array('dummy'));
		$subject->_set('settings', $settings);
		$subject->injectPageRenderer($pageRenderer);
		$subject->initializeAction();
	}

	/**
	 * @test
	 */
	public function showActionCreateCalendarWithCurrentDate() {
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
		$subject->expects($this->once())->method('getDayFromUrl')->will($this->returnValue(NULL));
		$subject->_set('view', $view);
		$subject->injectConfigurationManager($configurationManager);
		$subject->showAction();
	}

}