<?php

namespace JWeiland\Events2\Controller;

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
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Filter;

/**
 * The DayController contains actions for various list actions and detail view.
 */
class DayController extends AbstractController
{
    /**
     * action list.
     *
     * @param Filter $filter
     * @throws \Exception
     */
    public function listAction(Filter $filter = null)
    {
        $days = $this->dayRepository->findEvents('list', $this->validateAndAssignFilter($filter));
        $this->view->assign('days', $days);
    }

    /**
     * action list latest.
     *
     * @param Filter $filter
     * @throws \Exception
     */
    public function listLatestAction(Filter $filter = null)
    {
        $limit = (bool)$this->settings['mergeEvents'] ? 0 : (int)$this->settings['latest']['amountOfRecordsToShow'];
        $days = $days = $this->dayRepository->findEvents('latest', $this->validateAndAssignFilter($filter), $limit);

        if ($this->settings['mergeEvents']) {
            $days = $this->dayRepository->groupDaysByEventAndSort(
                $days,
                (int)$this->settings['latest']['amountOfRecordsToShow']
            );
        }

        $this->view->assign('days', $days);
    }

    /**
     * action list today.
     *
     * @param Filter $filter
     * @throws \Exception
     */
    public function listTodayAction(Filter $filter = null)
    {
        $days = $this->dayRepository->findEvents('today', $this->validateAndAssignFilter($filter));
        $this->view->assign('days', $days);
    }

    /**
     * action list this week.
     *
     * @param Filter $filter
     * @throws \Exception
     */
    public function listThisWeekAction(Filter $filter = null)
    {
        $days = $this->dayRepository->findEvents('thisWeek', $this->validateAndAssignFilter($filter));
        $this->view->assign('days', $days);
    }

    /**
     * action list range.
     *
     * @param Filter $filter
     * @throws \Exception
     */
    public function listRangeAction(Filter $filter = null)
    {
        $days = $this->dayRepository->findEvents('range', $this->validateAndAssignFilter($filter));
        $this->view->assign('days', $days);
    }

    /**
     * action show.
     *
     * Hint: I call showAction with int instead of DomainModel
     * to prevent that recursive validators will be called
     *
     * @param int $event
     * @param int $timestamp
     * @throws \Exception
     */
    public function showAction($event, $timestamp = 0)
    {
        $day = $this->dayRepository->findOneByTimestamp($event, $timestamp);
        if (!$day instanceof Day) {
            /** @var Event $eventObject */
            $eventObject = $this->eventRepository->findByIdentifier($event);

            /** @var Day $day */
            $day = $this->objectManager->get(Day::class);
            $day->setEvent($eventObject);
        }
        $this->view->assign('day', $day);
    }

    /**
     * action showByTimestamp.
     *
     * @param int $timestamp
     * @throws \Exception
     */
    public function showByTimestampAction($timestamp)
    {
        $days = $this->dayRepository->findByTimestamp((int)$timestamp);
        $this->view->assign('days', $days);
    }
}
