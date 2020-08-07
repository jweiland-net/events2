<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Ajax;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Utility\DateTimeUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * This class is needed for jQuery calendar. If you flip to next month, this
 * class will be called and returns the events valid for selected month.
 */
class FindDaysForMonth
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

    public function __construct(
        ?ExtConf $extConf = null,
        ?DateTimeUtility $dateTimeUtility = null,
        ?CacheHashCalculator $cacheHashCalculator = null
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

    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getQueryParams()['tx_events2_events']['arguments'] ?? [];
        $this->initialize($parameters);
        $month = (int)$this->getArgument('month');
        $year = (int)$this->getArgument('year');

        // Save a session for selected month
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
            if ($date->getTimezone()->getLocation() === false) {
                $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            }
            $dayOfMonth = $date->format('j');

            $dayArray[$dayOfMonth][] = $addDay;
        }
        $this->addHolidays($dayArray);

        return new JsonResponse($dayArray);
    }

    protected function initialize(array $arguments): void
    {
        $this->setArguments($arguments);
        ExtensionManagementUtility::loadBaseTca(true);
    }

    protected function setArguments(array $arguments): void
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
        $values = GeneralUtility::intExplode(',', $list, true);
        foreach ($values as $key => $value) {
            if ($value === 0) {
                unset($values[$key]);
            }
        }

        return implode(',', array_unique($values));
    }

    /**
     * Get an argument from GET.
     *
     * @param string $argumentName
     * @return mixed
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
     * We can't create a speaking URI within a JavaScript for-loop.
     * But creating all links in events2 calendar by public TYPO3 API needs to long.
     * That's why we build these links the old-school way: &tx_events2_event[event]=123&...
     *
     * @param int $timestamp
     * @return string
     */
    protected function getUriForDay(int $timestamp): string
    {
        // uriBuilder is very slow: 223ms for 31 links. Swiping through the months feels bad */
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
        return $siteUrl . http_build_query($query);
    }

    protected function addHolidays(array &$days): void
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
                    'uid' => (int)$holiday['day'],
                    'class' => 'holiday'
                ];
            }
        }
    }

    protected function saveMonthAndYearInSession(int $month, int $year): void
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

    protected function findAllDaysInMonth(int $month, int $year): array
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

    protected function getFrontendUserAuthentication(): FrontendUserAuthentication
    {
        return GeneralUtility::makeInstance(FrontendUserAuthentication::class);
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
