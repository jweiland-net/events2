<?php

namespace JWeiland\Events2\Tests\Unit\ViewHelpers\Widget\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Stefan Froemken <projects@jweiland.net>, jweiland.net
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use JWeiland\Events2\ViewHelpers\Widget\Controller\PaginateController;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\Statement;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class PaginateControllerTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\ViewHelpers\Widget\Controller\PaginateController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            PaginateController::class,
            array('getCount', 'prepareObjectsSlice'),
            array(),
            '',
            false
        );
        $this->subject->_set('view', $this->getMock(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface::class));
    }

    /**
     * tear down.
     */
    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|Statement|Query|QueryResult
     */
    protected function getObjects()
    {
        /** @var Statement|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $objects */
        $statement = $this->getAccessibleMock(Statement::class, array('dummy'), array(), '', false);
        $statement->_set('statement', 'SELECT');
        /** @var Query|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $objects */
        $query = $this->getAccessibleMock(Query::class, array('dummy'), array(), '', false);
        $query->_set('statement', $statement);
        /** @var QueryResult|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $objects */
        $objects = $this->getAccessibleMock(QueryResult::class, array('dummy'), array(), '', false);
        $objects->_set('query', $query);

        return $objects;
    }

    /**
     * @test
     */
    public function subjectHasDefaultValues()
    {
        $configuration = $this->subject->_get('configuration');

        $this->assertSame(6, count($configuration));
        $this->assertSame(15, $configuration['itemsPerPage']);
        $this->assertSame(false, $configuration['insertAbove']);
        $this->assertSame(true, $configuration['insertBelow']);
        $this->assertSame(99, $configuration['maximumNumberOfLinks']);
        $this->assertSame('POST,GET', $configuration['addQueryStringMethod']);
        $this->assertSame('', $configuration['section']);
    }

    /**
     * @test
     */
    public function initializeActionMergesWidgetConfigurationAndSetsDefaults()
    {
        $widgetConfiguration = array(
            'configuration' => array(
                'itemsPerPage' => 20,
                'insertBelow' => false,
                'section' => 'test'
            ),
            'objects' => $this->getObjects()
        );
        $this->subject->_set('widgetConfiguration', $widgetConfiguration);
        $this->subject->initializeAction();

        $configuration = $this->subject->_get('configuration');

        // test merge configuration
        $this->assertSame(20, $configuration['itemsPerPage']);
        $this->assertSame(false, $configuration['insertAbove']);
        $this->assertSame(false, $configuration['insertBelow']);
        $this->assertSame(99, $configuration['maximumNumberOfLinks']);
        $this->assertSame('POST,GET', $configuration['addQueryStringMethod']);
        $this->assertSame('test', $configuration['section']);
    }

    /**
     * @test
     */
    public function initializeActionSetDefaults()
    {
        $objects = $this->getObjects();
        $widgetConfiguration = array(
            'configuration' => array(),
            'objects' => $objects
        );
        $this->subject->_set('widgetConfiguration', $widgetConfiguration);
        $this->subject->expects($this->once())->method('getCount')->willReturn(47);
        $this->subject->initializeAction();

        $this->assertSame($objects, $this->subject->_get('objects'));
        $this->assertSame('SELECT', $this->subject->_get('originalStatement'));
        $this->assertSame((float)4, $this->subject->_get('numberOfPages'));
        $this->assertSame(99, $this->subject->_get('maximumNumberOfLinks'));
    }

    /**
     * @test
     */
    public function indexActionWithNoArgumentSetsCurrentPageTo1()
    {
        $this->subject->indexAction();
        $this->assertSame(
            1,
            $this->subject->_get('currentPage')
        );
    }

    /**
     * @test
     */
    public function indexActionWithCurrentPageSmallerThan1SetsCurrentPageTo1()
    {
        $this->subject->indexAction(-3);
        $this->assertSame(
            1,
            $this->subject->_get('currentPage')
        );
    }

    /**
     * @test
     */
    public function indexActionWithCurrentPageSetsCurrentPage()
    {
        $this->subject->indexAction(12);
        $this->assertSame(
            12,
            $this->subject->_get('currentPage')
        );
    }

    /**
     * @test
     */
    public function indexActionWithCurrentPageHigherThanNumberOfPagesWillNotCallPrepareObjectsSlice()
    {
        $this->subject->expects($this->never())->method('prepareObjectsSlice');
        $this->subject->indexAction(5);
        $this->assertSame(
            5,
            $this->subject->_get('currentPage')
        );
    }

    /**
     * @test
     */
    public function indexActionWillCallPrepareObjectsSliceWithItemsPerPage()
    {
        $widgetConfiguration = array(
            'configuration' => array(),
            'objects' => $this->getObjects()
        );
        $this->subject->_set('widgetConfiguration', $widgetConfiguration);

        $this->subject->expects($this->once())->method('getCount')->willReturn(47);
        $this->subject->expects($this->once())->method('prepareObjectsSlice')->with(
            $this->equalTo(15),
            $this->equalTo(30)
        )->willReturn(array());
        $this->subject->initializeAction();
        $this->subject->indexAction(3);
    }
    /**
     * @test
     */
    public function indexActionWillCallPrepareObjectsSliceWithMaxRecords()
    {
        $widgetConfiguration = array(
            'configuration' => array(),
            'maxRecords' => 3,
            'objects' => $this->getObjects()
        );
        $this->subject->_set('widgetConfiguration', $widgetConfiguration);

        $this->subject->expects($this->once())->method('getCount')->willReturn(47);
        $this->subject->expects($this->once())->method('prepareObjectsSlice')->with(
            $this->equalTo(3),
            $this->equalTo(3)
        )->willReturn(array());
        $this->subject->initializeAction();
        $this->subject->indexAction(2);
    }
}
