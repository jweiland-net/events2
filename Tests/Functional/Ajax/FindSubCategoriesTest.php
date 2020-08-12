<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Ajax;

use JWeiland\Events2\Ajax\FindSubCategories;
use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Repository\CategoryRepository;
use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

/**
 * Test case.
 */
class FindSubCategoriesTest extends FunctionalTestCase
{
    /**
     * @var \JWeiland\Events2\Ajax\FindSubCategories
     */
    protected $subject;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2'
    ];

    public function setUp()
    {
        parent::setUp();

        $this->subject = new FindSubCategories();
    }

    public function tearDown()
    {
        unset($this->subject);
        parent::tearDown();
    }

    /**
     * this test also tests the protected method reduceCategoryData.
     *
     * @test
     */
    public function processAjaxRequest()
    {
        $categories = [];
        $category1 = new Category();
        $category1->_setProperty('uid', 123);
        $category1->setTitle('BMW');
        $category1->setDescription('foo');
        $categories[] = $category1;
        $category2 = new Category();
        $category2->_setProperty('uid', 456);
        $category2->setTitle('Audi');
        $category1->setDescription('bar');
        $categories[] = $category2;

        /* @var QueryResult|\PHPUnit_Framework_MockObject_MockObject|AccessibleMockObjectInterface $categories */
        $queryResult = $this->getAccessibleMock(QueryResult::class, ['dummy'], [new Query('fooBarType')]);
        $queryResult->_set('queryResult', $categories);

        /** @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject $categoryRepository */
        $categoryRepository = $this
            ->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $categoryRepository->expects(self::once())->method('getSubCategories')->with(284)->willReturn($queryResult);

        $this->subject->injectCategoryRepository($categoryRepository);

        self::assertSame(
            '{"123":"BMW","456":"Audi"}',
            $this->subject->processAjaxRequest(['category' => '284']) // test if case to int works
        );
    }
}
