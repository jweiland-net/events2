<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Session;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/*
 * Methods to access current FE User Session
 */
class UserSession
{
    /**
     * @var FrontendUserAuthentication
     */
    protected $feUser;

    public function __construct(FrontendUserAuthentication $feUser = null)
    {
        $this->feUser = $feUser ?? GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $this->feUser->start();
    }

    /**
     * Selected month and year was saved in user session by eID script
     * this method returns these values to set calendar to this date.
     *
     * @return array contains month and year OR empty array
     */
    public function getMonthAndYear(): array
    {
        $monthAndYear = $this->feUser->getKey(
            'ses',
            'events2MonthAndYearForCalendar'
        );
        if (!is_array($monthAndYear)) {
            $monthAndYear = [];
        }

        return $monthAndYear;
    }

    public function setMonthAndYear(int $month, int $year): void
    {
        $month = MathUtility::forceIntegerInRange($month, 1, 12);
        $year = MathUtility::forceIntegerInRange($year, 1970);
        $this->feUser->setAndSaveSessionData(
            'events2MonthAndYearForCalendar',
            [
                'month' => str_pad((string)$month, 2, '0', STR_PAD_LEFT),
                'year' => (string)$year
            ]
        );
    }
}
