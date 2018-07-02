<?php

namespace JWeiland\Events2\Controller;

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
use JWeiland\Events2\Domain\Repository\DayRepository;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CalendarController extends AbstractController
{
    /**
     * @var \TYPO3\CMS\Core\Page\PageRenderer
     */
    protected $pageRenderer;

    /**
     * inject page renderer.
     *
     * @param PageRenderer $pageRenderer
     */
    public function injectPageRenderer(PageRenderer $pageRenderer)
    {
        $this->pageRenderer = $pageRenderer;
    }

    /**
     * action show.
     */
    public function showAction()
    {
        $placeHolders = $this->getEnvironmentPlaceholders();

        // get month and year from session
        $monthAndYear = $this->getMonthAndYearFromUserSession();
        $day = $this->getDayFromUrl();

        // move calendar to month and year if given
        if ($day instanceof Day) {
            // if there is a day given in URL
            $placeHolders['environment']['day'] = $day->getDay()->format('d');
            $placeHolders['environment']['month'] = $day->getDay()->format('m');
            $placeHolders['environment']['year'] = $day->getDay()->format('Y');
        } elseif (is_array($monthAndYear) && count($monthAndYear)) {
            // if there is a session found with given month and year
            $placeHolders['environment']['day'] = '01';
            $placeHolders['environment']['month'] = (string)$monthAndYear['month'];
            $placeHolders['environment']['year'] = (string)$monthAndYear['year'];
        } else {
            // if nothing found, set to current day
            $placeHolders['environment']['day'] = date('d');
            $placeHolders['environment']['month'] = date('m');
            $placeHolders['environment']['year'] = date('Y');
        }
        $this->view->assignMultiple($placeHolders);
    }

    /**
     * Get environment placeholders
     *
     * @return array
     */
    protected function getEnvironmentPlaceholders()
    {
        $placeHolders = [];
        $placeHolders['environment'] = [];
        $placeHolders['environment']['settings'] = $this->settings;
        $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $placeHolders['environment']['storagePids'] = $frameworkConfiguration['persistence']['storagePid'];
        $placeHolders['environment']['pidOfListPage'] = $this->settings['pidOfListPage'] ?: $GLOBALS['TSFE']->id;
        $placeHolders['environment']['siteUrl'] = $this->getTypo3SiteUrl();
        $placeHolders['environment']['siteId'] = $GLOBALS['TSFE']->id;

        return $placeHolders;
    }

    /**
     * Get TYPO3 SiteUrl
     *
     * @return string
     */
    protected function getTypo3SiteUrl()
    {
        return GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
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
        $monthAndYear = $this->getTypoScriptFrontendController()->fe_user->getKey(
            'ses',
            'events2MonthAndYearForCalendar'
        );
        if (!is_array($monthAndYear)) {
            $monthAndYear = [];
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
            /** @var Day $day */
            $day = $this->dayRepository->findByIdentifier((int)$pluginParameters['day']);
        }

        return $day;
    }

    /**
     * Get TSFE
     *
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
