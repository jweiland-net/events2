<?php

namespace JWeiland\Events2\Controller;

/*
 * This file is part of the TYPO3 CMS project.
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

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DayController extends AbstractController
{
    /**
     * action list.
     *
     * @param Filter $filter
     *
     * @return void
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
     *
     * @return void
     */
    public function listLatestAction(Filter $filter = null)
    {
        $days = $this->dayRepository->findEvents('latest', $this->validateAndAssignFilter($filter));
        $this->view->assign('days', $days);
    }
    
    /**
     * action list today.
     *
     * @param Filter $filter
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
     */
    public function showAction($event, $timestamp)
    {
        $this->view->assign('day', $this->dayRepository->findOneByTimestamp($event, $timestamp));
    }
    
    /**
     * action showByTimestamp.
     *
     * @param int $timestamp
     *
     * @return void
     */
    public function showByTimestampAction($timestamp)
    {
        $days = $this->dayRepository->findByTimestamp((int)$timestamp);
        $this->view->assign('days', $days);
    }
}
