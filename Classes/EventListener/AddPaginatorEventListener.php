<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\EventListener;

use JWeiland\Events2\Event\PostProcessFluidVariablesEvent;
use JWeiland\Events2\Pagination\GetPostPagination;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;

/*
 * Add paginator to fluid template
 */
class AddPaginatorEventListener extends AbstractControllerEventListener
{
    protected int $itemsPerPage = 15;

    protected array $allowedControllerActions = [
        'Day' => [
            'list'
        ],
        'Search' => [
            'listSearchResults'
        ]
    ];

    public function __invoke(PostProcessFluidVariablesEvent $event): void
    {
        // Do not show pagination for listLatest
        if (
            $this->isValidRequest($event)
            && ($days = $event->getFluidVariables()['days'] ?? null)
            && $days !== null
            && ($event->getSettings()['listType'] ?? 'listLatest') !== 'listLatest'
        ) {
            $paginator = new QueryResultPaginator(
                $event->getFluidVariables()['days'],
                $this->getCurrentPage($event),
                $this->getItemsPerPage($event)
            );

            $event->addFluidVariable('actionName', $event->getActionName());
            $event->addFluidVariable('paginator', $paginator);
            $event->addFluidVariable('days', $paginator->getPaginatedItems());
            $event->addFluidVariable('pagination', new GetPostPagination($paginator));
        }
    }

    protected function getCurrentPage(PostProcessFluidVariablesEvent $event): int
    {
        $currentPage = 1;
        if ($event->getRequest()->hasArgument('currentPage')) {
            $currentPage = $event->getRequest()->getArgument('currentPage');
        }
        return (int)$currentPage;
    }

    protected function getItemsPerPage(PostProcessFluidVariablesEvent $event): int
    {
        $itemsPerPage = $this->itemsPerPage;
        if (isset($event->getSettings()['pageBrowser']['itemsPerPage'])) {
            $itemsPerPage = $event->getSettings()['pageBrowser']['itemsPerPage'];
        }
        return (int)$itemsPerPage;
    }
}
