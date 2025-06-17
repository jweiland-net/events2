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
use JWeiland\Events2\Domain\Model\DateTimeEntry;
use JWeiland\Events2\Domain\Repository\TimeRepository;
use JWeiland\Events2\Service\DayGeneratorService;
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Service\Record\DayRecordService;
use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for class \JWeiland\Events2\Service\DayRelationService
 */
class DayRelationServiceTest extends FunctionalTestCase
{
    protected DayRelationService $subject;

    protected ExtConf $extConf;

    /**
     * @var DayGeneratorService|MockObject
     */
    protected $dayGeneratorServiceMock;

    /**
     * @var DayRecordService|MockObject
     */
    protected $dayRecordServiceMock;

    /**
     * @var TimeRepository|MockObject
     */
    protected $timeRepositoryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    protected array $eventRecord = [
        'uid' => 123,
        'pid' => 321,
        'hidden' => 0,
        'deleted' => 0,
        'sys_language_uid' => 0,
        'event_type' => 'single',
        'exceptions' => 0,
    ];

    protected array $exceptionRecord = [
        'uid' => 123,
        'pid' => 321,
        'hidden' => 0,
        'deleted' => 0,
        'sys_language_uid' => 0,
        'exception_type' => 'Time',
        'exception_date' => 0,
        'exception_time' => 0,
        'event' => 0,
    ];

    protected array $timeRecord = [
        'uid' => 1,
        'pid' => 321,
        'hidden' => 0,
        'type' => 'event_time',
        'weekday' => 'monday',
        'time_begin' => '08:12',
        'event' => [],
        'exception' => [],
    ];

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
        self::markTestIncomplete('DayRelationServiceTest not updated until right now');

        parent::setUp();

        $this->extConf = GeneralUtility::makeInstance(ExtConf::class);
        $this->extConf->setRecurringPast(3);
        $this->extConf->setRecurringFuture(6);

        $this->dayGeneratorServiceMock = $this->createMock(DayGeneratorService::class);
        $this->timeRepositoryMock = $this->createMock(TimeRepository::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->dayRecordServiceMock = $this->createMock(DayRecordService::class);
        $this->dayRecordServiceMock->removeAllByEventRecord(Argument::any());
        $this->dayRecordServiceMock->bulkInsertAllDayRecords(Argument::any());

        $this->subject = new DayRelationService(
            $this->dayGeneratorServiceMock,
            $this->dayRecordServiceMock,
            $this->timeRepositoryMock,
            new DateTimeUtility(),
            $this->loggerMock,
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->extConf,
            $this->dayRecordServiceMock,
            $this->timeRepositoryMock,
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function createDayRelationsWithEmptyRecordWillAddLogBecauseOfEmptyEventRecord(): void
    {
        $this->eventRecord = [];

        $this->timeRepositoryMock
            ->getAllByEventRecord($this->eventRecord, true)
            ->shouldNotBeCalled();

        $this->loggerMock
            ->warning(Argument::containingString('Related days could not be created, because of an empty eventRecord'))
            ->shouldBeCalled();

        $this->subject->createDayRelations(123);
    }

    #[Test]
    public function createDayRelationsWithEmptyRecordWillAddLogBecauseOfInvalidEventRecord(): void
    {
        unset($this->eventRecord['event_type']);

        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_event',
            $this->eventRecord,
        );

        $this->timeRepositoryMock
            ->getAllByEventRecord($this->eventRecord, true)
            ->shouldNotBeCalled();

        $this->loggerMock
            ->info(Argument::containingString('DayRelationService will not build day records for invalid events'))
            ->shouldBeCalled();

        $this->subject->createDayRelations(123);
    }

    #[Test]
    public function createDayRelationsWithEventRecordWillAddLogBecauseOfTranslatedEventRecord(): void
    {
        $this->eventRecord['sys_language_uid'] = 2;

        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_event',
            $this->eventRecord,
        );

        $this->timeRepositoryMock
            ->getAllByEventRecord($this->eventRecord, true)
            ->shouldNotBeCalled();

        $this->loggerMock
            ->info(Argument::containingString('DayRelationService will not build day records for translated events'))
            ->shouldBeCalled();

        $this->subject->createDayRelations(123);
    }

