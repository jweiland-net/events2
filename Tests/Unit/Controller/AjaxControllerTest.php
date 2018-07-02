<?php

namespace JWeiland\Events2\Tests\Unit\Controller;

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
use JWeiland\Events2\Controller\AjaxController;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class AjaxControllerTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Controller\AjaxController
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = new AjaxController();
    }

    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * data provider for callAjaxObjectActionWithInvalidObjectNameResultsInEmptyString.
     *
     * @return array
     */
    public function dataProviderForInvalidObjectNames()
    {
        $objectNames = [];
        $objectNames['integer'] = [123];
        $objectNames['object'] = [new \stdClass()];
        $objectNames['array'] = [['foo', 'bar']];
        $objectNames['non existing Class'] = ['fooBar'];

        return $objectNames;
    }

    /**
     * @test
     *
     * @dataProvider dataProviderForInvalidObjectNames
     *
     * @param mixed $objectName
     */
    public function callAjaxObjectActionWithInvalidObjectNameResultsInEmptyString($objectName)
    {
        $this->assertEmpty($this->subject->callAjaxObjectAction($objectName, []));
    }

    /**
     * @test
     */
    public function callAjaxObjectActionWithLowerCasedObjectNameWillBeConvertedToUcFirst()
    {
        /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $objectManager */
        $objectManager = $this->getMockBuilder(ObjectManager::class)->getMock();
        // ->get will be called within injectObjectManager the first time
        $objectManager->expects($this->at(0))->method('get')->with($this->equalTo(Arguments::class))->will($this->returnValue(new \stdClass()));
        // now we can configure method get for FindSubCategories
        $objectManager->expects($this->at(1))->method('get')->with($this->equalTo(FindSubCategories::class))->will($this->returnValue(new \stdClass()));

        $this->subject->injectObjectManager($objectManager);
        $this->assertEmpty($this->subject->callAjaxObjectAction('findSubCategories', []));
    }

    /**
     * @test
     */
    public function callAjaxObjectActionWithValidObjectNameAndArgumentsResultsWithJsonOutput()
    {
        $arguments = ['foo', 'bar'];
        $expectedResult = '[{"123":"foo"}]';

        /** @var FindSubCategories|\PHPUnit_Framework_MockObject_MockObject $findSubCategories */
        $findSubCategories = $this->getMockBuilder(FindSubCategories::class)->setMethods(['processAjaxRequest'])->getMock();
        $findSubCategories->expects($this->once())->method('processAjaxRequest')->with($arguments)->will($this->returnValue($expectedResult));

        /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $objectManager */
        $objectManager = $this->getMockBuilder(ObjectManager::class)->getMock();
        // ->get will be called within injectObjectManager the first time
        $objectManager->expects($this->at(0))->method('get')->with($this->equalTo(Arguments::class))->will($this->returnValue(new \stdClass()));
        // now we can configure method get for FindSubCategories
        $objectManager->expects($this->at(1))->method('get')->with($this->equalTo(FindSubCategories::class))->will($this->returnValue($findSubCategories));

        $this->subject->injectObjectManager($objectManager);
        $this->assertSame(
            $expectedResult,
            $this->subject->callAjaxObjectAction('FindSubCategories', $arguments)
        );
    }
}
