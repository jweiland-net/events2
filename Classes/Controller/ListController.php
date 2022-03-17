<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Utility\CacheUtility;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/*
 * ListController to show all the different lists like latest, today, week and range
 */
class ListController extends AbstractController
{
    protected DayRepository $dayRepository;

    public function injectDayRepository(DayRepository $dayRepository): void
    {
        $this->dayRepository = $dayRepository;
    }

    public function initializeObject(): void
    {
        $this->dayRepository->setSettings($this->settings);
    }

    protected function initializeView(ViewInterface $view): void
    {
        parent::initializeView($view);

        // This is a very seldom problem. It appears, when you save tt_content by a hook and cast value of pages to int before save.
        $data = $this->configurationManager->getContentObject()->data;
        if ($data['pages'] === '0') {
            $this->addFlashMessage(
                'Please check content record with UID "' . $data['records'] . '". Column "pages" can not be 0. It must be empty or higher than 0.',
                'tt_content column pages can not be 0',
                AbstractMessage::WARNING
            );
        }
    }

    public function initializeListAction(): void
    {
        $this->preProcessControllerAction();
    }

    public function listAction(?Filter $filter = null): void
    {
        $filter = $this->getOrCreateFilter($filter);
        $days = $this->dayRepository->getDaysForListType('list', $filter);
        $this->postProcessAndAssignFluidVariables([
            'days' => $days,
            'filter' => $filter
        ]);
        CacheUtility::addPageCacheTagsByQuery($days->getQuery());
    }

    public function initializeListLatestAction(): void
    {
        $this->preProcessControllerAction();
    }

    public function listLatestAction(?Filter $filter = null): void
    {
        $filter = $this->getOrCreateFilter($filter);
        $days = $this->dayRepository->getDaysForListType(
            'latest',
            $filter,
            (int)$this->settings['latest']['amountOfRecordsToShow']
        );
        $this->postProcessAndAssignFluidVariables([
            'days' => $days,
            'filter' => $filter
        ]);
        CacheUtility::addPageCacheTagsByQuery($days->getQuery());
    }

    public function initializeListTodayAction(): void
    {
        $this->preProcessControllerAction();
    }

    public function listTodayAction(?Filter $filter = null): void
    {
        $filter = $this->getOrCreateFilter($filter);
        $days = $this->dayRepository->getDaysForListType('today', $filter);
        $this->postProcessAndAssignFluidVariables([
            'days' => $days,
            'filter' => $filter
        ]);
        CacheUtility::addPageCacheTagsByQuery($days->getQuery());
    }

    public function initializeListThisWeekAction(): void
    {
        $this->preProcessControllerAction();
    }

    public function listThisWeekAction(?Filter $filter = null): void
    {
        $filter = $this->getOrCreateFilter($filter);
        $days = $this->dayRepository->getDaysForListType('thisWeek', $filter);
        $this->postProcessAndAssignFluidVariables([
            'days' => $days,
            'filter' => $filter,
        ]);
        CacheUtility::addPageCacheTagsByQuery($days->getQuery());
    }

    public function initializeListRangeAction(): void
    {
        $this->preProcessControllerAction();
    }

    public function listRangeAction(?Filter $filter = null): void
    {
        $filter = $this->getOrCreateFilter($filter);
        $days = $this->dayRepository->getDaysForListType('range', $filter);
        $this->postProcessAndAssignFluidVariables([
            'days' => $days,
            'filter' => $filter,
        ]);
        CacheUtility::addPageCacheTagsByQuery($days->getQuery());
    }

    /**
     * Validate filter
     * Create empty filter if not valid
     * Assign filter to view
     */
    protected function getOrCreateFilter(?Filter $filter): Filter
    {
        return $filter ?? GeneralUtility::makeInstance(Filter::class);
    }
}