    #[Test]
    public function createDayRelationsWithEmptyDateTimeStorageWillRemoveAddRelatedDayRecords(): void
    {
        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_event',
            $this->eventRecord,
        );

        $this->dayGeneratorServiceMock
            ->getDateTimeStorageForEvent(Argument::withEntry('uid', 123))
            ->shouldBeCalled()
            ->willReturn([]);

        $this->timeRepositoryMock
            ->getAllByEventRecord(Argument::withEntry('uid', 123), true)
            ->shouldNotBeCalled();

        self::assertSame(
            [],
            $this->subject->createDayRelations(123)['days'],
        );
    }

    #[Test]
    public function createDayRelationsWithSingleEventWithoutTimeWillAddDayWithoutTime(): void
    {
        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_event',
            $this->eventRecord,
        );

        $date = new \DateTimeImmutable('today midnight');
        $this->dayGeneratorServiceMock
            ->getDateTimeStorageForEvent(Argument::withEntry('uid', 123))
            ->shouldBeCalled()
            ->willReturn([
                (int)$date->format('U') => new DateTimeEntry($date, false),
            ]);

        $this->timeRepositoryMock
            ->getAllByEventRecord(Argument::withEntry('uid', 123), true)
            ->shouldBeCalled()
            ->willReturn([]);

        $days = $this->subject->createDayRelations(123)['days'];
        self::assertCount(
            1,
            $days,
        );

        $firstDay = current($days);
        self::assertSame(
            (int)$date->format('U'),
            $firstDay['day'],
            'Column: day - Expected: ' . $date->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $firstDay['day']),
        );
        self::assertSame(
            (int)$date->format('U'),
            $firstDay['day_time'],
            'Column: day_time - Expected: ' . $date->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $firstDay['day_time']),
        );
        self::assertSame(
            (int)$date->format('U'),
            $firstDay['sort_day_time'],
            'Column: sort_day_time - Expected: ' . $date->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $firstDay['sort_day_time']),
        );
        self::assertSame(
            (int)$date->format('U'),
            $firstDay['same_day_time'],
            'Column: same_day_time - Expected: ' . $date->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $firstDay['same_day_time']),
        );
    }

    public static function dataProviderForSimpleEventWithTimeForDifferentTypes(): array
    {
        return [
            'Test for time type "event_time"' => ['event_time', '08:00', '08:00:00'],
            'Test for time type "exception_time"' => ['exception_time', '12:20', '12:20:00'],
            'Test for time type "different_times"' => ['different_times', '15:30', '15:30:00'],
            'Test for time type "multiple_times"' => ['multiple_times', '20:15', '20:15:00'],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForSimpleEventWithTimeForDifferentTypes')]
    public function createDayRelationsWithSingleEventWithDifferentTimeTypesWillAddDayWithTime(
        string $timeType,
        string $timeBegin,
        string $dateTimeModification,
    ): void {
        $date = new \DateTimeImmutable('today midnight');

        $this->eventRecord['exceptions'] = 1;
        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_event',
            $this->eventRecord,
        );

        $this->exceptionRecord['exception_date'] = (int)$date->format('U');
        $this->exceptionRecord['event'] = $this->getDatabaseConnection()->lastInsertId();
        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_exception',
            $this->exceptionRecord,
        );

        $timeRecord = $this->timeRecord;
        $timeRecord['type'] = $timeType;
        $timeRecord['time_begin'] = $timeBegin;
        $timeRecord['weekday'] = strtolower($date->format('l'));
        $timeRecord['event'] = $this->eventRecord;
        $timeRecord['exception'] = $this->exceptionRecord;

        $this->dayGeneratorServiceMock
            ->getDateTimeStorageForEvent(Argument::withEntry('uid', 123))
            ->shouldBeCalled()
            ->willReturn([
                (int)$date->format('U') => new DateTimeEntry($date, false),
            ]);

        $this->timeRepositoryMock
            ->getAllByEventRecord(Argument::withEntry('uid', 123), true)
            ->shouldBeCalled()
            ->willReturn([$timeRecord]);

        $days = $this->subject->createDayRelations(123)['days'];
        self::assertCount(
            1,
            $days,
        );

        $firstDay = current($days);
        $dateWithTime = $date->modify($dateTimeModification);
        self::assertSame(
            (int)$date->format('U'),
            $firstDay['day'],
            'Column: day - Expected: ' . $date->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $firstDay['day']),
        );
        self::assertSame(
            (int)$dateWithTime->format('U'),
            $firstDay['day_time'],
            'Column: day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $firstDay['day_time']),
        );
        self::assertSame(
            (int)$dateWithTime->format('U'),
            $firstDay['sort_day_time'],
            'Column: sort_day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $firstDay['sort_day_time']),
        );
        self::assertSame(
            (int)$dateWithTime->format('U'),
            $firstDay['same_day_time'],
            'Column: same_day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $firstDay['same_day_time']),
        );
    }

    /**
     * If a time record is given, column day contains the day at midnight 00:00:00
     * and all other day_* - columns contains day incl. time 08:12:00
     */
    #[Test]
    public function createDayRelationsWithRecurringEventWillAddDaysWithTime(): void
    {
        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_event',
            $this->eventRecord,
        );

        $yesterday = new \DateTimeImmutable('yesterday midnight');
        $today = new \DateTimeImmutable('today midnight');
        $tomorrow = new \DateTimeImmutable('tomorrow midnight');

        $dateTimeStorage = [
            (int)$yesterday->format('U') => new DateTimeEntry($yesterday, false),
            (int)$today->format('U') => new DateTimeEntry($today, false),
            (int)$tomorrow->format('U') => new DateTimeEntry($tomorrow, false),
        ];

        $this->dayGeneratorServiceMock
            ->getDateTimeStorageForEvent(Argument::withEntry('uid', 123))
            ->shouldBeCalled()
            ->willReturn($dateTimeStorage);

        $timeRecord = $this->timeRecord;
        $timeRecord['event'] = $this->eventRecord;

        $this->timeRepositoryMock
            ->getAllByEventRecord(Argument::withEntry('uid', 123), true)
            ->shouldBeCalled()
            ->willReturn([$timeRecord]);

        $days = $this->subject->createDayRelations(123)['days'];
        self::assertCount(
            3,
            $days,
        );

        $date = $yesterday;
        foreach ($days as $day) {
            $dateWithTime = $date->modify('08:12:00');
            self::assertSame(
                (int)$date->format('U'),
                $day['day'],
                'Column: day - Expected: ' . $date->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['day']),
            );
            self::assertSame(
                (int)$dateWithTime->format('U'),
                $day['day_time'],
                'Column: day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['day_time']),
            );
            self::assertSame(
                (int)$dateWithTime->format('U'),
                $day['sort_day_time'],
                'Column: sort_day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['sort_day_time']),
            );
            self::assertSame(
                (int)$dateWithTime->format('U'),
                $day['same_day_time'],
                'Column: same_day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['same_day_time']),
            );
            $date = $date->modify('+1 day');
        }
    }

    /**
     * If multiple time records are given, column day contains the day at midnight 00:00:00
     * and all other day_* - columns contains day incl. time 08:12:00
     */
    #[Test]
    public function createDayRelationsWithRecurringEventWillAddDaysForMultipleTimes(): void
    {
        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_event',
            $this->eventRecord,
        );

        $yesterday = new \DateTimeImmutable('yesterday midnight');
        $today = new \DateTimeImmutable('today midnight');
        $tomorrow = new \DateTimeImmutable('tomorrow midnight');

        $dateTimeStorage = [
            (int)$yesterday->format('U') => new DateTimeEntry($yesterday, false),
            (int)$today->format('U') => new DateTimeEntry($today, false),
            (int)$tomorrow->format('U') => new DateTimeEntry($tomorrow, false),
        ];

        $this->dayGeneratorServiceMock
            ->getDateTimeStorageForEvent(Argument::withEntry('uid', 123))
            ->shouldBeCalled()
            ->willReturn($dateTimeStorage);

        $eventTimeRecord = $this->timeRecord;
        $eventTimeRecord['type'] = 'event_time';
        $eventTimeRecord['event'] = $this->eventRecord;
        $multipleTimesRecord = $this->timeRecord;
        $multipleTimesRecord['type'] = 'multiple_times';
        $multipleTimesRecord['time_begin'] = '15:30';
        $multipleTimesRecord['event'] = $this->eventRecord;

        $this->timeRepositoryMock
            ->getAllByEventRecord(Argument::withEntry('uid', 123), true)
            ->shouldBeCalled()
            ->willReturn([$eventTimeRecord, $multipleTimesRecord]);

        $days = $this->subject->createDayRelations(123)['days'];
        self::assertCount(
            6,
            $days,
        );

        $date = $yesterday;
        $switch = true;
        foreach ($days as $day) {
            $dateWithTime = $date->modify($switch ? '08:12:00' : '15:30:00');
            $switch = !$switch;
            self::assertSame(
                (int)$date->format('U'),
                $day['day'],
                'Column: day - Expected: ' . $date->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['day']),
            );
            self::assertSame(
                (int)$dateWithTime->format('U'),
                $day['day_time'],
                'Column: day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['day_time']),
            );
            self::assertSame(
                (int)$dateWithTime->format('U'),
                $day['sort_day_time'],
                'Column: sort_day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['sort_day_time']),
            );
            // Be careful, we have multiple time records! So this one here is day at morning 08:12
            self::assertSame(
                (int)$dateWithTime->modify('08:12:00')->format('U'),
                $day['same_day_time'],
                'Column: same_day_time - Expected: ' . $dateWithTime->modify('08:12:00')->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['same_day_time']),
            );
            if ($switch) {
                $date = $date->modify('+1 day');
            }
        }
    }

    #[Test]
    public function createDayRelationsWithRecurringEventWillNotAddHiddenTimes(): void
    {
        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_event',
            $this->eventRecord,
        );

        $yesterday = new \DateTimeImmutable('yesterday midnight');
        $today = new \DateTimeImmutable('today midnight');
        $tomorrow = new \DateTimeImmutable('tomorrow midnight');

        $dateTimeStorage = [
            (int)$yesterday->format('U') => new DateTimeEntry($yesterday, false),
            (int)$today->format('U') => new DateTimeEntry($today, false),
            (int)$tomorrow->format('U') => new DateTimeEntry($tomorrow, false),
        ];

        $this->dayGeneratorServiceMock
            ->getDateTimeStorageForEvent(Argument::withEntry('uid', 123))
            ->shouldBeCalled()
            ->willReturn($dateTimeStorage);

        $eventTimeRecord = $this->timeRecord;
        $eventTimeRecord['type'] = 'event_time';
        $eventTimeRecord['event'] = $this->eventRecord;
        $multipleTimesRecord = $this->timeRecord;
        $multipleTimesRecord['hidden'] = 1;
        $multipleTimesRecord['type'] = 'multiple_times';
        $multipleTimesRecord['time_begin'] = '15:30';
        $multipleTimesRecord['event'] = $this->eventRecord;

        $this->timeRepositoryMock
            ->getAllByEventRecord(Argument::withEntry('uid', 123), true)
            ->shouldBeCalled()
            ->willReturn([$eventTimeRecord, $multipleTimesRecord]);

        $days = $this->subject->createDayRelations(123)['days'];
        self::assertCount(
            3,
            $days,
        );

        $date = $yesterday;
        foreach ($days as $day) {
            $dateWithTime = $date->modify('08:12:00');
            self::assertSame(
                (int)$date->format('U'),
                $day['day'],
                'Column: day - Expected: ' . $date->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['day']),
            );
            self::assertSame(
                (int)$dateWithTime->format('U'),
                $day['day_time'],
                'Column: day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['day_time']),
            );
            self::assertSame(
                (int)$dateWithTime->format('U'),
                $day['sort_day_time'],
                'Column: sort_day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['sort_day_time']),
            );
            self::assertSame(
                (int)$dateWithTime->format('U'),
                $day['same_day_time'],
                'Column: same_day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['same_day_time']),
            );
            $date = $date->modify('+1 day');
        }
    }

    /**
     * Test a recurring event with different times for weekday
     * In that case day is current day at midnight
     * day_time and sort_day_time are equal
     */
    #[Test]
    public function createDayRelationsWithRecurringEventAndDifferentTimes(): void
    {
        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_event',
            $this->eventRecord,
        );

        $yesterday = new \DateTimeImmutable('yesterday midnight');
        $today = new \DateTimeImmutable('today midnight');
        $tomorrow = new \DateTimeImmutable('tomorrow midnight');

        $dateTimeStorage = [
            (int)$yesterday->format('U') => new DateTimeEntry($yesterday, false),
            (int)$today->format('U') => new DateTimeEntry($today, false),
            (int)$tomorrow->format('U') => new DateTimeEntry($tomorrow, false),
        ];

        $this->dayGeneratorServiceMock
            ->getDateTimeStorageForEvent(Argument::withEntry('uid', 123))
            ->shouldBeCalled()
            ->willReturn($dateTimeStorage);

        $eventTimeRecord = $this->timeRecord;
        $eventTimeRecord['type'] = 'event_time';
        $eventTimeRecord['event'] = $this->eventRecord;
        $differentTimesRecord = $this->timeRecord;
        $differentTimesRecord['type'] = 'different_times';
        $differentTimesRecord['weekday'] = strtolower($today->format('l'));
        $differentTimesRecord['time_begin'] = '15:30';
        $differentTimesRecord['event'] = $this->eventRecord;

        $this->timeRepositoryMock
            ->getAllByEventRecord(Argument::withEntry('uid', 123), true)
            ->shouldBeCalled()
            ->willReturn([$eventTimeRecord, $differentTimesRecord]);

        $days = $this->subject->createDayRelations(123)['days'];
        self::assertCount(
            3,
            $days,
        );

        $date = $yesterday;
        $switch = true;
        foreach ($days as $day) {
            $dateWithTime = $date->modify($switch ? '08:12:00' : '15:30:00');
            $switch = !$switch;
            self::assertSame(
                (int)$date->format('U'),
                $day['day'],
                'Column: day - Expected: ' . $date->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['day']),
            );
            self::assertSame(
                (int)$dateWithTime->format('U'),
                $day['day_time'],
                'Column: day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['day_time']),
            );
            self::assertSame(
                (int)$dateWithTime->format('U'),
                $day['sort_day_time'],
                'Column: sort_day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['sort_day_time']),
            );
            self::assertSame(
                (int)$dateWithTime->format('U'),
                $day['same_day_time'],
                'Column: same_day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['same_day_time']),
            );
            $date = $date->modify('+1 day');
        }
    }

    /**
     * Test a recurring event with exception times for one special day
     * In that case day is current day at midnight
     * day_time and sort_day_time are equal
     */
    #[Test]
    public function createDayRelationsWithRecurringEventAndExceptionTimes(): void
    {
        $yesterday = new \DateTimeImmutable('yesterday midnight');
        $today = new \DateTimeImmutable('today midnight');
        $tomorrow = new \DateTimeImmutable('tomorrow midnight');

        $this->eventRecord['exceptions'] = 1;
        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_event',
            $this->eventRecord,
        );

        $this->exceptionRecord['exception_date'] = (int)$today->format('U');
        $this->exceptionRecord['event'] = $this->getDatabaseConnection()->lastInsertId();
        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_exception',
            $this->exceptionRecord,
        );

        $dateTimeStorage = [
            (int)$yesterday->format('U') => new DateTimeEntry($yesterday, false),
            (int)$today->format('U') => new DateTimeEntry($today, false),
            (int)$tomorrow->format('U') => new DateTimeEntry($tomorrow, false),
        ];

        $this->dayGeneratorServiceMock
            ->getDateTimeStorageForEvent(Argument::withEntry('uid', 123))
            ->shouldBeCalled()
            ->willReturn($dateTimeStorage);

        $eventTimeRecord = $this->timeRecord;
        $eventTimeRecord['type'] = 'event_time';
        $eventTimeRecord['event'] = $this->eventRecord;
        $exceptionTimeRecord = $this->timeRecord;
        $exceptionTimeRecord['type'] = 'exception_time';
        $exceptionTimeRecord['time_begin'] = '15:30';
        $exceptionTimeRecord['exception'] = $this->exceptionRecord;

        $this->timeRepositoryMock
            ->getAllByEventRecord(Argument::withEntry('uid', 123), true)
            ->shouldBeCalled()
            ->willReturn([$eventTimeRecord, $exceptionTimeRecord]);

        $days = $this->subject->createDayRelations(123)['days'];
        self::assertCount(
            3,
            $days,
        );

        $date = $yesterday;
        $switch = true;
        foreach ($days as $day) {
            $dateWithTime = $date->modify($switch ? '08:12:00' : '15:30:00');
            $switch = !$switch;
            self::assertSame(
                (int)$date->format('U'),
                $day['day'],
                'Column: day - Expected: ' . $date->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['day']),
            );
            self::assertSame(
                (int)$dateWithTime->format('U'),
                $day['day_time'],
                'Column: day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['day_time']),
            );
            self::assertSame(
                (int)$dateWithTime->format('U'),
                $day['sort_day_time'],
                'Column: sort_day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['sort_day_time']),
            );
            self::assertSame(
                (int)$dateWithTime->format('U'),
                $day['same_day_time'],
                'Column: same_day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['same_day_time']),
            );
            $date = $date->modify('+1 day');
        }
    }

    /**
     * Test a recurring event with multiple exception times for one special day
     * In that case day is current day at midnight
     * day_time is current day + specified time
     * sort_day_time is current day + specified time
     */
    #[Test]
    public function createDayRelationsWithRecurringEventAndMultipleExceptionTimes(): void
    {
        $yesterday = new \DateTimeImmutable('yesterday midnight');
        $today = new \DateTimeImmutable('today midnight');
        $tomorrow = new \DateTimeImmutable('tomorrow midnight');

        $this->eventRecord['exceptions'] = 2;
        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_event',
            $this->eventRecord,
        );

        $exceptionRecord123 = $this->exceptionRecord;
        $exceptionRecord123['uid'] = 123;
        $exceptionRecord123['exception_date'] = (int)$today->format('U');
        $exceptionRecord123['event'] = $this->getDatabaseConnection()->lastInsertId();
        $exceptionRecord124 = $this->exceptionRecord;
        $exceptionRecord124['uid'] = 124;
        $exceptionRecord124['exception_date'] = (int)$today->format('U');
        $exceptionRecord124['event'] = $this->getDatabaseConnection()->lastInsertId();

        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_exception',
            $exceptionRecord123,
        );
        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_exception',
            $exceptionRecord124,
        );

        $dateTimeStorage = [
            (int)$yesterday->format('U') => new DateTimeEntry($yesterday, false),
            (int)$today->format('U') => new DateTimeEntry($today, false),
            (int)$tomorrow->format('U') => new DateTimeEntry($tomorrow, false),
        ];

        $this->dayGeneratorServiceMock
            ->getDateTimeStorageForEvent(Argument::withEntry('uid', 123))
            ->shouldBeCalled()
            ->willReturn($dateTimeStorage);

        $eventTimeRecord = $this->timeRecord;
        $eventTimeRecord['type'] = 'event_time';
        $eventTimeRecord['event'] = $this->eventRecord;
        $exception123TimeRecord = $this->timeRecord;
        $exception123TimeRecord['type'] = 'exception_time';
        $exception123TimeRecord['time_begin'] = '15:30';
        $exception123TimeRecord['exception'] = $exceptionRecord123;
        $exception124TimeRecord = $this->timeRecord;
        $exception124TimeRecord['type'] = 'exception_time';
        $exception124TimeRecord['time_begin'] = '20:15';
        $exception124TimeRecord['exception'] = $exceptionRecord124;

        $this->timeRepositoryMock
            ->getAllByEventRecord(Argument::withEntry('uid', 123), true)
            ->shouldBeCalled()
            ->willReturn([$eventTimeRecord, $exception123TimeRecord, $exception124TimeRecord]);

        $days = $this->subject->createDayRelations(123)['days'];
        self::assertCount(
            4,
            $days,
        );

        $date = $yesterday;
        $switch = true;
        foreach ($days as $day) {
            if ($date == $today) {
                $dateWithTime = $date->modify($switch ? '15:30:00' : '20:15:00');
                $switch = !$switch;
            } else {
                $dateWithTime = $date->modify('08:12:00');
                $switch = true;
            }
            self::assertSame(
                (int)$date->format('U'),
                $day['day'],
                'Column: day - Expected: ' . $date->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['day']),
            );
            self::assertSame(
                (int)$dateWithTime->format('U'),
                $day['day_time'],
                'Column: day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['day_time']),
            );
            self::assertSame(
                (int)$dateWithTime->format('U'),
                $day['sort_day_time'],
                'Column: sort_day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['sort_day_time']),
            );

            if ($date == $today) {
                self::assertSame(
                    (int)$dateWithTime->modify('15:30:00')->format('U'),
                    $day['same_day_time'],
                    'Column: same_day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['same_day_time']),
                );
            } else {
                self::assertSame(
                    (int)$dateWithTime->format('U'),
                    $day['same_day_time'],
                    'Column: same_day_time - Date: ' . $dateWithTime->format('d.m.Y H:i:s'),
                );
            }
            if ($switch) {
                $date = $date->modify('+1 day');
            }
        }
    }

    #[Test]
    public function createDayRelationsWithDurationEvent(): void
    {
        $yesterday = new \DateTimeImmutable('yesterday midnight');
        $today = new \DateTimeImmutable('today midnight');
        $tomorrow = new \DateTimeImmutable('tomorrow midnight');

        $this->eventRecord['event_type'] = 'duration';

        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_event',
            $this->eventRecord,
        );

        $dateTimeStorage = [
            (int)$yesterday->format('U') => new DateTimeEntry($yesterday, false),
            (int)$today->format('U') => new DateTimeEntry($today, false),
            (int)$tomorrow->format('U') => new DateTimeEntry($tomorrow, false),
        ];

        $this->dayGeneratorServiceMock
            ->getDateTimeStorageForEvent(Argument::withEntry('uid', 123))
            ->shouldBeCalled()
            ->willReturn($dateTimeStorage);

        $eventTimeRecord = $this->timeRecord;
        $eventTimeRecord['type'] = 'event_time';
        $eventTimeRecord['event'] = $this->eventRecord;

        $this->timeRepositoryMock
            ->getAllByEventRecord(Argument::withEntry('uid', 123), true)
            ->shouldBeCalled()
            ->willReturn([$eventTimeRecord]);

        $days = $this->subject->createDayRelations(123)['days'];
        self::assertCount(
            3,
            $days,
        );

        $date = $yesterday;
        foreach ($days as $day) {
            $dateWithTime = $date->modify('08:12:00');
            self::assertSame(
                (int)$date->format('U'),
                $day['day'],
                'Column: day - Expected: ' . $date->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['day']),
            );
            self::assertSame(
                (int)$dateWithTime->format('U'),
                $day['day_time'],
                'Column: day_time - Expected: ' . $dateWithTime->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['day_time']),
            );
            self::assertSame(
                (int)$yesterday->modify('08:12:00')->format('U'),
                $day['sort_day_time'],
                'Column: sort_day_time - Expected: ' . $yesterday->modify('08:12:00')->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['sort_day_time']),
            );
            self::assertSame(
                (int)$yesterday->modify('08:12:00')->format('U'),
                $day['same_day_time'],
                'Column: same_day_time - Expected: ' . $yesterday->modify('08:12:00')->format('d.m.Y H:i:s') . ' - Current: ' . date('d.m.Y H:i:s', $day['same_day_time']),
            );
            $date = $date->modify('+1 day');
        }
    }
}
