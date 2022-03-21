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

    public function __invoke(PostProcessFluidVariablesEvent $controllerActionEvent): void
    {
        // Do not show pagination for listLatest
        if (
            $this->isValidRequest($controllerActionEvent)
            && ($days = $controllerActionEvent->getFluidVariables()['days'] ?? null)
            && $days !== null
            && ($controllerActionEvent->getSettings()['listType'] ?? 'listLatest') !== 'listLatest'
        ) {
            $paginator = new QueryResultPaginator(
                $controllerActionEvent->getFluidVariables()['days'],
                $this->getCurrentPage($controllerActionEvent),
                $this->getItemsPerPage($controllerActionEvent)
            );

            $controllerActionEvent->addFluidVariable('actionName', $controllerActionEvent->getActionName());
            $controllerActionEvent->addFluidVariable('paginator', $paginator);
            $controllerActionEvent->addFluidVariable('days', $paginator->getPaginatedItems());
            $controllerActionEvent->addFluidVariable('pagination', new GetPostPagination($paginator));
        }
    }

    protected function getCurrentPage(PostProcessFluidVariablesEvent $controllerActionEvent): int
    {
        $currentPage = 1;
        if ($controllerActionEvent->getRequest()->hasArgument('currentPage')) {
            $currentPage = $controllerActionEvent->getRequest()->getArgument('currentPage');
        }
        return (int)$currentPage;
    }

    protected function getItemsPerPage(PostProcessFluidVariablesEvent $controllerActionEvent): int
    {
        $itemsPerPage = $this->itemsPerPage;
        if (isset($controllerActionEvent->getSettings()['pageBrowser']['itemsPerPage'])) {
            $itemsPerPage = $controllerActionEvent->getSettings()['pageBrowser']['itemsPerPage'];
        }
        return (int)$itemsPerPage;
    }
}
