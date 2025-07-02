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
use JWeiland\Events2\Service\DayGeneratorService;
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Service\Record\DayRecordService;
use JWeiland\Events2\Service\Record\EventRecordService;
use JWeiland\Events2\Service\Record\ExceptionRecordService;
use JWeiland\Events2\Service\Result\DayGeneratorResult;
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
    protected DayRelationService $subject;

    protected ExtConf $extConf;

    protected DayGeneratorService|MockObject $dayGeneratorServiceMock;

    protected DayRecordService|MockObject $dayRecordServiceMock;

    protected EventRecordService|MockObject $eventRecordServiceMock;

    protected ExceptionRecordService|MockObject $exceptionRecordServiceMock;

    protected ReferenceIndex|MockObject $referenceIndexMock;

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

        $this->dayGeneratorServiceMock = $this->createMock(DayGeneratorService::class);
        $this->dayRecordServiceMock = $this->createMock(DayRecordService::class);
        $this->eventRecordServiceMock = $this->createMock(EventRecordService::class);
        $this->exceptionRecordServiceMock = $this->createMock(ExceptionRecordService::class);
        $this->referenceIndexMock = $this->createMock(ReferenceIndex::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->subject = new DayRelationService(
            $this->dayGeneratorServiceMock,
            $this->dayRecordServiceMock,
            $this->eventRecordServiceMock,
            $this->exceptionRecordServiceMock,
            $this->referenceIndexMock,
            $this->loggerMock,
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->referenceIndexMock,
            $this->exceptionRecordServiceMock,
            $this->eventRecordServiceMock,
            $this->dayRecordServiceMock,
            $this->timeServiceMock,
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function createDayRelationsWithMissingRecordWillSkipProcess(): void
    {
        $this->loggerMock
            ->expects(self::atLeastOnce())
            ->method('warning')
            ->willReturnMap([
                [self::stringContains('Event record could not be found'), null],
            ]);

        $this->dayRecordServiceMock
            ->expects(self::never())
            ->method('removeAllByEventUid');

        $this->subject->createDayRelations(123);
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
            ]
        );

        $this->dayRecordServiceMock
            ->expects(self::never())
            ->method('removeAllByEventUid');

        $this->subject->createDayRelations(123);
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
            ]
        );

        $this->dayRecordServiceMock
            ->expects(self::never())
            ->method('removeAllByEventUid');

        $this->subject->createDayRelations(123);
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
            ]
        );

        $eventRecord = $connection->select(
            ['*'],
            'tx_events2_domain_model_event',
            [
                'uid' => 123,
            ]
        )->fetchAssociative();
        $eventRecord['exceptions'] = [];

        $dayRecords = [
            [
                'uid' => 1,
                'pid' => 1,
            ]
        ];

        $dayGeneratorResult = new DayGeneratorResult($eventRecord);
        $dayGeneratorResult->addDayRecords($dayRecords);

        $this->dayGeneratorServiceMock
            ->expects(self::once())
            ->method('getDateTimeStorageForEventRecord')
            ->with(self::identicalTo($eventRecord))
            ->willReturn($dayGeneratorResult);

        $this->eventRecordServiceMock
            ->expects(self::once())
            ->method('getLanguageUidsOfTranslatedEventRecords')
            ->with(self::identicalTo($eventRecord))
            ->willReturn([1, 2]);

        $this->exceptionRecordServiceMock
            ->expects(self::once())
            ->method('findAllByEventUid')
            ->with(self::identicalTo(123))
            ->willReturn([]);

        $this->dayRecordServiceMock
            ->expects(self::once())
            ->method('removeAllByEventUid')
            ->with(self::identicalTo(123));

        $this->dayRecordServiceMock
            ->expects(self::once())
            ->method('bulkInsertAllDayRecords')
            ->with(
                self::identicalTo($dayRecords),
                self::identicalTo(123),
                self::identicalTo([1, 2]),
            );

        $this->subject->createDayRelations(123);
    }
}
