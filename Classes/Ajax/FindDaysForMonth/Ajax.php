<?php

namespace JWeiland\Events2\Ajax\FindDaysForMonth;

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
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Ajax
{
    /**
     * arguments from GET.
     *
     * @var array
     */
    protected $arguments = array();

    /**
     * database.
     *
     * @var DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @var ExtConf
     */
    protected $extConf;

    /**
     * @var DateTimeUtility
     */
    protected $dateTimeUtility;

    /**
     * @var DayRepository
     */
    protected $dayRepository;

    /**
     * @var CacheHashCalculator
     */
    protected $cacheHashCalculator;

    /**
     * inject extConf
     *
     * @param ExtConf $extConf
     *
     * @return void
     */
    public function injectExtConf(ExtConf $extConf)
    {
        $this->extConf = $extConf;
    }

    /**
     * inject DateTime Utility.
     *
     * @param DateTimeUtility $dateTimeUtility
     *
     * @return void
     */
    public function injectDateTimeUtility(DateTimeUtility $dateTimeUtility)
    {
        $this->dateTimeUtility = $dateTimeUtility;
    }

    /**
     * inject dayRepository
     *
     * @param DayRepository $dayRepository
     *
     * @return void
     */
    public function injectDayRepository(DayRepository $dayRepository)
    {
        $this->dayRepository = $dayRepository;
    }

    /**
     * inject CacheHash Calculator.
     *
     * @param CacheHashCalculator $cacheHashCalculator
     *
     * @return void
     */
    public function injectCacheHashCalculator(CacheHashCalculator $cacheHashCalculator)
    {
        $this->cacheHashCalculator = $cacheHashCalculator;
    }

    /**
     * initializes this class.
     *
     * @param array $arguments
     */
    public function initialize(array $arguments)
    {
        // load cached TCA. Needed for enableFields
        Bootstrap::getInstance()->loadExtensionTables();
        $this->setArguments($arguments);
    }

    /**
     * set and check GET Arguments.
     *
     * @param array $arguments
     */
    public function setArguments(array $arguments)
    {
        // sanitize categories
        $sanitizedArguments['categories'] = $this->sanitizeCommaSeparatedIntValues((string)$arguments['categories']);
        $sanitizedArguments['month'] = (int)$arguments['month'];
        $sanitizedArguments['year'] = (int)$arguments['year'];
        $sanitizedArguments['pidOfListPage'] = (int)$arguments['pidOfListPage'];
        $sanitizedArguments['storagePids'] = $this->sanitizeCommaSeparatedIntValues((string)$arguments['storagePids']);

        $this->arguments = $sanitizedArguments;
    }

    /**
     * sanitize comma separated values
     * remove empty values
     * remove values which can't be interpreted as int
     * cast each valid value to int
     *
     * @param string $list
     *
     * @return string The sanitized int list
     */
    protected function sanitizeCommaSeparatedIntValues($list)
    {
        $values = GeneralUtility::trimExplode(',', $list, true);
        foreach ($values as $key => $value) {
            if (MathUtility::canBeInterpretedAsInteger($value)) {
                $values[$key] = (int)$value;
            } else {
                unset($values[$key]);
            }
        }

        return implode(',', array_unique($values));
    }

    /**
     * getter for arguments
     * needed for unitTests.
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * process ajax request.
     *
     * @param array $arguments Arguments to process
     *
     * @return string
     */
    public function processAjaxRequest(array $arguments)
    {
        $this->initialize($arguments);
        $month = $this->getArgument('month');
        $year = $this->getArgument('year');

        // save a session for selected month
        $this->saveMonthAndYearInSession($month, $year);

        $dayArray = array();
        $days = $this->findAllDaysInMonth($month, $year);
        /** @var Day $day */
        foreach ($days as $day) {
            $addDay = array(
                'uid' => $day->getEvent()->getUid(),
                'title' => $day->getEvent()->getTitle()
            );
            $addDay['uri'] = $this->getUriForDay($day->getDay()->format('U'));
            $dayOfMonth = $day->getDay()->format('j');
            $dayArray[$dayOfMonth][] = $addDay;
        }
        $this->addHolidays($dayArray);

        return json_encode($dayArray);
    }

    /**
     * get an argument from GET.
     *
     * @param string $argumentName
     *
     * @return string
     */
    protected function getArgument($argumentName)
    {
        if (isset($this->arguments[$argumentName])) {
            return $this->arguments[$argumentName];
        } else {
            return '';
        }
    }

    /**
     * We can't create the uri within a JavaScript for-loop.
     * This way we also have realurl functionality
     * We need the current day for calendar and day controller.
     *
     * @param int $timestamp
     *
     * @return string
     */
    public function getUriForDay($timestamp)
    {
        // uriBuilder is very slow: 223ms for 31 links */
        /*$uri = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($pid)
            ->uriFor('show', array('day' => $dayUid), 'Day', 'events2', 'events');*/

        // create uri manually instead of uriBuilder
        $siteUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'index.php?';
        $query = array(
            'id' => $this->getArgument('pidOfListPage'),
            'tx_events2_events' => array(
                'controller' => 'Day',
                'action' => 'showByTimestamp',
                'timestamp' => (int)$timestamp,
            ),
        );
        $cacheHashArray = $this->cacheHashCalculator->getRelevantParameters(GeneralUtility::implodeArrayForUrl('', $query));
        $query['cHash'] = $this->cacheHashCalculator->calculateCacheHash($cacheHashArray);
        $uri = $siteUrl . http_build_query($query);

        return $uri;
    }

    /**
     * Add Holidays
     *
     * @param array $days
     */
    protected function addHolidays(array &$days)
    {
        $monthOfYear = $this->getDatabaseConnection()->fullQuoteStr(
            $this->getArgument('month'),
            'tx_events2_domain_model_holiday'
        );
        $holidays = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'day',
            'tx_events2_domain_model_holiday',
            'month=' . $monthOfYear
        );
        if (!empty($holidays)) {
            foreach ($holidays as $holiday) {
                $days[$holiday['day']][] = array(
                    'uid' => $holiday['day'],
                    'class' => 'holiday'
                );
            }
        }
    }

    /**
     * save selected month and year in an user session.
     *
     * @param int $month
     * @param int $year
     */
    protected function saveMonthAndYearInSession($month, $year)
    {
        $userAuthentication = $this->getFrontendUserAuthentication();
        $userAuthentication->start();
        $userAuthentication->setAndSaveSessionData(
            'events2MonthAndYearForCalendar',
            array(
                'month' => str_pad((string)$month, 2, '0', STR_PAD_LEFT),
                'year' => (string)$year
            )
        );
    }

    /**
     * find all days in given month.
     *
     * @param int $month
     * @param int $year
     *
     * @return array
     */
    public function findAllDaysInMonth($month, $year)
    {
        $earliestAllowedDate = new \DateTime("now midnight");
        $earliestAllowedDate->modify(sprintf('-%d months', $this->extConf->getRecurringPast()));
        $latestAllowedDate = new \DateTime("now midnight");
        $latestAllowedDate->modify(sprintf('+%d months', $this->extConf->getRecurringFuture()));

        // get start and ending of given month
        // j => day without leading 0, n => month without leading 0
        $firstDayOfMonth = $this->dateTimeUtility->standardizeDateTimeObject(
            \DateTime::createFromFormat('j.n.Y', '1.' . $month . '.' . $year)
        );
        $lastDayOfMonth = clone $firstDayOfMonth;
        $lastDayOfMonth->modify('last day of this month')->modify('tomorrow');

        if (
            $earliestAllowedDate > $firstDayOfMonth &&
            $earliestAllowedDate->format('mY') === $firstDayOfMonth->format('mY')
        ) {
            // if both dates are in same month and year, set to highest value
            $firstDayOfMonth = $earliestAllowedDate;
        } elseif (
            $latestAllowedDate < $lastDayOfMonth &&
            $latestAllowedDate->format('mY') === $lastDayOfMonth->format('mY')
        ) {
            // if both dates are in same month and year, set to lowest value
            $lastDayOfMonth = $latestAllowedDate;
        } elseif (
            $earliestAllowedDate > $firstDayOfMonth ||
            $latestAllowedDate < $lastDayOfMonth
        ) {
            // if both values are out of range, do not return any date
            return array();
        }

        $constraint = array();

        $query = $this->dayRepository->createQuery();
        $query->getQuerySettings()->setStoragePageIds(explode(',', $this->getArgument('storagePids')));
        if (!empty($this->getArgument('categories'))) {
            $orConstraint = array();
            foreach (explode(',', $this->getArgument('categories')) as $category) {
                $orConstraint[] = $query->contains('event.categories', $category);
            }
            $constraint[] = $query->logicalOr($orConstraint);
        }
        $constraint[] = $query->greaterThanOrEqual('day', $firstDayOfMonth);
        $constraint[] = $query->lessThan('day', $lastDayOfMonth);

        return $query->matching($query->logicalAnd($constraint))->execute();
    }

    /**
     * Get Frontend User Authentication
     *
     * @return FrontendUserAuthentication
     */
    protected function getFrontendUserAuthentication()
    {
        /** @var FrontendUserAuthentication $feAuthentication */
        $feAuthentication = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Authentication\\FrontendUserAuthentication');
        return $feAuthentication;
    }

    /**
     * Get TYPO3 Database Connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    public function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
