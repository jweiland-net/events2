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
use TYPO3\CMS\Core\PageTitle\PageTitleProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

/**
 * Instead of just setting the PageTitle to DetailView on Detail Page,
 * we think it would be much cooler to see the Event title in Browser-Tab.
 *
 * Please use config.pageTitleProviders.* to use our PageTitleProvider.
 */
class Events2PageTitleProvider implements PageTitleProviderInterface
{
    protected EventRepository $eventRepository;

    protected DayRepository $dayRepository;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->eventRepository = $objectManager->get(EventRepository::class);
        $this->dayRepository = $objectManager->get(DayRepository::class);
    }

    public function getTitle(): string
    {
        $pageTitle = '';
        $gp = GeneralUtility::_GPmerged('tx_events2_events') ?? [];
        if ($this->isValidRequest($gp)) {
            $dayRecord = $this->dayRepository->getDayRecord(
                (int)$gp['event'],
                (int)$gp['timestamp']
            );

            if (!empty($dayRecord)) {
                $date = new \DateTime(date('c', (int)$gp['timestamp']));
                $eventRecord = $this->eventRepository->getEventRecord(
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

    protected function isValidRequest(array $gp): bool
    {
        if (!is_array($gp)) {
            return false;
        }

        if (!isset($gp['controller'], $gp['action'], $gp['timestamp'])) {
            return false;
        }

        return (int)$gp['timestamp'] > 0;
    }
}
