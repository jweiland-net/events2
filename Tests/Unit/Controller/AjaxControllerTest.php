<?php

namespace JWeiland\Events2\Tests\Unit\Controller;

/*
 * This file is part of the TYPO3 CMS project.
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
use JWeiland\Events2\Controller\AjaxController;
use TYPO3\CMS\Core\Tests\UnitTestCase;

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
        $objectNames = array();
        $objectNames['integer'] = array(123);
        $objectNames['object'] = array(new \stdClass());
        $objectNames['array'] = array(array('foo', 'bar'));
        $objectNames['non existing Class'] = array('fooBar');

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
        $this->assertEmpty($this->subject->callAjaxObjectAction($objectName, array()));
    }

    /**
     * @test
     */
    public function callAjaxObjectActionWithLowerCasedObjectNameWillBeConvertedToUcFirst()
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager|\PHPUnit_Framework_MockObject_MockObject $objectManager */
        $objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        // ->get will be called within injectObjectManager the first time
        $objectManager->expects($this->at(0))->method('get')->with($this->equalTo('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Arguments'))->will($this->returnValue(new \stdClass()));
        // now we can configure method get for FindSubCategories
        $objectManager->expects($this->at(1))->method('get')->with($this->equalTo('JWeiland\\Events2\\Ajax\\FindSubCategories'))->will($this->returnValue(new \stdClass()));

        $this->subject->injectObjectManager($objectManager);
        $this->assertEmpty($this->subject->callAjaxObjectAction('findSubCategories', array()));
    }

    /**
     * @test
     */
    public function callAjaxObjectActionWithValidObjectNameAndArgumentsResultsWithJsonOutput()
    {
        $arguments = array('foo', 'bar');
        $expectedResult = '[{"123":"foo"}]';

        /** @var \JWeiland\Events2\Ajax\FindSubCategories|\PHPUnit_Framework_MockObject_MockObject $findSubCategories */
        $findSubCategories = $this->getMock('JWeiland\\Events2\\Ajax\\FindSubCategories', array('processAjaxRequest'));
        $findSubCategories->expects($this->once())->method('processAjaxRequest')->with($arguments)->will($this->returnValue($expectedResult));

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager|\PHPUnit_Framework_MockObject_MockObject $objectManager */
        $objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        // ->get will be called within injectObjectManager the first time
        $objectManager->expects($this->at(0))->method('get')->with($this->equalTo('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Arguments'))->will($this->returnValue(new \stdClass()));
        // now we can configure method get for FindSubCategories
        $objectManager->expects($this->at(1))->method('get')->with($this->equalTo('JWeiland\\Events2\\Ajax\\FindSubCategories'))->will($this->returnValue($findSubCategories));

        $this->subject->injectObjectManager($objectManager);
        $this->assertSame(
            $expectedResult,
            $this->subject->callAjaxObjectAction('FindSubCategories', $arguments)
        );
    }
}
