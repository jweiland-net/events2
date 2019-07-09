<?php

namespace JWeiland\Events2\Tests\Unit\Backend\FormDataProvider;

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

use JWeiland\Events2\Backend\FormDataProvider\InitializeNewEventRecord;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test case.
 */
class InitializeNewEventRecordTest extends UnitTestCase
{
    /**
     * @var InitializeNewEventRecord
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = new InitializeNewEventRecord();

        $GLOBALS['EXEC_TIME'] = time();
    }

    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function addDataWithWrongTableNameWillNotModifyResult()
    {
        $result = [
            'tableName' => 'WhatEver'
        ];

        $this->assertSame(
            $result,
            $this->subject->addData($result)
        );
    }

    /**
     * @test
     */
    public function addDataWillNotModifyResultIfRecordIsNotNew()
    {
        $result = [
            'tableName' => 'tx_events2_domain_model_event',
            'command' => 'edit'
        ];

        $this->assertSame(
            $result,
            $this->subject->addData($result)
        );
    }

    /**
     * @test
     */
    public function addDataWithEventTableAndNewRecordWillModifyEventBegin()
    {
        $expected = $result = [
            'tableName' => 'tx_events2_domain_model_event',
            'command' => 'new'
        ];

        $expected['databaseRow'] = [
            'event_begin' => $GLOBALS['EXEC_TIME']
        ];

        $this->assertSame(
            $expected,
            $this->subject->addData($result)
        );
    }
}
