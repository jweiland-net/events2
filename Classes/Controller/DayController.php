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
use TYPO3\CMS\Core\Messaging\FlashMessage;

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
        $limit = (bool)$this->settings['mergeRecurringEvents'] ? 0 : (int)$this->settings['latest']['amountOfRecordsToShow'];
        $days = $days = $this->dayRepository->findEvents('latest', $this->validateAndAssignFilter($filter), $limit);

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
            // something went wrong. Inform user how to solve this problem.

            // This is a very seldom problem. It appears, when you save tt_content by a hook and cast value of pages to int before save.
            $data = $this->configurationManager->getContentObject()->data;
            if ($data['pages'] === '0') {
                $this->addFlashMessage(
                    'Please check content record with UID "' . $data['records'] . '". Column "pages" can not be 0. It must be empty or higher than 0.',
                    'tt_content column pages can not be 0',
                    FlashMessage::WARNING
                );
            } else {
                // If a time record was added or changed, the timestamp in URI will not match any timestamps in DB anymore
                $this->addFlashMessage(
                    'It seems that event record was updated in the meantime. Please go back to list view, reload and try to open the detail view again',
                    'Event record has changed',
                    FlashMessage::WARNING
                );
            }
        } else {
            $this->view->assign('day', $day);
        }
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
