<?php

namespace JWeiland\Events2\Ajax\FindDaysForMonth;

/*
 * This file is part of the events2 project.
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
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * This class is needed for jQuery calendar. If you flip to next month, this
 * class will be called and returns the events valid for selected month.
 */
class Ajax
{
    /**
     * arguments from GET.
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * @var ExtConf
     */
    protected $extConf;

    /**
     * @var DateTimeUtility
     */
    protected $dateTimeUtility;

    /**
     * @var CacheHashCalculator
     */
    protected $cacheHashCalculator;

    /**
     * Ajax constructor.
     *
     * @param ExtConf|null $extConf
     * @param DateTimeUtility|null $dateTimeUtility
     * @param CacheHashCalculator|null $cacheHashCalculator
     */
    public function __construct(
        ExtConf $extConf = null,
        DateTimeUtility $dateTimeUtility = null,
        CacheHashCalculator $cacheHashCalculator = null
    ) {
        if ($extConf === null) {
            $extConf = GeneralUtility::makeInstance(ExtConf::class);
        }
        $this->extConf = $extConf;

        if ($dateTimeUtility === null) {
            $dateTimeUtility = GeneralUtility::makeInstance(DateTimeUtility::class);
        }
        $this->dateTimeUtility = $dateTimeUtility;

        if ($cacheHashCalculator === null) {
            $cacheHashCalculator = GeneralUtility::makeInstance(CacheHashCalculator::class);
        }
        $this->cacheHashCalculator = $cacheHashCalculator;
    }

    /**
     * Initializes this class.
     *
     * @param array $arguments
     * @return void
     */
    protected function initialize(array $arguments)
    {
        $this->setArguments($arguments);
        ExtensionManagementUtility::loadBaseTca(true);
    }

    /**
     * Process ajax request.
     *
     * @param array $arguments Arguments to process
     * @return string
     * @throws \Exception
     */
    public function processAjaxRequest(array $arguments)
    {
        $this->initialize($arguments);
        $month = $this->getArgument('month');
        $year = $this->getArgument('year');

        // save a session for selected month
        $this->saveMonthAndYearInSession($month, $year);

        $dayArray = [];
        $days = $this->findAllDaysInMonth($month, $year);
        foreach ($days as $day) {
            $addDay = [
                'uid' => $day['uid'],
                'title' => $day['title']
            ];
            $addDay['uri'] = $this->getUriForDay((int)$day['day']);

            // generate day of month.
            // Convert int to DateTime like extbase does and set TimezoneType to something like Europe/Berlin
            $date = new \DateTime(date('c', (int)$day['day']));
            if ($date->timezone_type !== 3) {
                $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            }
            $dayOfMonth = $date->format('j');

            $dayArray[$dayOfMonth][] = $addDay;
        }
        $this->addHolidays($dayArray);

        return json_encode($dayArray);
    }

    /**
     * Set and check GET Arguments.
     *
     * @param array $arguments
     * @return void
     */
    protected function setArguments(array $arguments)
    {
        // sanitize categories
        $sanitizedArguments['categories'] = $this->sanitizeCommaSeparatedIntValues((string)$arguments['categories']);
        $sanitizedArguments['month'] = MathUtility::forceIntegerInRange($arguments['month'], 1, 12);
        $sanitizedArguments['year'] = MathUtility::forceIntegerInRange($arguments['year'], 1500, 2500);
        $sanitizedArguments['pidOfListPage'] = (int)$arguments['pidOfListPage'];
        $sanitizedArguments['storagePids'] = $this->sanitizeCommaSeparatedIntValues((string)$arguments['storagePids']);

        $this->arguments = $sanitizedArguments;
    }

    /**
     * Sanitize comma separated values
     * Remove empty values
     * Remove values which can't be interpreted as int
     * Cast each valid value to int
     *
     * @param string $list
     * @return string The sanitized int list
     */
    protected function sanitizeCommaSeparatedIntValues(string $list): string
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
     * Get an argument from GET.
     *
     * @param string $argumentName
     * @return string|array
     */
    protected function getArgument(string $argumentName)
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
     * @return string
     */
    protected function getUriForDay(int $timestamp): string
    {
        // uriBuilder is very slow: 223ms for 31 links */
        /*$uri = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($pid)
            ->uriFor('show', ['day' => $dayUid), 'Day', 'events2', 'events'];*/

        // create uri manually instead of uriBuilder
        $siteUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'index.php?';
        $query = [
            'id' => $this->getArgument('pidOfListPage'),
            'tx_events2_events' => [
                'controller' => 'Day',
                'action' => 'showByTimestamp',
                'timestamp' => (int)$timestamp,
            ],
        ];
        $cacheHashArray = $this->cacheHashCalculator->getRelevantParameters(GeneralUtility::implodeArrayForUrl('', $query));
        $query['cHash'] = $this->cacheHashCalculator->calculateCacheHash($cacheHashArray);
        $uri = $siteUrl . http_build_query($query);

        return $uri;
    }

