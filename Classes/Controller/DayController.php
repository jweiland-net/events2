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
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/*
 * The DayController contains actions to show a day record
 */
class DayController extends AbstractController
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

    public function listAction(?Filter $filter = null): ResponseInterface
    {
        $filter ??= GeneralUtility::makeInstance(Filter::class);
        $amountOfRecordsToShow = 0;
        if ($this->settings['listType'] === 'listLatest') {
            $amountOfRecordsToShow = (int)$this->settings['latest']['amountOfRecordsToShow'];
        }

        $days = $this->dayRepository->getDaysForListType(
            $this->settings['listType'] ?? 'list',
            $filter,
            $amountOfRecordsToShow
        );

        $this->postProcessAndAssignFluidVariables([
            'days' => $days,
            'filter' => $filter
        ]);

        CacheUtility::addPageCacheTagsByQuery($days->getQuery());

        return $this->htmlResponse();
    }

    /*
     * I call showAction with int instead of DomainModel to prevent that recursive validators will be called.
     */
    public function showAction(int $event, int $timestamp = 0): ResponseInterface
    {
        $day = $this->dayRepository->findDayByEventAndTimestamp($event, $timestamp);

        $this->postProcessControllerAction($day->getEvent(), $day);

        $this->postProcessAndAssignFluidVariables([
            'day' => $day
        ]);

        CacheUtility::addCacheTagsByEventRecords([$day->getEvent()]);

        return $this->htmlResponse();
    }
}
