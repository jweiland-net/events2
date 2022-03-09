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
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * This class is needed for jQuery calendar. If you click on a day this class will be called
 * and returns an URI to the expected event for given day.
 */
class GetUriForDay
{
    /**
     * Arguments from GET.
     */
    protected array $arguments = [];

    protected UriBuilder $uriBuilder;

    protected DateTimeUtility $dateTimeUtility;

    protected DatabaseService $databaseService;

    /**
     * Will be called by call_user_func_array, so don't add Extbase classes with inject methods as argument
     */
    public function __construct(
        UriBuilder $uriBuilder,
        DateTimeUtility $dateTimeUtility,
        DatabaseService $databaseService
    ) {
        $this->uriBuilder = $uriBuilder;
        $this->dateTimeUtility = $dateTimeUtility;
        $this->databaseService = $databaseService;
    }

    public function processRequest(ServerRequestInterface $request): JsonResponse
    {
        $arguments = $request->getQueryParams()['tx_events2_events']['arguments'] ?? [];
        $this->setArguments($arguments);

        $startDate = $this->getStartDateFromRequest();
        $endDate = $startDate->modify('+1 day');

        $days = $this->databaseService->getDaysInRange(
            $startDate,
            $endDate,
            GeneralUtility::intExplode(',', $this->getArgument('storagePids'), true),
            GeneralUtility::intExplode(',', $this->getArgument('categories'), true)
        );

        //'uri' => $this->getUriForDay((int)$day['day']),

        return new JsonResponse($days);
    }

    /**
     * Sanitize various values before setting them in arguments
     */
    protected function setArguments(array $arguments): void
    {
        $this->arguments = [
            'day' => MathUtility::forceIntegerInRange($arguments['day'], 1, 31),
            'month' => MathUtility::forceIntegerInRange($arguments['month'], 1, 12),
            'year' => (int)$arguments['year'],
            'categories' => $this->sanitizeCommaSeparatedIntValues((string)$arguments['categories']),
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

    protected function getStartDateFromRequest(): \DateTimeImmutable
    {
        return $this->dateTimeUtility->standardizeDateTimeObject(
            \DateTimeImmutable::createFromFormat(
                'j.n.Y',
                sprintf(
                    '%d.%d.%d',
                    $this->getArgument('day'),
                    $this->getArgument('month'),
                    $this->getArgument('year'),
                )
            )
        );
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

    protected function getUriWithTimestamp(int $timestamp): string
    {
        return $this->uriBuilder
            ->reset()
            ->setTargetPageUid((int)$this->getArgument('pidOfListPage'))
            ->uriFor(
                'showByTimestamp',
                ['timestamp' => $timestamp],
                'Day',
                'events2',
                'events'
            );
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
