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
use TYPO3\CMS\Core\Pagination\PaginationInterface;
use TYPO3\CMS\Core\Pagination\PaginatorInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;

/**
 * Add paginator to fluid template
 */
class AddPaginatorEventListener extends AbstractControllerEventListener
{
    protected int $itemsPerPage = 15;

    /**
     * Fluid variable name for paginated records
     */
    protected string $fluidVariableForPaginatedRecords = 'days';

    protected string $fallbackPaginationClass = GetPostPagination::class;

    protected array $allowedControllerActions = [
        'Day' => [
            'list',
        ],
        'Search' => [
            'listSearchResults',
        ],
    ];

    public function __invoke(PostProcessFluidVariablesEvent $controllerActionEvent): void
    {
        // Do not show pagination for listLatest
        if (
            $this->isValidRequest($controllerActionEvent)
            && ($controllerActionEvent->getFluidVariables()[$this->fluidVariableForPaginatedRecords] ?? null)
            && ($controllerActionEvent->getSettings()['listType'] ?? 'listLatest') !== 'listLatest'
        ) {
            $paginator = new QueryResultPaginator(
                $controllerActionEvent->getFluidVariables()[$this->fluidVariableForPaginatedRecords],
                $this->getCurrentPage($controllerActionEvent),
                $this->getItemsPerPage($controllerActionEvent)
            );

            $controllerActionEvent->addFluidVariable('actionName', $controllerActionEvent->getActionName());
            $controllerActionEvent->addFluidVariable('paginator', $paginator);
            $controllerActionEvent->addFluidVariable(
                $this->fluidVariableForPaginatedRecords,
                $paginator->getPaginatedItems()
            );
            $controllerActionEvent->addFluidVariable(
                'pagination',
                $this->getPagination($controllerActionEvent, $paginator)
            );
        }
    }

    protected function getCurrentPage(PostProcessFluidVariablesEvent $controllerActionEvent): int
    {
        $currentPage = 1;
        if ($controllerActionEvent->getRequest()->hasArgument('currentPage')) {
            // $currentPage have to be positive and greater than 0
            // See: AbstractPaginator::setCurrentPageNumber()
            $currentPage = MathUtility::forceIntegerInRange(
                (int)$controllerActionEvent->getRequest()->getArgument('currentPage'),
                1
            );
        }

        return $currentPage;
    }

    protected function getItemsPerPage(PostProcessFluidVariablesEvent $event): int
    {
        return (int)($event->getSettings()['pageBrowser']['itemsPerPage'] ?? $this->itemsPerPage);
    }

    protected function getPagination(
        PostProcessFluidVariablesEvent $event,
        PaginatorInterface $paginator
    ): PaginationInterface {
        $paginationClass = $event->getSettings()['pageBrowser']['class'] ?? $this->fallbackPaginationClass;

        if (!class_exists($paginationClass)) {
            $paginationClass = $this->fallbackPaginationClass;
        }

        if (!is_subclass_of($paginationClass, PaginationInterface::class)) {
            $paginationClass = $this->fallbackPaginationClass;
        }

        return GeneralUtility::makeInstance($paginationClass, $paginator);
    }
}
