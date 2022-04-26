<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Domain\Repository;

use JWeiland\Events2\Domain\Repository\CategoryRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case.
 */
class CategoryRepositoryTest extends FunctionalTestCase
{
    use ProphecyTrait;

    protected CategoryRepository $subject;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2'
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/../../Fixtures/sys_category.xml');

        $this->subject = GeneralUtility::makeInstance(CategoryRepository::class);
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function getCategoriesWithEmptyStringWillReturnQueryResultWithNoCategories(): void
    {
        $queryResult = $this->subject->getCategories('');

        self::assertSame(
            [],
            $queryResult->toArray()
        );
    }

    /**
     * @test
     */
    public function getCategoriesWithOneUidWillReturnQueryResultWithOneCategories(): void
    {
        $queryResult = $this->subject->getCategories('2');
        $categories = $queryResult->toArray();

        self::assertCount(
            1,
            $categories
        );

        $category = current($categories);
        self::assertSame(
            'Audi',
            $category->getTitle()
        );
    }

    /**
     * @test
     */
    public function getCategoriesWithTwoUidsWillReturnQueryResultWithTwoCategories(): void
    {
        $queryResult = $this->subject->getCategories('2,3');
        $categories = $queryResult->toArray();

        self::assertCount(
            2,
            $categories
        );

        $category = current($categories);
        self::assertSame(
            'Audi',
            $category->getTitle()
        );
        $category = next($categories);
        self::assertSame(
            'BMW',
            $category->getTitle()
        );
    }

    /**
     * @test
     */
    public function getCategoriesWithMultipleUidsWillReturnQueryResultWithSortedCategories(): void
    {
        $queryResult = $this->subject->getCategories('1,2,3');
        $categories = $queryResult->toArray();

        self::assertCount(
            3,
            $categories
        );

        $category = current($categories);
        self::assertSame(
            'Audi',
            $category->getTitle()
        );
        $category = next($categories);
        self::assertSame(
            'BMW',
            $category->getTitle()
        );
        $category = next($categories);
        self::assertSame(
            'Cars',
            $category->getTitle()
        );
    }

    /**
     * @test
     */
    public function getSubCategoriesWithZeroWillReturnQueryResultWithRootCategory(): void
    {
        $queryResult = $this->subject->getSubCategories(0);
        $categories = $queryResult->toArray();

        self::assertCount(
            1,
            $categories
        );

        $category = current($categories);
        self::assertSame(
            'Cars',
            $category->getTitle()
        );
    }

    /**
     * @test
     */
    public function getSubCategoriesWithOneWillReturnQueryResultWithSubCategories(): void
    {
        $queryResult = $this->subject->getSubCategories(1);
        $categories = $queryResult->toArray();

        self::assertCount(
            2,
            $categories
        );

        $category = current($categories);
        self::assertSame(
            'Audi',
            $category->getTitle()
        );
        $category = next($categories);
        self::assertSame(
            'BMW',
            $category->getTitle()
        );
    }

    /**
     * @test
     */
    public function getSelectedCategoriesWillNotSelectCategoriesByInvalidValues(): void
    {
        $queryResult = $this->subject->getSelectedCategories('two,,0');

        self::assertSame(
            [],
            $queryResult->toArray()
        );
    }

    /**
     * @test
     */
    public function getSelectedCategoriesWillSelectRootCategory(): void
    {
        $queryResult = $this->subject->getSelectedCategories('1');
        $categories = $queryResult->toArray();

        self::assertCount(
            1,
            $categories
        );

        $category = current($categories);
        self::assertSame(
            'Cars',
            $category->getTitle()
        );
    }

    /**
     * @test
     */
    public function getSelectedCategoriesWillSelectOneSubCategory(): void
    {
        $queryResult = $this->subject->getSelectedCategories('3', 1);
        $categories = $queryResult->toArray();

        self::assertCount(
            1,
            $categories
        );

        $category = current($categories);
        self::assertSame(
            'BMW',
            $category->getTitle()
        );
    }

    /**
     * @test
     */
    public function getSelectedCategoriesWillNotFindSubCategoriesOnRootLevel(): void
    {
        $queryResult = $this->subject->getSelectedCategories('2,3');
        $categories = $queryResult->toArray();

        self::assertCount(
            0,
            $categories
        );
    }
}
