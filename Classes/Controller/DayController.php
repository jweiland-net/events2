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
use JWeiland\Events2\Domain\Repository\OrganizerRepository;
use JWeiland\Events2\Service\JsonLdService;
use JWeiland\Events2\Utility\CacheUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/*
 * The DayController contains actions for various list actions and detail view.
 */
class DayController extends AbstractController
{
    /**
     * @var DayRepository
     */
    protected $dayRepository;

    /**
     * @var OrganizerRepository
     */
    protected $organizerRepository;

    public function __construct(
        DayRepository $dayRepository,
        OrganizerRepository $organizerRepository
    ) {
        $this->dayRepository = $dayRepository;
        $this->organizerRepository = $organizerRepository;
    }

    public function initializeObject(): void
    {
        $this->dayRepository->setSettings($this->settings);
    }

    protected function initializeView(ViewInterface $view): void
    {
        parent::initializeView($view);

        if ($this->settings['showFilterForOrganizerInFrontend']) {
            $this->view->assign(
                'organizers',
                $this->organizerRepository->getOrganizersForFilter()
            );
        }
    }

    /**
     * @param Filter|null $filter
     */
    public function listAction(?Filter $filter = null): void
    {
        $days = $this->dayRepository->findEvents('list', $this->validateAndAssignFilter($filter));
        $this->view->assign('days', $days);
        CacheUtility::addPageCacheTagsByQuery($days->getQuery());
    }

    /**
     * @param Filter|null $filter
     */
    public function listLatestAction(?Filter $filter = null): void
    {
        $days = $days = $this->dayRepository->findEvents(
            'latest',
            $this->validateAndAssignFilter($filter),
            (int)$this->settings['latest']['amountOfRecordsToShow']
        );

        $this->view->assign('days', $days);
        CacheUtility::addPageCacheTagsByQuery($days->getQuery());
    }

    /**
     * @param Filter|null $filter
     */
    public function listTodayAction(?Filter $filter = null): void
    {
        $days = $this->dayRepository->findEvents('today', $this->validateAndAssignFilter($filter));
        $this->view->assign('days', $days);
        CacheUtility::addPageCacheTagsByQuery($days->getQuery());
    }

    /**
     * @param Filter|null $filter
     */
    public function listThisWeekAction(?Filter $filter = null): void
    {
        $days = $this->dayRepository->findEvents('thisWeek', $this->validateAndAssignFilter($filter));
        $this->view->assign('days', $days);
        CacheUtility::addPageCacheTagsByQuery($days->getQuery());
    }

    /**
     * @param Filter|null $filter
     */
    public function listRangeAction(?Filter $filter = null): void
    {
        $days = $this->dayRepository->findEvents('range', $this->validateAndAssignFilter($filter));
        $this->view->assign('days', $days);
        CacheUtility::addPageCacheTagsByQuery($days->getQuery());
    }

    /**
     * I call showAction with int instead of DomainModel to prevent that recursive validators will be called.
     *
     * @param int $event
     * @param int $timestamp
     */
    public function showAction(int $event, int $timestamp = 0): void
    {
        $day = $this->dayRepository->findDayByEventAndTimestamp($event, $timestamp);
        $jsonLdService = GeneralUtility::makeInstance(JsonLdService::class);
        $jsonLdService->addJsonLdToPageHeader($day);

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
        CacheUtility::addCacheTagsByEventRecords([$day->getEvent()]);
    }

    /**
     * @param int $timestamp
     */
    public function showByTimestampAction(int $timestamp): void
    {
        $days = $this->dayRepository->findByTimestamp($timestamp);
        $this->view->assign('days', $days);
        CacheUtility::addPageCacheTagsByQuery($days->getQuery());
    }

    /**
     * Validate filter
     * Create empty filter if not valid
     * Assign filter to view
     *
     * @param Filter|null $filter
     * @return Filter
     */
    protected function validateAndAssignFilter(?Filter $filter): Filter
    {
        if ($filter === null) {
            $filter = GeneralUtility::makeInstance(Filter::class);
        }
        $this->view->assign('filter', $filter);
        return $filter;
    }
}
