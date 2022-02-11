<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Ajax;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Event\ModifyDaysForMonthEvent;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Session\UserSession;
use JWeiland\Events2\Utility\DateTimeUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * This class is needed for jQuery calendar. If you flip to next month, this
 * class will be called and returns the events valid for selected month.
 */
class FindDaysForMonth
{
    /**
     * Arguments from GET.
     */
    protected array $arguments = [];

    protected ExtConf $extConf;

    protected DateTimeUtility $dateTimeUtility;

    protected CacheHashCalculator $cacheHashCalculator;

    protected UserSession $userSession;

    protected DatabaseService $databaseService;

    protected EventDispatcher $eventDispatcher;

    /**
     * Will be called by call_user_func_array, so don't add Extbase classes with inject methods as argument
     */
    public function __construct(
        ExtConf $extConf,
        DateTimeUtility $dateTimeUtility,
        CacheHashCalculator $cacheHashCalculator,
        UserSession $userSession,
        DatabaseService $databaseService,
        EventDispatcher $eventDispatcher
    ) {
        $this->extConf = $extConf;
        $this->dateTimeUtility = $dateTimeUtility;
        $this->cacheHashCalculator = $cacheHashCalculator;
        $this->userSession = $userSession;
        $this->databaseService = $databaseService;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function processRequest(ServerRequestInterface $request): JsonResponse
    {
        $parameters = $request->getQueryParams()['tx_events2_events']['arguments'] ?? [];
        $this->initialize($parameters);
        $month = (int)$this->getArgument('month');
        $year = (int)$this->getArgument('year');

        // Save a session for selected month
        $this->userSession->setMonthAndYear($month, $year);

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
            $date = new \DateTimeImmutable(date('c', (int)$day['day']));
            if ($date->getTimezone()->getLocation() === false) {
                $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            }

            $dayOfMonth = $date->format('j');

            $dayArray[$dayOfMonth][] = $addDay;
        }

        $this->addHolidays($dayArray);

        /** @var ModifyDaysForMonthEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ModifyDaysForMonthEvent($dayArray)
        );

        return new JsonResponse($event->getDays());
    }

    protected function initialize(array $arguments): void
    {
        $this->setArguments($arguments);
        ExtensionManagementUtility::loadBaseTca(true);
    }

    /**
     * Sanitize various values before setting them in arguments
     */
    protected function setArguments(array $arguments): void
    {
        $this->arguments = [
            'categories' => $this->sanitizeCommaSeparatedIntValues((string)$arguments['categories']),
            'month' => MathUtility::forceIntegerInRange($arguments['month'], 1, 12),
            'year' => MathUtility::forceIntegerInRange($arguments['year'], 1500, 2500),
            'pidOfListPage' => (int)$arguments['pidOfListPage'],
            'storagePids' => $this->sanitizeCommaSeparatedIntValues((string)$arguments['storagePids'])
        ];
    }

    /**
     * Sanitize comma separated values
     * Remove empty values
     * Remove values which can't be interpreted as int
     * Cast each valid value to int
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
     * @return string|int
     */
    protected function getArgument(string $argumentName)
    {
        return $this->arguments[$argumentName] ?? '';
    }

    /**
     * We can't create a speaking URI within a JavaScript for-loop.
     * But creating all links in events2 calendar by public TYPO3 API needs to long.
     * That's why we build these links the old-school way: &tx_events2_event[event]=123&...
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
                'timestamp' => $timestamp,
            ],
        ];
        $cacheHashArray = $this->cacheHashCalculator->getRelevantParameters(GeneralUtility::implodeArrayForUrl('', $query));
        $query['cHash'] = $this->cacheHashCalculator->calculateCacheHash($cacheHashArray);

        return $siteUrl . http_build_query($query);
    }

    protected function addHolidays(array &$days): void
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_holiday');
        $statement = $queryBuilder
            ->select('day')
            ->from('tx_events2_domain_model_holiday')
            ->where(
                $queryBuilder->expr()->eq(
                    'month',
                    $queryBuilder->createNamedParameter($this->getArgument('month'), \PDO::PARAM_INT)
                )
            )
            ->execute();

        while ($holiday = $statement->fetch()) {
            $days[$holiday['day']][] = [
                'uid' => (int)$holiday['day'],
                'class' => 'holiday'
            ];
        }
    }

    /**
     * @return array[]
     */
    protected function findAllDaysInMonth(int $month, int $year): array
    {
        $earliestAllowedDate = new \DateTimeImmutable('now midnight');
        $earliestAllowedDate = $earliestAllowedDate->modify(sprintf('-%d months', $this->extConf->getRecurringPast()));

        $latestAllowedDate = new \DateTimeImmutable('now midnight');
        $latestAllowedDate = $latestAllowedDate->modify(sprintf('+%d months', $this->extConf->getRecurringFuture()));

        // get start and ending of given month
        // j => day without leading 0, n => month without leading 0
        $firstDayOfMonth = $this->dateTimeUtility->standardizeDateTimeObject(
            \DateTimeImmutable::createFromFormat('j.n.Y', '1.' . $month . '.' . $year)
        );
        $lastDayOfMonth = $firstDayOfMonth->modify('last day of this month');

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

        return $this->databaseService->getDaysInRange(
            $firstDayOfMonth,
            $lastDayOfMonth->modify('tomorrow'),
            GeneralUtility::intExplode(',', $this->getArgument('storagePids'), true),
            GeneralUtility::intExplode(',', $this->getArgument('categories'), true)
        );
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
