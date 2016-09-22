<?php

namespace JWeiland\Events2\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Stefan Froemken <projects@jweiland.net>, jweiland.net
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
use JWeiland\Events2\Domain\Model\Day;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CalendarController extends ActionController
{
    /**
     * @var \TYPO3\CMS\Core\Page\PageRenderer
     */
    protected $pageRenderer;

    /**
     * @var \JWeiland\Events2\Domain\Repository\DayRepository
     */
    protected $dayRepository;

    /**
     * inject page renderer.
     *
     * @param \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer
     */
    public function injectPageRenderer(\TYPO3\CMS\Core\Page\PageRenderer $pageRenderer)
    {
        $this->pageRenderer = $pageRenderer;
    }

    /**
     * inject day repository.
     *
     * @param \JWeiland\Events2\Domain\Repository\DayRepository $dayRepository
     */
    public function injectDayRepository(\JWeiland\Events2\Domain\Repository\DayRepository $dayRepository)
    {
        $this->dayRepository = $dayRepository;
    }

    /**
     * action show.
     */
    public function showAction()
    {
        $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $this->view->assign('storagePids', $frameworkConfiguration['persistence']['storagePid']);
        $this->view->assign('pidOfListPage', $this->settings['pidOfListPage'] ?: $GLOBALS['TSFE']->id);
        $this->view->assign('siteUrl', GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
        $this->view->assign('siteId', $GLOBALS['TSFE']->id);

        // get month and year from session
        $monthAndYear = $this->getMonthAndYearFromUserSession();
        $day = $this->getDayFromUrl();

        // move calendar to month and year if given
        if ($day instanceof Day) {
            // if there is a day given in URL
            $this->view->assign('day', $day->getDay()->format('d'));
            $this->view->assign('month', $day->getDay()->format('m'));
            $this->view->assign('year', $day->getDay()->format('Y'));
        } elseif (is_array($monthAndYear) && count($monthAndYear)) {
            // if there is a session found with given month and year
            $this->view->assign('day', '01');
            $this->view->assign('month', $monthAndYear['month']);
            $this->view->assign('year', $monthAndYear['year']);
        } else {
            // if nothing found, set to current day
            $this->view->assign('day', date('d'));
            $this->view->assign('month', date('m'));
            $this->view->assign('year', date('Y'));
        }
    }

    /**
     * selected month and year was saved in user session by eID script
     * this method returns these values to set calendar to this date
     * Further we need this method for UnitTests (getMock).
     *
     * @return array contains month and year OR empty array
     */
    protected function getMonthAndYearFromUserSession()
    {
        $monthAndYear = $GLOBALS['TSFE']->fe_user->getKey('ses', 'events2MonthAndYearForCalendar');
        if (!is_array($monthAndYear)) {
            $monthAndYear = array();
        }

        return $monthAndYear;
    }

    /**
     * get day from url
     * we can't set $day as parameter in showAction($day), because this action is of controller Calendar and not Event.
     *
     * @return \JWeiland\Events2\Domain\Model\Day|null
     */
    protected function getDayFromUrl()
    {
        $day = null;
        // get parameters of event-plugin-namespace
        $pluginParameters = GeneralUtility::_GPmerged('tx_events2_events');
        if (
            is_array($pluginParameters) &&
            array_key_exists('day', $pluginParameters) &&
            MathUtility::canBeInterpretedAsInteger($pluginParameters['day'])
        ) {
            $day = $this->dayRepository->findByIdentifier((int) $pluginParameters['day']);
        }

        return $day;
    }
}
