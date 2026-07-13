<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Pagination;

use JWeiland\Events2\Pagination\GetPostPagination;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\PaginatorInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for GetPostPagination
 */
class GetPostPaginationTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'phpTimeZone' => Events2Constants::PHP_TIMEZONE,
        ],
    ];

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);

        parent::tearDown();
    }

    private function buildPaginator(int $currentPageNumber = 1, int $itemsPerPage = 10, int $itemCount = 25): PaginatorInterface
    {
        return new ArrayPaginator(range(1, $itemCount), $currentPageNumber, $itemsPerPage);
    }

    private function setRequest(array $queryParams = [], array $parsedBody = []): void
    {
        $request = new ServerRequest('https://www.example.com/', 'GET');
        $request = $request->withQueryParams($queryParams);
        $request = $request->withParsedBody($parsedBody);

        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    #[Test]
    public function constructorWithoutAnyRequestArgumentsResultsInEmptyArguments(): void
    {
        $this->setRequest();

        $subject = new GetPostPagination($this->buildPaginator());

        self::assertSame(
            [
                'currentPage' => 1,
            ],
            $subject->getFirstPageArguments(),
        );
    }

    #[Test]
    public function constructorMergesGetArgumentsFromListNamespace(): void
    {
        $this->setRequest([
            'tx_events2_list' => [
                'organizer' => '5',
            ],
        ]);

        $subject = new GetPostPagination($this->buildPaginator());

        self::assertSame(
            [
                'organizer' => '5',
                'currentPage' => 1,
            ],
            $subject->getFirstPageArguments(),
        );
    }

    #[Test]
    public function constructorMergesPostArgumentsOverridingGetArgumentsFromListNamespace(): void
    {
        $this->setRequest(
            [
                'tx_events2_list' => [
                    'organizer' => '5',
                ],
            ],
            [
                'tx_events2_list' => [
                    'organizer' => '7',
                ],
            ],
        );

        $subject = new GetPostPagination($this->buildPaginator());

        self::assertSame(
            [
                'organizer' => '7',
                'currentPage' => 1,
            ],
            $subject->getFirstPageArguments(),
        );
    }

    #[Test]
    public function constructorMergesGetAndPostArgumentsFromSearchResultsNamespace(): void
    {
        $this->setRequest(
            [
                'tx_events2_searchresults' => [
                    'search' => 'foo',
                ],
            ],
            [
                'tx_events2_searchresults' => [
                    'eventType' => 'bar',
                ],
            ],
        );

        $subject = new GetPostPagination($this->buildPaginator());

        self::assertSame(
            [
                'search' => 'foo',
                'eventType' => 'bar',
                'currentPage' => 1,
            ],
            $subject->getFirstPageArguments(),
        );
    }

    #[Test]
    public function constructorOnlyUsesListNamespaceWhenBothPluginNamespacesArePopulated(): void
    {
        // Simulates two plugins (List and SearchResults) being embedded on the same page.
        // As only one plugin namespace is ever populated with real request data, the class
        // must not merge both namespaces into one argument set.
        $this->setRequest([
            'tx_events2_list' => [
                'organizer' => '5',
            ],
            'tx_events2_searchresults' => [
                'search' => 'foo',
            ],
        ]);

        $subject = new GetPostPagination($this->buildPaginator());

        self::assertSame(
            [
                'organizer' => '5',
                'currentPage' => 1,
            ],
            $subject->getFirstPageArguments(),
        );
    }

    #[Test]
    public function constructorOnlyUsesSearchResultsNamespaceWhenListNamespaceIsEmpty(): void
    {
        $this->setRequest([
            'tx_events2_list' => [],
            'tx_events2_searchresults' => [
                'search' => 'foo',
            ],
        ]);

        $subject = new GetPostPagination($this->buildPaginator());

        self::assertSame(
            [
                'search' => 'foo',
                'currentPage' => 1,
            ],
            $subject->getFirstPageArguments(),
        );
    }

    #[Test]
    public function constructorSkipsArgumentsStartingWithDoubleUnderscore(): void
    {
        $this->setRequest([
            'tx_events2_list' => [
                'organizer' => '5',
                '__referrer' => [
                    '@action' => 'list',
                ],
            ],
        ]);

        $subject = new GetPostPagination($this->buildPaginator());

        self::assertSame(
            [
                'organizer' => '5',
                'currentPage' => 1,
            ],
            $subject->getFirstPageArguments(),
        );
    }

    #[Test]
    public function constructorSkipsFrameworkControlArguments(): void
    {
        $this->setRequest([
            'tx_events2_list' => [
                'organizer' => '5',
                '@extension' => 'Events2',
                '@subpackage' => '',
                '@controller' => 'Day',
                '@action' => 'list',
                '@format' => 'html',
                'extension' => 'events2',
                'plugin' => 'list',
                'controller' => 'Day',
                'action' => 'list',
            ],
        ]);

        $subject = new GetPostPagination($this->buildPaginator());

        self::assertSame(
            [
                'organizer' => '5',
                'currentPage' => 1,
            ],
            $subject->getFirstPageArguments(),
        );
    }

    #[Test]
    public function getPreviousPageNumberReturnsNullOnFirstPage(): void
    {
        $this->setRequest();

        $subject = new GetPostPagination($this->buildPaginator(1));

        self::assertNull($subject->getPreviousPageNumber());
    }

    #[Test]
    public function getPreviousPageNumberReturnsPageNumberWhenNotOnFirstPage(): void
    {
        $this->setRequest();

        $subject = new GetPostPagination($this->buildPaginator(2));

        self::assertSame(1, $subject->getPreviousPageNumber());
    }

    #[Test]
    public function getPreviousPageArgumentsKeepsFilterArgumentsAndSetsCurrentPage(): void
    {
        $this->setRequest([
            'tx_events2_list' => [
                'organizer' => '5',
            ],
        ]);

        $subject = new GetPostPagination($this->buildPaginator(2));

        self::assertSame(
            [
                'organizer' => '5',
                'currentPage' => 1,
            ],
            $subject->getPreviousPageArguments(),
        );
    }

    #[Test]
    public function getNextPageNumberReturnsNullOnLastPage(): void
    {
        $this->setRequest();

        $subject = new GetPostPagination($this->buildPaginator(3));

        self::assertNull($subject->getNextPageNumber());
    }

    #[Test]
    public function getNextPageNumberReturnsPageNumberWhenNotOnLastPage(): void
    {
        $this->setRequest();

        $subject = new GetPostPagination($this->buildPaginator(2));

        self::assertSame(3, $subject->getNextPageNumber());
    }

    #[Test]
    public function getNextPageArgumentsKeepsFilterArgumentsAndSetsCurrentPage(): void
    {
        $this->setRequest([
            'tx_events2_searchresults' => [
                'search' => 'foo',
            ],
        ]);

        $subject = new GetPostPagination($this->buildPaginator(2));

        self::assertSame(
            [
                'search' => 'foo',
                'currentPage' => 3,
            ],
            $subject->getNextPageArguments(),
        );
    }

    #[Test]
    public function getFirstPageNumberReturns1(): void
    {
        $this->setRequest();

        $subject = new GetPostPagination($this->buildPaginator(2));

        self::assertSame(1, $subject->getFirstPageNumber());
    }

    #[Test]
    public function getLastPageNumberReturnsNumberOfPages(): void
    {
        $this->setRequest();

        $subject = new GetPostPagination($this->buildPaginator(2));

        self::assertSame(3, $subject->getLastPageNumber());
    }

    #[Test]
    public function getLastPageArgumentsKeepsFilterArgumentsAndSetsCurrentPage(): void
    {
        $this->setRequest([
            'tx_events2_list' => [
                'organizer' => '5',
            ],
        ]);

        $subject = new GetPostPagination($this->buildPaginator(2));

        self::assertSame(
            [
                'organizer' => '5',
                'currentPage' => 3,
            ],
            $subject->getLastPageArguments(),
        );
    }

    #[Test]
    public function getStartRecordNumberReturnsFirstRecordNumberOfCurrentPage(): void
    {
        $this->setRequest();

        $subject = new GetPostPagination($this->buildPaginator(2));

        self::assertSame(11, $subject->getStartRecordNumber());
    }

    #[Test]
    public function getStartRecordNumberReturnsZeroIfCurrentPageExceedsNumberOfPages(): void
    {
        $this->setRequest();

        $paginatorMock = $this->createMock(PaginatorInterface::class);
        $paginatorMock->expects(self::atLeastOnce())->method('getCurrentPageNumber')->willReturn(5);
        $paginatorMock->expects(self::atLeastOnce())->method('getNumberOfPages')->willReturn(3);

        $subject = new GetPostPagination($paginatorMock);

        self::assertSame(0, $subject->getStartRecordNumber());
    }

    #[Test]
    public function getEndRecordNumberReturnsLastRecordNumberOfCurrentPage(): void
    {
        $this->setRequest();

        $subject = new GetPostPagination($this->buildPaginator(2));

        self::assertSame(20, $subject->getEndRecordNumber());
    }

    #[Test]
    public function getEndRecordNumberReturnsZeroIfCurrentPageExceedsNumberOfPages(): void
    {
        $this->setRequest();

        $paginatorMock = $this->createMock(PaginatorInterface::class);
        $paginatorMock->expects(self::atLeastOnce())->method('getCurrentPageNumber')->willReturn(5);
        $paginatorMock->expects(self::atLeastOnce())->method('getNumberOfPages')->willReturn(3);

        $subject = new GetPostPagination($paginatorMock);

        self::assertSame(0, $subject->getEndRecordNumber());
    }

    #[Test]
    public function getAllPageNumbersReturnsRangeFromFirstToLastPage(): void
    {
        $this->setRequest();

        $subject = new GetPostPagination($this->buildPaginator(2));

        self::assertSame([1, 2, 3], $subject->getAllPageNumbers());
    }
}
