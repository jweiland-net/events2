<?php
namespace JWeiland\Events2\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Stefan Froemken <sfroemken@jweiland.net>, jweiland.net
 *  
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @package events2
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CalendarController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var \TYPO3\CMS\Core\Page\PageRenderer
	 * @inject
	 */
	protected $pageRenderer;





	/**
	 * initialize show action
	 *
	 * @return void
	 */
	public function initializeAction() {
		// forceOnTop makes $newScriptTag . LF . $jsFiles. So we have to add JS files in reverse order
		//$this->pageRenderer->addJsLibrary('maps2JQuery40', ExtensionManagementUtility::siteRelPath('events2') . 'Resources/Public/JavaScript/Calendar.js', 'text/javascript', FALSE, TRUE, '', TRUE);
		if ($this->settings['includeJQueryUiLibrary']) {
			$this->pageRenderer->addJsLibrary('maps2JQuery30', ExtensionManagementUtility::siteRelPath('events2') . 'Resources/Public/JavaScript/jquery.ui.datepicker-de.js', 'text/javascript', FALSE, TRUE, '', TRUE);
			$this->pageRenderer->addJsLibrary('maps2JQuery20', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js', 'text/javascript', FALSE, TRUE, '', TRUE);
		}
		if ($this->settings['includeJQueryLibrary']) {
			$this->pageRenderer->addJsLibrary('maps2JQuery10', '//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js', 'text/javascript', FALSE, TRUE, '', TRUE);
		}
		if ($this->settings['includeJQueryUiCss']) {
			$this->pageRenderer->addCssFile(ExtensionManagementUtility::siteRelPath('events2') . 'Resources/Public/Css/pforzheim/jquery-ui-1.10.3.custom.css', 'stylesheet', 'all', FALSE);
		}
	}

	/**
	 * action show
	 *
	 * @return void
	 */
	public function showAction() {
		$this->view->assign('siteUrl', GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
		$this->view->assign('siteId', $GLOBALS['TSFE']->id);

		// move calendar to month and year if given
		$monthAndYear = $GLOBALS['TSFE']->fe_user->getKey('ses', 'events2MonthAndYearForCalendar');
		if (is_array($monthAndYear) && count($monthAndYear)) {
			$this->view->assign('day', '01');
			$this->view->assign('month', $monthAndYear['month']);
			$this->view->assign('year', $monthAndYear['year']);
		} else {
			$this->view->assign('day', date('d'));
			$this->view->assign('month', date('m'));
			$this->view->assign('year', date('Y'));
		}
	}

}