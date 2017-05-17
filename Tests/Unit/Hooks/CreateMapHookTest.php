<?php

namespace JWeiland\Events2\Tests\Unit\Hooks;

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
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Hooks\CreateMap;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class CreateMapTest extends UnitTestCase
{
    /**
     * @var CreateMap|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface
     */
    protected $subject;
    
    /**
     * @var DatabaseConnection|ObjectProphecy
     */
    protected $dbProphecy;
    
    public function setUp()
    {
        $this->dbProphecy = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $this->dbProphecy->reveal();
        $this->subject = $this->getAccessibleMock(
            CreateMap::class,
            array('dummy')
        );
    }
    
    public function tearDown()
    {
        unset($this->subject);
    }
    
    /**
     * @test
     */
    public function getAddressWithAllValues()
    {
        $this->dbProphecy->exec_SELECTgetSingleRow('cn_short_en', 'static_countries', 'uid=85')
            ->shouldBeCalled()
            ->willReturn([
                'cn_short_en' => 'Germany'
            ]);
        $this->subject->_set('currentRecord', array(
            'uid' => 123,
            'street' => 'Echterdinger Straße',
            'house_number' => '57',
            'zip' => 70794,
            'city' => 'Filderstadt',
            'country' => 85
        
        ));
        $this->assertSame(
            'Echterdinger Straße 57 70794 Filderstadt Germany',
            $this->subject->getAddress()
        );
    }

    /**
     * @test
     */
    public function getAddressWithNonGivenCountry()
    {
        $this->subject->_set('currentRecord', array(
            'uid' => 123,
            'street' => 'Echterdinger Straße',
            'house_number' => '57',
            'zip' => 70794,
            'city' => 'Filderstadt'
        
        ));
        $this->assertSame(
            'Echterdinger Straße 57 70794 Filderstadt',
            $this->subject->getAddress()
        );
    }
    
    /**
     * @test
     */
    public function getAddressWithHouseNumberInStreet()
    {
        $this->subject->_set('currentRecord', array(
            'uid' => 123,
            'street' => 'Echterdinger Straße 57',
            'zip' => 70794,
            'city' => 'Filderstadt'
        
        ));
        $this->assertSame(
            'Echterdinger Straße 57 70794 Filderstadt',
            $this->subject->getAddress()
        );
    }
}
