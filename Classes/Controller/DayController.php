<?php
declare(strict_types = 1);
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
use JWeiland\Events2\Domain\Model\Filter;
use TYPO3\CMS\Core\Messaging\FlashMessage;

/**
 * The DayController contains actions for various list actions and detail view.
 */
class DayController extends AbstractController
{
    /**
     * @param Filter|null $filter
     */
    public function listAction(Filter $filter = null)
    {
        $days = $this->dayRepository->findEvents('list', $this->validateAndAssignFilter($filter));
        $this->view->assign('days', $days);
    }

    /**
     * @param Filter|null $filter
     */
    public function listLatestAction(Filter $filter = null)
    {
        $days = $days = $this->dayRepository->findEvents(
            'latest',
            $this->validateAndAssignFilter($filter),
            (int)$this->settings['latest']['amountOfRecordsToShow']
        );

        $this->view->assign('days', $days);
    }

    /**
     * @param Filter|null $filter
     */
    public function listTodayAction(Filter $filter = null)
    {
        $days = $this->dayRepository->findEvents('today', $this->validateAndAssignFilter($filter));
        $this->view->assign('days', $days);
    }

    /**
     * @param Filter|null $filter
     */
    public function listThisWeekAction(Filter $filter = null)
    {
        $days = $this->dayRepository->findEvents('thisWeek', $this->validateAndAssignFilter($filter));
        $this->view->assign('days', $days);
    }

    /**
     * @param Filter|null $filter
     */
    public function listRangeAction(Filter $filter = null)
    {
        $days = $this->dayRepository->findEvents('range', $this->validateAndAssignFilter($filter));
        $this->view->assign('days', $days);
    }

    /**
     * I call showAction with int instead of DomainModel to prevent that recursive validators will be called.
     *
     * @param int $event
     * @param int $timestamp
     */
    public function showAction(int $event, int $timestamp = 0)
    {
        $day = $this->dayRepository->findDayByEventAndTimestamp($event, $timestamp);

        // This is a very seldom problem. It appears, when you save tt_content by a hook and cast value of pages to int before save.
        $data = $this->configurationManager->getContentObject()->data;
        if ($data['pages'] === '0') {
            $this->addFlashMessage(
                'Please check content record with UID "' . $data['records'] . '". Column "pages" can not be 0. It must be empty or higher than 0.',
                'tt_content column pages can not be 0',
                FlashMessage::WARNING
            );
        }

        $this->view->assign('day', $day);
    }

    /**
     * @param int $timestamp
     */
    public function showByTimestampAction(int $timestamp)
    {
        $days = $this->dayRepository->findByTimestamp((int)$timestamp);
        $this->view->assign('days', $days);
    }
}
