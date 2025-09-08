<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Middleware;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Event\ModifyDaysForMonthEvent;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Session\UserSession;
use JWeiland\Events2\Utility\DateTimeUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * This middleware is needed for LiteCalendar. If you flip to the next month, this
 * class will be called and return the events valid for the selected month.
 */
final readonly class GetDaysForMonthMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected ExtConf $extConf,
        protected DateTimeUtility $dateTimeUtility,
        protected UserSession $userSession,
        protected DatabaseService $databaseService,
        protected EventDispatcher $eventDispatcher,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getHeader('ext-events2') !== ['getDaysForMonth']) {
            return $handler->handle($request);
        }

        $json = (string)$request->getBody();
        if (json_validate($json) !== true) {
            return new JsonResponse();
        }

        $data = json_decode($json, true);
        if (!isset($data['month'], $data['year'], $data['categories'], $data['storagePages'])) {
            return new JsonResponse([
                'error' => 'Request uncompleted. Missing month, year, categories or storagePages in request.',
            ], 400);
        }

        $month = MathUtility::forceIntegerInRange($data['month'], 1, 12);
        $year = MathUtility::forceIntegerInRange($data['year'], 1500, 2500);
        $categories = GeneralUtility::intExplode(',', $data['categories'], true);
        $storagePages = GeneralUtility::intExplode(',', $data['storagePages'], true);

        // Save a session for a selected month
        $this->userSession->setMonthAndYear($month, $year);

        $daysOfMonth = [];
        foreach ($this->findAllDaysInMonth($month, $year, $categories, $storagePages) as $day) {
            // generate day of the month.
            // Convert int to DateTime like extbase does and set TimezoneType to something like Europe/Berlin
            $date = new \DateTimeImmutable(date('c', (int)$day['day']));
            if ($date->getTimezone()->getLocation() === false) {
                $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            }

            $daysOfMonth[] = [
                'uid' => (int)$day['uid'],
                'isHoliday' => false,
                'additionalClasses' => [],
                'dayOfMonth' => (int)$date->format('j'),
            ];
        }

        $this->addHolidays($daysOfMonth, $month);

        /** @var ModifyDaysForMonthEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ModifyDaysForMonthEvent($daysOfMonth),
        );

        return new JsonResponse($event->getDays());
    }

    protected function addHolidays(array &$days, int $month): void
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_holiday');
        $queryResult = $queryBuilder
            ->select('day')
            ->from('tx_events2_domain_model_holiday')
            ->where(
                $queryBuilder->expr()->eq(
                    'month',
                    $queryBuilder->createNamedParameter($month, Connection::PARAM_INT),
                ),
            )
            ->executeQuery();

        while ($holiday = $queryResult->fetchAssociative()) {
            $days[] = [
                'dayOfMonth' => (int)$holiday['day'],
                'isHoliday' => true,
                'additionalClasses' => ['holiday'],
            ];
        }
    }

    /**
     * @return array[]
     */
    protected function findAllDaysInMonth(int $month, int $year, array $categories, array $storagePages): array
    {
        $earliestAllowedDate = new \DateTimeImmutable('now midnight');
        $earliestAllowedDate = $earliestAllowedDate->modify(sprintf('-%d months', $this->extConf->getRecurringPast()));

        $latestAllowedDate = new \DateTimeImmutable('now midnight');
        $latestAllowedDate = $latestAllowedDate->modify(sprintf('+%d months', $this->extConf->getRecurringFuture()));

        // get start and ending of a given month
        // j => day without leading 0, n => month without leading 0
        $firstDayOfMonth = $this->dateTimeUtility->standardizeDateTimeObject(
            \DateTimeImmutable::createFromFormat('j.n.Y', '1.' . $month . '.' . $year),
        );
        $lastDayOfMonth = $firstDayOfMonth->modify('last day of this month');

        if (
            $earliestAllowedDate > $firstDayOfMonth &&
            $earliestAllowedDate->format('mY') === $firstDayOfMonth->format('mY')
        ) {
            // if $earliestAllowedDate 17.01.2008 is greater than $firstDayOfMonth (01.01.2008)
            // and both dates are in the same month, then set the date to $earliestAllowedDate 17.01.2008
            $firstDayOfMonth = $earliestAllowedDate;
        } elseif (
            $latestAllowedDate < $lastDayOfMonth &&
            $latestAllowedDate->format('mY') === $lastDayOfMonth->format('mY')
        ) {
            // if $latestAllowedDate 23.09.2008 is lower than $lastDayOfMonth (30.09.2008)
            // and both dates are in the same month, then set the date to $latestAllowedDate 23.09.2008
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
            $storagePages,
            $categories,
        );
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
