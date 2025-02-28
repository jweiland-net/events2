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
use JWeiland\Events2\Traits\IsValidEventListenerRequestTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Pagination\PaginationInterface;
use TYPO3\CMS\Core\Pagination\PaginatorInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;

/**
 * Add paginator to fluid template
 */
#[AsEventListener('events2/addPaginator')]
final readonly class AddPaginatorEventListener
{
    use IsValidEventListenerRequestTrait;

    private const ITEMS_PER_PAGE = 15;

    /**
     * Fluid variable name for paginated records
     */
    private const FLUID_VARIABLE_NAME = 'days';

    private const FALLBACK_CLASS = GetPostPagination::class;

    protected const ALLOWED_CONTROLLER_ACTIONS = [
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
            && ($controllerActionEvent->getFluidVariables()[self::FLUID_VARIABLE_NAME] ?? null)
            && ($controllerActionEvent->getSettings()['listType'] ?? 'listLatest') !== 'listLatest'
        ) {
            $paginator = new QueryResultPaginator(
                $controllerActionEvent->getFluidVariables()[self::FLUID_VARIABLE_NAME],
                $this->getCurrentPage($controllerActionEvent),
                $this->getItemsPerPage($controllerActionEvent),
            );

            $controllerActionEvent->addFluidVariable('actionName', $controllerActionEvent->getActionName());
            $controllerActionEvent->addFluidVariable('paginator', $paginator);
            $controllerActionEvent->addFluidVariable(
                self::FLUID_VARIABLE_NAME,
                $paginator->getPaginatedItems(),
            );
            $controllerActionEvent->addFluidVariable(
                'pagination',
                $this->getPagination($controllerActionEvent, $paginator),
            );
        }
    }

    private function getCurrentPage(PostProcessFluidVariablesEvent $controllerActionEvent): int
    {
        $currentPage = 1;
        if ($controllerActionEvent->getRequest()->hasArgument('currentPage')) {
            // $currentPage have to be positive and greater than 0
            // See: AbstractPaginator::setCurrentPageNumber()
            $currentPage = MathUtility::forceIntegerInRange(
                (int)$controllerActionEvent->getRequest()->getArgument('currentPage'),
                1,
            );
        }

        return $currentPage;
    }

    private function getItemsPerPage(PostProcessFluidVariablesEvent $event): int
    {
        return (int)($event->getSettings()['pageBrowser']['itemsPerPage'] ?? self::ITEMS_PER_PAGE);
    }

    private function getPagination(
        PostProcessFluidVariablesEvent $event,
        PaginatorInterface $paginator,
    ): PaginationInterface {
        $paginationClass = $event->getSettings()['pageBrowser']['class'] ?? self::FALLBACK_CLASS;

        if (!class_exists($paginationClass)) {
            $paginationClass = self::FALLBACK_CLASS;
        }

        if (!is_subclass_of($paginationClass, PaginationInterface::class)) {
            $paginationClass = self::FALLBACK_CLASS;
        }

        return GeneralUtility::makeInstance($paginationClass, $paginator);
    }
}