    /**
     * Add Holidays
     *
     * @param array $days
     * @return void
     */
    protected function addHolidays(array &$days)
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_holiday');
        $holidays = $queryBuilder
            ->select('day')
            ->from('tx_events2_domain_model_holiday')
            ->where(
                $queryBuilder->expr()->eq(
                    'month',
                    $queryBuilder->createNamedParameter($this->getArgument('month'), \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();

        if (!empty($holidays)) {
            foreach ($holidays as $holiday) {
                $days[$holiday['day']][] = [
                    'uid' => $holiday['day'],
                    'class' => 'holiday'
                ];
            }
        }
    }

    /**
     * save selected month and year in an user session.
     *
     * @param int $month
     * @param int $year
     * @return void
     */
    protected function saveMonthAndYearInSession(int $month, int $year)
    {
        $userAuthentication = $this->getFrontendUserAuthentication();
        $userAuthentication->start();
        $userAuthentication->setAndSaveSessionData(
            'events2MonthAndYearForCalendar',
            [
                'month' => str_pad((string)$month, 2, '0', STR_PAD_LEFT),
                'year' => (string)$year
            ]
        );
    }

    /**
     * Find all days in given month.
     *
     * @param int $month
     * @param int $year
     * @return array
     * @throws \Exception
     */
    protected function findAllDaysInMonth(int $month, int $year)
    {
        $earliestAllowedDate = new \DateTime('now midnight');
        $earliestAllowedDate->modify(sprintf('-%d months', $this->extConf->getRecurringPast()));
        $latestAllowedDate = new \DateTime('now midnight');
        $latestAllowedDate->modify(sprintf('+%d months', $this->extConf->getRecurringFuture()));

        // get start and ending of given month
        // j => day without leading 0, n => month without leading 0
        $firstDayOfMonth = $this->dateTimeUtility->standardizeDateTimeObject(
            \DateTime::createFromFormat('j.n.Y', '1.' . $month . '.' . $year)
        );
        $lastDayOfMonth = clone $firstDayOfMonth;
        $lastDayOfMonth->modify('last day of this month');

        if (
            $earliestAllowedDate > $firstDayOfMonth &&
            $earliestAllowedDate->format('mY') === $firstDayOfMonth->format('mY')
        ) {
            // if $earliestAllowedDate 17.01.2008 is greater than $firstDayOfMonth (01.01.2008)
            // and both dates are in same month, then set date to $earliestAllowedDate 17.01.2008
            $firstDayOfMonth = $earliestAllowedDate;
        } elseif (
            $latestAllowedDate < $lastDayOfMonth &&
            $latestAllowedDate->format('mY') === $lastDayOfMonth->format('mY')
        ) {
            // if $latestAllowedDate 23.09.2008 is lower than $lastDayOfMonth (30.09.2008)
            // and both dates are in same month, then set date to $latestAllowedDate 23.09.2008
            $lastDayOfMonth = $latestAllowedDate;
        } elseif (
            $earliestAllowedDate > $firstDayOfMonth ||
            $latestAllowedDate < $lastDayOfMonth
        ) {
            // if both values are out of range, do not return any date
            return [];
        }

        /** @var DatabaseService $databaseService */
        $databaseService = GeneralUtility::makeInstance(DatabaseService::class);
        return $databaseService->getDaysInRange(
            $firstDayOfMonth,
            $lastDayOfMonth->modify('tomorrow'),
            GeneralUtility::intExplode(',', $this->getArgument('storagePids'), true),
            GeneralUtility::intExplode(',', $this->getArgument('categories'), true)
        );
    }

    /**
     * Get Frontend User Authentication
     *
     * @return FrontendUserAuthentication
     */
    protected function getFrontendUserAuthentication(): FrontendUserAuthentication
    {
        return GeneralUtility::makeInstance(FrontendUserAuthentication::class);
    }

    /**
     * Get TYPO3s Connection Pool
     *
     * @return ConnectionPool
     */
    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
