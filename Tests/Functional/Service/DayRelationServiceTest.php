<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Service;

use JWeiland\Events2\Service\DayGeneratorService;
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Service\Record\DayRecordService;
use JWeiland\Events2\Service\Record\EventRecordService;
use JWeiland\Events2\Service\Record\ExceptionRecordService;
use JWeiland\Events2\Service\Result\DayGeneratorResult;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for class \JWeiland\Events2\Service\DayRelationService
 */
class DayRelationServiceTest extends FunctionalTestCase
{
    protected DayGeneratorService|MockObject $dayGeneratorServiceMock;

    protected ReferenceIndex|MockObject $referenceIndexMock;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'phpTimeZone' => Events2Constants::PHP_TIMEZONE,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->referenceIndexMock = $this->createStub(ReferenceIndex::class);
    }

    protected function tearDown(): void
    {
        unset(
            $this->referenceIndexMock,
        );

        parent::tearDown();
    }

    #[Test]
    public function createDayRelationsWithMissingRecordWillSkipProcess(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects(self::atLeastOnce())
            ->method('warning')
            ->willReturnMap([
                [self::stringContains('Event record could not be found'), null],
            ]);

        $dayRecordServiceMock = $this->createMock(DayRecordService::class);
        $dayRecordServiceMock
            ->expects(self::never())
            ->method('removeAllByEventUid');

        $subject = new DayRelationService(
            $this->createStub(DayGeneratorService::class),
            $dayRecordServiceMock,
            $this->createStub(EventRecordService::class),
            $this->createStub(ExceptionRecordService::class),
            $this->referenceIndexMock,
            $loggerMock,
        );

        $subject->createDayRelations(123);
    }

    #[Test]
    public function createDayRelationsWithEmptyEventTypeWillSkipProcess(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_event');
        $connection->insert(
            'tx_events2_domain_model_event',
            [
                'uid' => 123,
                'event_type' => '',
            ],
        );

        $dayRecordServiceMock = $this->createMock(DayRecordService::class);
        $dayRecordServiceMock
            ->expects(self::never())
            ->method('removeAllByEventUid');

        $subject = new DayRelationService(
            $this->createStub(DayGeneratorService::class),
            $dayRecordServiceMock,
            $this->createStub(EventRecordService::class),
            $this->createStub(ExceptionRecordService::class),
            $this->referenceIndexMock,
            $this->createStub(Logger::class),
        );

        $subject->createDayRelations(123);
    }

    #[Test]
    public function createDayRelationsWithTranslatedEventWillSkipProcess(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_event');
        $connection->insert(
            'tx_events2_domain_model_event',
            [
                'uid' => 123,
                'sys_language_uid' => 3,
            ],
        );

        $dayRecordServiceMock = $this->createMock(DayRecordService::class);
        $dayRecordServiceMock
            ->expects(self::never())
            ->method('removeAllByEventUid');

        $subject = new DayRelationService(
            $this->createStub(DayGeneratorService::class),
            $dayRecordServiceMock,
            $this->createStub(EventRecordService::class),
            $this->createStub(ExceptionRecordService::class),
            $this->referenceIndexMock,
            $this->createStub(Logger::class),
        );

        $subject->createDayRelations(123);
    }

    #[Test]
    public function createDayRelationsWillAddDays(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_event');
        $connection->insert(
            'tx_events2_domain_model_event',
            [
                'uid' => 123,
                'event_type' => 'single',
                'sys_language_uid' => 0,
            ],
        );

        $eventRecord = $connection->select(
            ['*'],
            'tx_events2_domain_model_event',
            [
                'uid' => 123,
            ],
        )->fetchAssociative();
        $eventRecord['exceptions'] = [];

        $dayRecords = [
            [
                'uid' => 1,
                'pid' => Events2Constants::PAGE_STORAGE,
            ],
        ];

        $dayGeneratorResult = new DayGeneratorResult($eventRecord);
        $dayGeneratorResult->addDayRecords($dayRecords);

        $dayGeneratorServiceMock = $this->createMock(DayGeneratorService::class);
        $dayGeneratorServiceMock
            ->expects(self::once())
            ->method('getDayGeneratorResultForEventRecord')
            ->with(self::identicalTo($eventRecord))
            ->willReturn($dayGeneratorResult);

        $eventRecordServiceMock = $this->createMock(EventRecordService::class);
        $eventRecordServiceMock
            ->expects(self::once())
            ->method('getLanguageUidsOfTranslatedEventRecords')
            ->with(self::identicalTo($eventRecord))
            ->willReturn([1, 2]);

        $exceptionRecordServiceMock = $this->createMock(ExceptionRecordService::class);
        $exceptionRecordServiceMock
            ->expects(self::once())
            ->method('findAllByEventUid')
            ->with(self::identicalTo(123))
            ->willReturn([]);

        $dayRecordServiceMock = $this->createMock(DayRecordService::class);
        $dayRecordServiceMock
            ->expects(self::once())
            ->method('removeAllByEventUid')
            ->with(self::identicalTo(123));

        $dayRecordServiceMock
            ->expects(self::once())
            ->method('bulkInsertAllDayRecords')
            ->with(
                self::identicalTo($dayRecords),
                self::identicalTo(123),
                self::identicalTo([1, 2]),
            );

        $subject = new DayRelationService(
            $dayGeneratorServiceMock,
            $dayRecordServiceMock,
            $eventRecordServiceMock,
            $exceptionRecordServiceMock,
            $this->referenceIndexMock,
            $this->createStub(Logger::class),
        );

        $subject->createDayRelations(123);
    }
}
