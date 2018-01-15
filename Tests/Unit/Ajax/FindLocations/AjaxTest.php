<?php

namespace JWeiland\Events2\Tests\Unit\Ajax\FindLocations;

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
use JWeiland\Events2\Ajax\FindLocations;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class AjaxTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Ajax\FindLocations\Ajax
     */
    protected $subject;

    /**
     * @var DatabaseConnection
     */
    protected $dbProphecy;

    public function setUp()
    {
        $this->subject = new FindLocations\Ajax();
        $this->dbProphecy = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $this->dbProphecy->reveal();
        $GLOBALS['TYPO3_LOADED_EXT'] = [
            'events2' => []
        ];
    }

    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function processAjaxRequestWithNoLocationsReturnsEmptyString()
    {
        $arguments = [
            'locationPart' => '',
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
            'locationPart' => 'Hello german umlauts: öäü. <b>How are you?</b>',
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
            'locationPart' => 'x',
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
            $subject->processAjaxRequest(['locationPart' => 'at h'])
        );
        $this->assertSame(
            '[{"uid":234,"label":"Marienheide"},{"uid":345,"label":"Marienhagen"}]',
            $subject->processAjaxRequest(['locationPart' => 'mar'])
        );
    }
}
