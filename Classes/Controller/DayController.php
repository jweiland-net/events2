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
use JWeiland\Events2\Traits\InjectCacheServiceTrait;
use JWeiland\Events2\Traits\InjectDayFactoryTrait;
use JWeiland\Events2\Traits\InjectDayRepositoryTrait;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * The DayController contains actions to show a day record
 */
class DayController extends AbstractController
{
    use InjectCacheServiceTrait;
    use InjectDayFactoryTrait;
    use InjectDayRepositoryTrait;

    public function initializeObject(): void
    {
        $this->dayRepository->setSettings($this->settings);
    }

    protected function initializeView(ViewInterface $view): void
    {
        parent::initializeView($view);

        // This is a very seldom problem. It appears when you save tt_content by a hook and cast value of pages to int before save.
        $data = $this->request->getAttribute('currentContentObject')->data;
        if ($data['pages'] === '0') {
            $this->addFlashMessage(
                'Please check content record with UID "' . $data['records'] . '". Column "pages" can not be 0. It must be empty or higher than 0.',
                'tt_content column pages can not be 0',
                ContextualFeedbackSeverity::WARNING,
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
            $amountOfRecordsToShow,
        );

        $this->postProcessAndAssignFluidVariables([
            'days' => $days,
            'filter' => $filter,
        ]);

        $this->cacheService->addPageCacheTagsByQuery($days->getQuery());

        return $this->htmlResponse();
    }

    /**
     * I call showAction with int instead of DomainModel to prevent that recursive validators will be called.
     */
    public function showAction(int $event, int $timestamp = 0): ResponseInterface
    {
        $day = $this->dayFactory->findDayByEventAndTimestamp($event, $timestamp, $this->dayRepository->createQuery());

        $this->postProcessControllerAction($day->getEvent(), $day);

        $this->postProcessAndAssignFluidVariables([
            'day' => $day,
        ]);

        $this->cacheService->addCacheTagsByEventRecords([$day->getEvent()]);

        return $this->htmlResponse();
    }
}
