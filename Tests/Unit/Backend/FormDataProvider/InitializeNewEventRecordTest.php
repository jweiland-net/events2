<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Backend\FormDataProvider;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Context\Context;
use JWeiland\Events2\Backend\FormDataProvider\InitializeNewEventRecord;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test case.
 */
class InitializeNewEventRecordTest extends UnitTestCase
{
    protected InitializeNewEventRecord $subject;

    protected function setUp(): void
    {
        $this->subject = new InitializeNewEventRecord();
    }

    protected function tearDown(): void
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function addDataWithWrongTableNameWillNotModifyResult(): void
    {
        $result = [
            'tableName' => 'WhatEver'
        ];

        self::assertSame(
            $result,
            $this->subject->addData($result)
        );
    }

    /**
     * @test
     */
    public function addDataWillNotModifyResultIfRecordIsNotNew(): void
    {
        $result = [
            'tableName' => 'tx_events2_domain_model_event',
            'command' => 'edit'
        ];

        self::assertSame(
            $result,
            $this->subject->addData($result)
        );
    }

    /**
     * @test
     */
    public function addDataWithEventTableAndNewRecordWillModifyEventBegin(): void
    {
        $expected = $result = [
            'tableName' => 'tx_events2_domain_model_event',
            'command' => 'new'
        ];

        $expected['databaseRow'] = [
            'event_begin' => GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp')
        ];

        self::assertSame(
            $expected,
            $this->subject->addData($result)
        );
    }
}
