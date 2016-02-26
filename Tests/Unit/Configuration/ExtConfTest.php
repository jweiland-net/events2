<?php

namespace JWeiland\Events2\Tests\Unit\Configuration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Stefan Froemken <projects@jweiland.net>, jweiland.net
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
use TYPO3\CMS\Core\Tests\UnitTestCase;
use JWeiland\Events2\Configuration\ExtConf;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class ExtConfTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Configuration\ExtConf
     */
    protected $subject;

    protected $backupExtConfOfEvents = '';

    /**
     * set up.
     */
    public function setUp()
    {
        $this->backupExtConfOfEvents = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['events2'];
        $newExtConfForEvents = array(
            'recurringPast' => '3',
            'recurringFuture' => '6',
        );
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['events2'] = serialize($newExtConfForEvents);
        $this->subject = new ExtConf();
    }

    /**
     * tear down.
     */
    public function tearDown()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['events2'] = $this->backupExtConfOfEvents;
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getRecurringPastReturns3monthAsDefault()
    {
        $this->assertSame(
            3,
            $this->subject->getRecurringPast()
        );
    }

    /**
     * @test
     */
    public function setRecurringPastWithIntegerWillReturnSameInGetter()
    {
        $this->subject->setRecurringPast(6);
        $this->assertSame(
            6,
            $this->subject->getRecurringPast()
        );
    }

    /**
     * @test
     */
    public function setRecurringPastWithStringWillReturnIntegerInGetter()
    {
        $this->subject->setRecurringPast('6');
        $this->assertSame(
            6,
            $this->subject->getRecurringPast()
        );
    }

    /**
     * @test
     */
    public function setRecurringPastWithInvalidValueWillReturnDefaultValueInGetter()
    {
        $this->subject->setRecurringPast('invalidValue');
        $this->assertSame(
            3,
            $this->subject->getRecurringPast()
        );
    }

    /**
     * @test
     */
    public function getRecurringFutureReturns6monthAsDefault()
    {
        $this->assertSame(
            6,
            $this->subject->getRecurringFuture()
        );
    }

    /**
     * @test
     */
    public function setRecurringFutureWithIntegerWillReturnSameInGetter()
    {
        $this->subject->setRecurringFuture(12);
        $this->assertSame(
            12,
            $this->subject->getRecurringFuture()
        );
    }

    /**
     * @test
     */
    public function setRecurringFutureWithStringWillReturnIntegerInGetter()
    {
        $this->subject->setRecurringFuture('12');
        $this->assertSame(
            12,
            $this->subject->getRecurringFuture()
        );
    }

    /**
     * @test
     */
    public function setRecurringFutureWithInvalidValueWillReturnDefaultValueInGetter()
    {
        $this->subject->setRecurringFuture('invalidValue');
        $this->assertSame(
            6,
            $this->subject->getRecurringFuture()
        );
    }
}
