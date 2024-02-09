<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Helper;

use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Session\UserSession;
use JWeiland\Events2\Traits\Typo3RequestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper class containing various methods to help to build variables for LiteCalendar
 */
class CalendarHelper
{
    use Typo3RequestTrait;

    public function __construct(
        protected readonly DayHelper $dayHelper,
        protected readonly UserSession $userSession
    ) {
    }

    public function getCalendarVariables(): array
    {
        $variables = $this->getBasicVariables();
        $this->addDefaultCalendarVariables($variables);
        $this->addCalendarVariablesByDayParameterFromUrl($variables);
        $this->addCalendarVariablesByUserSession($variables);

        return $variables;
    }

    protected function getBasicVariables(): array
    {
        return [
            'siteUrl' => $this->getTypo3SiteUrl(),
            'siteId' => $this->getTypoScriptFrontendController()->id
        ];
    }

    protected function addCalendarVariablesByUserSession(array &$variables): void
    {
        // 1st priority. If a user session was found we will use stored month/year from session
        $monthAndYear = $this->userSession->getMonthAndYear();
        if ($monthAndYear !== []) {
            $variables['day'] = '01';
            $variables['month'] = (string)$monthAndYear['month'];
            $variables['year'] = (string)$monthAndYear['year'];
        }
    }

    protected function addCalendarVariablesByDayParameterFromUrl(array &$variables): void
    {
        // 2nd priority. If a day parameter was found in current URL use that
        $day = $this->dayHelper->getDayFromUri();
        if ($day instanceof Day) {
            $variables['day'] = $day->getDay()->format('d');
            $variables['month'] = $day->getDay()->format('m');
            $variables['year'] = $day->getDay()->format('Y');
        }
    }

    protected function addDefaultCalendarVariables(array &$variables): void
    {
        // lowest priority. Will be overwritten, if day oder user session exists
        $variables['day'] = date('d');
        $variables['month'] = date('m');
        $variables['year'] = date('Y');
    }

    protected function getTypo3SiteUrl(): string
    {
        return GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
    }
}
