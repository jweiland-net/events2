<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Backend\FormDataProvider;

use JWeiland\Events2\Backend\FormDataProvider\InitializeNewEventRecord;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class InitializeNewEventRecordTest extends UnitTestCase
{
    protected InitializeNewEventRecord $subject;

    /**
     * This tests works with Context class which is a Singleton. These must be deleted while tearDown.
     */
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        date_default_timezone_set(Events2Constants::PHP_TIMEZONE);

        $this->subject = new InitializeNewEventRecord(
            GeneralUtility::makeInstance(Context::class),
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function addDataWithWrongTableNameWillNotModifyResult(): void
    {
        $result = [
            'tableName' => 'WhatEver',
        ];

        self::assertSame(
            $result,
            $this->subject->addData($result),
        );
    }

    #[Test]
    public function addDataWillNotModifyResultIfRecordIsNotNew(): void
    {
        $result = [
            'tableName' => 'tx_events2_domain_model_event',
            'command' => 'edit',
        ];

        self::assertSame(
            $result,
            $this->subject->addData($result),
        );
    }

    #[Test]
    public function addDataWithEventTableAndNewRecordWillModifyEventBegin(): void
    {
        $result = [
            'tableName' => 'tx_events2_domain_model_event',
            'command' => 'new',
        ];

        $expected = \DateTimeImmutable::createFromInterface(
            GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'full'),
        )->setTime(0, 0, 0);

        self::assertEquals(
            $expected,
            $this->subject->addData($result)['databaseRow']['event_begin'],
        );
    }

    #[Test]
    public function addDataWillPrefillEventBeginWithDateTimeObject(): void
    {
        $result = [
            'tableName' => 'tx_events2_domain_model_event',
            'command' => 'new',
        ];

        // Since TYPO3 14 DatetimeElement requires a \DateTimeInterface here.
        // An int timestamp lets the "new event" form fail with #1731132127.
        self::assertInstanceOf(
            \DateTimeInterface::class,
            $this->subject->addData($result)['databaseRow']['event_begin'],
        );
    }
}
