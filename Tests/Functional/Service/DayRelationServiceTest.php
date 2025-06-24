<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Service;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Service\Record\EventRecordService;
use JWeiland\Events2\Service\Record\ExceptionRecordService;
use JWeiland\Events2\Service\DayGeneratorService;
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Service\Record\DayRecordService;
use JWeiland\Events2\Service\Result\DayGeneratorResult;
use JWeiland\Events2\Service\TimeService;
use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for class \JWeiland\Events2\Service\DayRelationService
 */
class DayRelationServiceTest extends FunctionalTestCase
{
    protected DayRelationService $subject;

    protected ExtConf $extConf;

    protected DayGeneratorService|MockObject $dayGeneratorServiceMock;

    protected DayRecordService|MockObject $dayRecordServiceMock;

    protected EventRecordService|MockObject $eventRecordServiceMock;

    protected ExceptionRecordService|MockObject $exceptionRecordServiceMock;

    protected TimeService|MockObject $timeServiceMock;

    protected LoggerInterface|MockObject $loggerMock;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->extConf = new ExtConf(
            recurringPast: 3,
            recurringFuture: 6,
        );

        $this->dayGeneratorServiceMock = $this->createMock(DayGeneratorService::class);
        $this->dayRecordServiceMock = $this->createMock(DayRecordService::class);
        $this->eventRecordServiceMock = $this->createMock(EventRecordService::class);
        $this->exceptionRecordServiceMock = $this->createMock(ExceptionRecordService::class);
        $this->timeServiceMock = $this->createMock(TimeService::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->subject = new DayRelationService(
            $this->dayGeneratorServiceMock,
            $this->dayRecordServiceMock,
            $this->eventRecordServiceMock,
            $this->exceptionRecordServiceMock,
            $this->timeServiceMock,
            new DateTimeUtility(),
            $this->loggerMock,
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->extConf,
            $this->exceptionRecordServiceMock,
            $this->eventRecordServiceMock,
            $this->dayRecordServiceMock,
            $this->timeServiceMock,
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function createDayRelationsWithEmptyRecordWillNotAddDays(): void
    {
        $this->eventRecordServiceMock
            ->expects(self::atLeastOnce())
            ->method('findByUid')
            ->with(self::identicalTo(123))
            ->willReturn([]);

        $this->loggerMock
            ->expects(self::atLeastOnce())
            ->method('warning')
            ->willReturnMap([
                [self::stringContains('Event record could not be found'), null],
            ]);

        self::assertSame(
            [],
            $this->subject->createDayRelations(123),
        );
    }

    #[Test]
    public function createDayRelationsWithMissingEventColumnsWillNotAddDays(): void
    {
        $this->eventRecordServiceMock
            ->expects(self::atLeastOnce())
            ->method('findByUid')
            ->with(self::identicalTo(123))
            ->willReturn([
                'uid' => 123,
                'event_type' => 'single',
            ]);

        $this->loggerMock
            ->expects(self::atLeastOnce())
            ->method('error')
            ->willReturnMap([
                [self::stringContains('Missing required columns'), null],
            ]);

        self::assertSame(
            [],
            $this->subject->createDayRelations(123),
        );
    }

    #[Test]
    public function createDayRelationsWithInvalidEventWillNotAddDays(): void
    {
        $this->eventRecordServiceMock
            ->expects(self::atLeastOnce())
            ->method('findByUid')
            ->with(self::identicalTo(123))
            ->willReturn([
                'uid' => 123,
                'event_type' => 'single',
                'sys_language_uid' => 2,
            ]);

        self::assertSame(
            [],
            $this->subject->createDayRelations(123),
        );
    }

    #[Test]
    public function createDayRelationsWillAddDays(): void
    {
        $dayRecord = [
            'uid' => 321,
        ];

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'single',
            'sys_language_uid' => 0,
        ];

        $dayGeneratorResult = new DayGeneratorResult($eventRecord);
        $dayGeneratorResult->addDayRecords($dayRecord);

        $this->dayRecordServiceMock
            ->expects(self::atLeastOnce())
            ->method('removeAllByEventRecord');

        $this->dayGeneratorServiceMock
            ->expects(self::atLeastOnce())
            ->method('getDateTimeStorageForEventRecord')
            ->willReturn($dayGeneratorResult);

        $this->dayRecordServiceMock
            ->expects(self::atLeastOnce())
            ->method('bulkInsertAllDayRecords');

        $this->eventRecordServiceMock
            ->expects(self::atLeastOnce())
            ->method('findByUid')
            ->with(self::identicalTo(123))
            ->willReturn($eventRecord);

        self::assertSame(
            array_merge(
                $eventRecord,
                [
                    'exceptions' => [],
                    'days' => $dayRecord,
                ]
            ),
            $this->subject->createDayRelations(123),
        );
    }
}
