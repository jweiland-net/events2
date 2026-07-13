<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Pagination;

use JWeiland\Events2\Traits\Typo3RequestTrait;
use TYPO3\CMS\Core\Pagination\PaginationInterface;
use TYPO3\CMS\Core\Pagination\PaginatorInterface;

/**
 * This Pagination respects also existing POST data from search form to keep filter while
 * navigation through result pages.
 *
 * This class is shared by the "List" plugin (Day::list, filtered by organizer, argument
 * namespace "tx_events2_list") and the "SearchResults" plugin (Search::listSearchResults,
 * argument namespace "tx_events2_searchresults"). Both namespaces have to be merged here,
 * as only one of them is populated at a time, depending on which plugin rendered the
 * current page.
 */
class GetPostPagination implements PaginationInterface
{
    use Typo3RequestTrait;

    protected const PLUGIN_NAMESPACES = [
        'tx_events2_list',
        'tx_events2_searchresults',
    ];

    protected array $arguments = [];

    public function __construct(protected readonly PaginatorInterface $paginator)
    {
        foreach (self::PLUGIN_NAMESPACES as $pluginNamespace) {
            $getMergedWithPost = $this->getMergedWithPostFromRequest($pluginNamespace);

            if ($getMergedWithPost === []) {
                continue;
            }

            foreach ($getMergedWithPost as $argumentName => $argument) {
                if ($argumentName[0] === '_' && $argumentName[1] === '_') {
                    continue;
                }
                if (in_array($argumentName, ['@extension', '@subpackage', '@controller', '@action', '@format'], true)) {
                    continue;
                }
                if (in_array($argumentName, ['extension', 'plugin', 'controller', 'action'], true)) {
                    continue;
                }

                $this->arguments[$argumentName] = $argument;
            }

            // Only one plugin namespace is ever populated per request, so stop here to
            // guarantee $this->arguments can never be filled from more than one of them.
            break;
        }
    }

    public function getPreviousPageNumber(): ?int
    {
        $previousPage = $this->paginator->getCurrentPageNumber() - 1;

        if ($previousPage > $this->paginator->getNumberOfPages()) {
            return null;
        }

        return $previousPage >= $this->getFirstPageNumber() ? $previousPage : null;
    }

    public function getPreviousPageArguments(): ?array
    {
        $arguments = $this->arguments;
        $arguments['currentPage'] = $this->getPreviousPageNumber();

        return $arguments;
    }

    public function getNextPageNumber(): ?int
    {
        $nextPage = $this->paginator->getCurrentPageNumber() + 1;

        return $nextPage <= $this->paginator->getNumberOfPages() ? $nextPage : null;
    }

    public function getNextPageArguments(): ?array
    {
        $arguments = $this->arguments;
        $arguments['currentPage'] = $this->getNextPageNumber();

        return $arguments;
    }

    public function getFirstPageNumber(): int
    {
        return 1;
    }

    public function getFirstPageArguments(): ?array
    {
        $arguments = $this->arguments;
        $arguments['currentPage'] = $this->getFirstPageNumber();

        return $arguments;
    }

    public function getLastPageNumber(): int
    {
        return $this->paginator->getNumberOfPages();
    }

    public function getLastPageArguments(): ?array
    {
        $arguments = $this->arguments;
        $arguments['currentPage'] = $this->getLastPageNumber();

        return $arguments;
    }

    public function getStartRecordNumber(): int
    {
        if ($this->paginator->getCurrentPageNumber() > $this->paginator->getNumberOfPages()) {
            return 0;
        }

        return $this->paginator->getKeyOfFirstPaginatedItem() + 1;
    }

    public function getEndRecordNumber(): int
    {
        if ($this->paginator->getCurrentPageNumber() > $this->paginator->getNumberOfPages()) {
            return 0;
        }

        return $this->paginator->getKeyOfLastPaginatedItem() + 1;
    }

    public function getAllPageNumbers(): array
    {
        return range($this->getFirstPageNumber(), $this->getLastPageNumber());
    }
}
