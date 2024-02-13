<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\PageTitleProvider;

use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Traits\Typo3RequestTrait;
use TYPO3\CMS\Core\PageTitle\PageTitleProviderInterface;

/**
 * Instead of just setting the PageTitle to DetailView on Detail Page,
 * we think it would be much cooler to see the Event title in Browser-Tab.
 *
 * Please use config.pageTitleProviders.* to use our PageTitleProvider.
 */
class Events2PageTitleProvider implements PageTitleProviderInterface
{
    use Typo3RequestTrait;

    public function __construct(
        protected readonly EventRepository $eventRepository,
        protected readonly DayRepository $dayRepository
    ) {}

    public function getTitle(): string
    {
        $pageTitle = '';
        $gp = $this->getMergedRequestParameters();
        if ($this->isValidRequest($gp)) {
            $dayRecord = $this->dayRepository->getDayRecord(
                (int)$gp['event'],
                (int)$gp['timestamp']
            );

            if (!empty($dayRecord)) {
                $date = new \DateTimeImmutable(date('c', (int)$gp['timestamp']));
                $eventRecord = $this->eventRepository->getRecord(
                    (int)$dayRecord['event']
                );

                if (!empty($eventRecord)) {
                    $pageTitle = sprintf(
                        '%s - %s',
                        trim($eventRecord['title']),
                        $date->format('d.m.Y')
                    );
                }
            }
        }

        return $pageTitle;
    }

    protected function getMergedRequestParameters(): array
    {
        $getMergedWithPost = $this->getMergedWithPostFromRequest('tx_events2_show');
        if ($getMergedWithPost === []) {
            $getMergedWithPost = $this->getMergedWithPostFromRequest('tx_events2_list');
        }

        return $getMergedWithPost;
    }

    /**
     * This PageTitleProvider will only work on detail page of events2.
     * event and timestamp have to be given. Else: Page title will not be overwritten.
     */
    protected function isValidRequest(array $gp): bool
    {
        if (!isset($gp['controller'], $gp['action'], $gp['event'], $gp['timestamp'])) {
            return false;
        }

        return (int)$gp['timestamp'] > 0 && (int)$gp['event'] > 0;
    }
}
