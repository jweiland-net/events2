<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Repository\UserRepository;
use JWeiland\Events2\Helper\DayHelper;
use JWeiland\Events2\Session\UserSession;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/*
 * Controller to show the jquery UI calendar. Further is stores the selected month in user-session
 */
class CalendarController extends AbstractController
{
    /**
     * @var DayHelper
     */
    protected $dayHelper;

    /**
     * @var UserSession
     */
    protected $userSession;

    public function __construct(
        DayHelper $dayHelper,
        UserSession $userSession
    ) {
        $this->dayHelper = $dayHelper;
        $this->userSession = $userSession;
    }

    public function showAction(): void
    {
        $placeHolders = $this->getEnvironmentPlaceholders();

        // get month and year from session
        $monthAndYear = $this->userSession->getMonthAndYear();
        $day = $this->dayHelper->getDayFromUri();

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

    protected function getEnvironmentPlaceholders(): array
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

    protected function getTypo3SiteUrl(): string
    {
        return GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
    }
}
