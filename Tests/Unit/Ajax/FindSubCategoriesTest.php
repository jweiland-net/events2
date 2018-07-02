<?php

namespace JWeiland\Events2\Tests\Unit\Ajax;

/*
 * This file is part of the events2 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use JWeiland\Events2\Ajax\FindSubCategories;
use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Repository\CategoryRepository;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class FindSubCategoriesTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Ajax\FindSubCategories
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = new FindSubCategories();
    }

    public function tearDown()
    {
        unset($this->subject);
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
        $category1->setIcon('fileadmin/img1.jpg');
        $categories[] = $category1;
        $category2 = new Category();
        $category2->_setProperty('uid', 456);
        $category2->setTitle('Audi');
        $category1->setDescription('bar');
        $category1->setIcon('fileadmin/img2.jpg');
        $categories[] = $category2;

        /* @var QueryResult|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface $categories */
        $queryResult = $this->getAccessibleMock(QueryResult::class, ['dummy'], [new Query('fooBarType')]);
        $queryResult->_set('queryResult', $categories);

        /** @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject $categoryRepository */
        $categoryRepository = $this
            ->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $categoryRepository->expects($this->once())->method('getSubCategories')->with(284)->will($this->returnValue($queryResult));

        $this->subject->injectCategoryRepository($categoryRepository);

        $this->assertSame(
            '{"123":"BMW","456":"Audi"}',
            $this->subject->processAjaxRequest(['category' => '284']) // test if case to int works
        );
    }
}
