<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Controller;

use JWeiland\Events2\Ajax\FindSubCategories;
use JWeiland\Events2\Controller\AjaxController;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Test case.
 */
class AjaxControllerTest extends FunctionalTestCase
{
    /**
     * @var AjaxController
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

        $this->subject = new AjaxController();
    }

    public function tearDown()
    {
        unset($this->subject);

        parent::tearDown();
    }

    /**
     * @test
     */
    public function callAjaxObjectActionWithEmptyObjectNameResultsEmptyString()
    {
        self::assertEmpty(
            $this->subject->callAjaxObjectAction('')
        );
    }

    /**
     * @test
     */
    public function callAjaxObjectActionWithLowerCasedObjectNameWillBeConvertedToUcFirst()
    {
        /** @var ObjectManager|ObjectProphecy $objectManager */
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager
            ->get(Arguments::class)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());
        $objectManager
            ->get(FindSubCategories::class)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());

        $this->subject->injectObjectManager($objectManager->reveal());
        self::assertEmpty(
            $this->subject->callAjaxObjectAction('findSubCategories', [])
        );
    }

    /**
     * @test
     */
    public function callAjaxObjectActionWithValidObjectNameAndArgumentsResultsWithJsonOutput()
    {
        $arguments = ['foo', 'bar'];
        $expectedResult = '[{"123":"foo"}]';

        /** @var FindSubCategories|ObjectProphecy $findSubCategories */
        $findSubCategories = $this->prophesize(FindSubCategories::class);
        $findSubCategories
            ->processAjaxRequest($arguments)
            ->shouldBeCalled()
            ->willReturn($expectedResult);

        /** @var ObjectManager|ObjectProphecy $objectManager */
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager
            ->get(Arguments::class)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());
        $objectManager
            ->get(FindSubCategories::class)
            ->shouldBeCalled()
            ->willReturn($findSubCategories);

        $this->subject->injectObjectManager($objectManager->reveal());
        self::assertSame(
            $expectedResult,
            $this->subject->callAjaxObjectAction('FindSubCategories', $arguments)
        );
    }
}
