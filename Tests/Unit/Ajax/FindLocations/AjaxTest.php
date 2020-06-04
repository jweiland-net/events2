<?php

namespace JWeiland\Events2\Tests\Unit\Ajax\FindLocations;

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
use JWeiland\Events2\Ajax\FindLocations;
use JWeiland\Events2\Tests\Unit\Utility\AccessibleProxies\ExtensionManagementUtilityAccessibleProxy;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Category\CategoryRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case.
 */
class AjaxTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Ajax\FindLocations\Ajax
     */
    protected $subject;

    /**
     * @var PhpFrontend|ObjectProphecy
     */
    protected $phpFrontendProphecy;

    /**
     * @var CacheManager|ObjectProphecy
     */
    protected $cacheManagerProphecy;

    /**
     * set up.
     */
    public function setUp()
    {
        $cacheData = [
            'categoryRegistry' => serialize(new CategoryRegistry())
        ];

        $this->phpFrontendProphecy = $this->prophesize(PhpFrontend::class);
        if (version_compare(TYPO3_branch, '9.0', '>=')) {
            $this->phpFrontendProphecy->require(Argument::any())->shouldBeCalled()->willReturn($cacheData);
        } else {
            $this->phpFrontendProphecy->requireOnce(Argument::any())->shouldBeCalled()->willReturn(true);
        }

        /** @var CacheManager|ObjectProphecy $cacheManagerProphecy */
        $this->cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $this->cacheManagerProphecy
            ->getCache('cache_core')
            ->shouldBeCalled()
            ->willReturn($this->phpFrontendProphecy->reveal());
        GeneralUtility::setSingletonInstance(CacheManager::class, $this->cacheManagerProphecy->reveal());

        $this->subject = new FindLocations\Ajax();
        $GLOBALS['TYPO3_LOADED_EXT'] = [
            'events2' => []
        ];
    }

    /**
     * tear down.
     */
    public function tearDown()
    {
        parent::tearDown();
        ExtensionManagementUtilityAccessibleProxy::setCacheManager(null);
        unset($this->subject);
    }

    /**
     * @test
     */
    public function processAjaxRequestWithNoLocationsReturnsEmptyString()
    {
        $arguments = [
            'search' => '',
        ];
        /** @var FindLocations\Ajax|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this
            ->getMockBuilder(FindLocations\Ajax::class)
            ->setMethods(['findLocations'])
            ->getMock();
        $subject->expects($this->never())->method('findLocations');
        $this->assertSame(
            '',
            $subject->processAjaxRequest($arguments)
        );
    }

    /**
     * @test
     */
    public function processAjaxRequestWithHtmlCallsFindLocationsWithoutHtml()
    {
        $arguments = [
            'search' => 'Hello german umlauts: öäü. <b>How are you?</b>',
        ];
        $expectedArgument = 'Hello german umlauts: öäü. How are you?';
        /** @var FindLocations\Ajax|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this
            ->getMockBuilder(FindLocations\Ajax::class)
            ->setMethods(['findLocations'])
            ->getMock();
        $subject->expects($this->once())->method('findLocations')->with($expectedArgument)->will($this->returnValue([]));
        $this->assertSame(
            '[]',
            $subject->processAjaxRequest($arguments)
        );
    }

    /**
     * @test
     */
    public function processAjaxRequestWithTooSmallLocationsReturnsEmptyString()
    {
        $arguments = [
            'search' => 'x',
        ];
        /** @var FindLocations\Ajax|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this
            ->getMockBuilder(FindLocations\Ajax::class)
            ->setMethods(['findLocations'])
            ->getMock();
        $subject->expects($this->never())->method('findLocations');
        $this->assertSame(
            '',
            $subject->processAjaxRequest($arguments)
        );
    }

    /**
     * @test
     */
    public function processAjaxRequestWithLocationsReturnsJson()
    {
        $locationMap = [
            [
                'at h',
                [
                    [
                        'uid' => 123,
                        'label' => 'at home',
                    ],
                ],
            ],
            [
                'mar',
                [
                    [
                        'uid' => 234,
                        'label' => 'Marienheide',
                    ],
                    [
                        'uid' => 345,
                        'label' => 'Marienhagen',
                    ],
                ],
            ],
        ];
        /** @var FindLocations\Ajax|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this
            ->getMockBuilder(FindLocations\Ajax::class)
            ->setMethods(['findLocations'])
            ->getMock();
        $subject->expects($this->exactly(2))->method('findLocations')->will($this->returnValueMap($locationMap));
        $this->assertSame(
            '[{"uid":123,"label":"at home"}]',
            $subject->processAjaxRequest(['search' => 'at h'])
        );
        $this->assertSame(
            '[{"uid":234,"label":"Marienheide"},{"uid":345,"label":"Marienhagen"}]',
            $subject->processAjaxRequest(['search' => 'mar'])
        );
    }
}
