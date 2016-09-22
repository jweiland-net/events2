<?php

namespace JWeiland\Events2\Tests\Unit\Configuration;

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
