<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Service;

use JWeiland\Events2\Service\DayRecordBuilderService;
use JWeiland\Events2\Service\Record\TimeRecordService;
use JWeiland\Events2\Service\Result\DateTimeResult;
use JWeiland\Events2\Service\Result\DayGeneratorResult;
use JWeiland\Events2\Service\TimeService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 */
class TimeServiceTest extends FunctionalTestCase
{
    protected TimeService $subject;

    protected TimeRecordService|MockObject $timeRecordServiceMock;

    protected DayRecordBuilderService|MockObject $dayRecordBuilderServiceMock;

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

        $this->timeRecordServiceMock = $this->createMock(TimeRecordService::class);
        $this->dayRecordBuilderServiceMock = $this->createMock(DayRecordBuilderService::class);
        $this->dayRecordBuilderServiceMock
            ->expects(self::once())
            ->method('buildDayRecordsFor');

        $this->subject = new TimeService(
            $this->timeRecordServiceMock,
            $this->dayRecordBuilderServiceMock,
            new DateTimeUtility(),
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->timeRecordServiceMock,
            $this->dayRecordBuilderServiceMock,
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function enrichWithNoTimeRecordsWillNotAddTimeResult(): void
    {
        $eventRecord = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
        ];

        $dateTimeResult = new DateTimeResult(
            new \DateTimeImmutable('midnight'),
            false,
        );

        $dayGeneratorResult = new DayGeneratorResult($eventRecord);
        $dayGeneratorResult->addDateTimeResult($dateTimeResult);

        $this->subject->enrichWithTimeAndBuildDayRecords($dayGeneratorResult);

        foreach ($dayGeneratorResult->getDateTimeResultStorageSorted() as $dateTimeResult) {
            self::assertCount(
                0,
                $dateTimeResult->getTimeResultStorage(),
            );
        }
    }

    public static function invalidTimeRecordDataProvider(): array
    {
        return [
            'Missing type' => [[
                'uid' => 1,
                'pid' => Events2Constants::PAGE_STORAGE,
                'hidden' => 0,
                'event' => 0,
                'exception' => 0,
                'weekday' => 0,
            ]],
            'Missing hidden' => [[
                'uid' => 1,
                'pid' => Events2Constants::PAGE_STORAGE,
                'type' => 0,
                'event' => 0,
                'exception' => 0,
                'weekday' => 0,
            ]],
            'Missing event' => [[
                'uid' => 1,
                'pid' => Events2Constants::PAGE_STORAGE,
                'type' => 0,
                'hidden' => 0,
                'exception' => 0,
                'weekday' => 0,
            ]],
            'Missing exception' => [[
                'uid' => 1,
                'pid' => Events2Constants::PAGE_STORAGE,
                'type' => 0,
                'hidden' => 0,
                'event' => 0,
                'weekday' => 0,
            ]],
            'Missing weekday' => [[
                'uid' => 1,
                'pid' => Events2Constants::PAGE_STORAGE,
                'type' => 0,
                'hidden' => 0,
                'event' => 0,
                'exception' => 0,
            ]],
            'hidden time record' => [[
                'uid' => 1,
                'pid' => Events2Constants::PAGE_STORAGE,
                'type' => 0,
                'hidden' => 1,
                'event' => [],
                'exception' => 0,
                'weekday' => 0,
            ]],
            'event is no array' => [[
                'uid' => 1,
                'pid' => Events2Constants::PAGE_STORAGE,
                'type' => 0,
                'hidden' => 0,
                'event' => 0,
                'exception' => [],
                'weekday' => 0,
            ]],
            'exception is no array' => [[
                'uid' => 1,
                'pid' => Events2Constants::PAGE_STORAGE,
                'type' => 0,
                'hidden' => 0,
                'event' => [],
                'exception' => 0,
                'weekday' => 0,
            ]],
        ];
    }

    #[Test]
    #[DataProvider('invalidTimeRecordDataProvider')]
    public function enrichWithInvalidTimeRecordWillNotAddTimeResult(array $timeRecord): void
    {
        $eventRecord = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
        ];

        $dateTimeResult = new DateTimeResult(
            new \DateTimeImmutable('midnight'),
            false,
        );

        $dayGeneratorResult = new DayGeneratorResult($eventRecord);
        $dayGeneratorResult->addDateTimeResult($dateTimeResult);

        $this->timeRecordServiceMock
            ->expects(self::once())
            ->method('getAllByEventRecord')
            ->with(
                self::identicalTo($eventRecord),
                self::identicalTo(true),
            )
            ->willReturn([$timeRecord]);

        $this->subject->enrichWithTimeAndBuildDayRecords($dayGeneratorResult);

        foreach ($dayGeneratorResult->getDateTimeResultStorageSorted() as $dateTimeResult) {
            self::assertCount(
                0,
                $dateTimeResult->getTimeResultStorage(),
            );
        }
    }

    #[Test]
    public function enrichWithExceptionTimeRecordWillAddTimeResult(): void
    {
        $dateTime = new \DateTimeImmutable('midnight');

        $exceptionRecord = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'hidden' => 0,
            'exception_type' => 'Add',
            'exception_date' => (int)$dateTime->format('U'),
        ];

        $eventRecord = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'exceptions' => [
                1 => $exceptionRecord,
            ],
        ];

        $exceptionTimeRecord = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'type' => 'exception_time',
            'hidden' => 0,
            'event' => [],
            'exception' => $exceptionRecord,
            'weekday' => 0,
        ];

        $dayGeneratorResult = new DayGeneratorResult($eventRecord);
        $dayGeneratorResult->addDateTimeResult(new DateTimeResult($dateTime, false));

        $this->timeRecordServiceMock
            ->expects(self::once())
            ->method('getAllByEventRecord')
            ->with(
                self::identicalTo($eventRecord),
                self::identicalTo(true),
            )
            ->willReturn([$exceptionTimeRecord]);

        $this->subject->enrichWithTimeAndBuildDayRecords($dayGeneratorResult);

        foreach ($dayGeneratorResult->getDateTimeResultStorageSorted() as $dateTimeResult) {
            self::assertCount(
                1,
                $dateTimeResult->getTimeResultStorage(),
            );
            foreach ($dateTimeResult->getTimeResultStorage() as $timeResult) {
                self::assertEquals(
                    $exceptionTimeRecord,
                    $timeResult->getTimeRecord(),
                );
            }
        }
    }

    #[Test]
    public function enrichWithMultipleExceptionTimeRecordsWillAddTimeResult(): void
    {
        $dateTime = new \DateTimeImmutable('midnight');
        $dateTimeYesterday = new \DateTimeImmutable('yesterday midnight');

        $exceptionRecordMatching = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'hidden' => 0,
            'exception_type' => 'Add',
            'exception_date' => (int)$dateTime->format('U'),
        ];

        $exceptionRecordNonMatching = [
            'uid' => 2,
            'pid' => Events2Constants::PAGE_STORAGE,
            'hidden' => 0,
            'exception_type' => 'Add',
            'exception_date' => (int)$dateTimeYesterday->format('U'),
        ];

        $eventRecord = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'exceptions' => [
                1 => $exceptionRecordMatching,
                2 => $exceptionRecordNonMatching,
            ],
        ];

        $exceptionTimeRecordMatching = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'type' => 'exception_time',
            'hidden' => 0,
            'event' => [],
            'exception' => $exceptionRecordMatching,
            'weekday' => 0,
        ];

        $exceptionTimeRecordNonMatching = [
            'uid' => 2,
            'pid' => Events2Constants::PAGE_STORAGE,
            'type' => 'exception_time',
            'hidden' => 0,
            'event' => [],
            'exception' => $exceptionRecordNonMatching,
            'weekday' => 0,
        ];

        $dayGeneratorResult = new DayGeneratorResult($eventRecord);
        $dayGeneratorResult->addDateTimeResult(new DateTimeResult($dateTime, false));

        $this->timeRecordServiceMock
            ->expects(self::once())
            ->method('getAllByEventRecord')
            ->with(
                self::identicalTo($eventRecord),
                self::identicalTo(true),
            )
            ->willReturn([$exceptionTimeRecordMatching, $exceptionTimeRecordNonMatching]);

        $this->subject->enrichWithTimeAndBuildDayRecords($dayGeneratorResult);

        foreach ($dayGeneratorResult->getDateTimeResultStorageSorted() as $dateTimeResult) {
            self::assertCount(
                1,
                $dateTimeResult->getTimeResultStorage(),
            );
            foreach ($dateTimeResult->getTimeResultStorage() as $timeResult) {
                self::assertEquals(
                    $exceptionTimeRecordMatching,
                    $timeResult->getTimeRecord(),
                );
            }
        }
    }

    #[Test]
    public function enrichWithOneHiddenExceptionTimeRecordsWillAddTimeResult(): void
    {
        $dateTime = new \DateTimeImmutable('midnight');

        $exceptionRecordMatching = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'hidden' => 0,
            'exception_type' => 'Add',
            'exception_date' => (int)$dateTime->format('U'),
        ];

        $exceptionRecordNonMatching = [
            'uid' => 2,
            'pid' => Events2Constants::PAGE_STORAGE,
            'hidden' => 1,
            'exception_type' => 'Add',
            'exception_date' => (int)$dateTime->format('U'),
        ];

        $eventRecord = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'exceptions' => [
                1 => $exceptionRecordMatching,
                2 => $exceptionRecordNonMatching,
            ],
        ];

        $exceptionTimeRecordMatching = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'type' => 'exception_time',
            'hidden' => 0,
            'event' => [],
            'exception' => $exceptionRecordMatching,
            'weekday' => 0,
        ];

        $exceptionTimeRecordNonMatching = [
            'uid' => 2,
            'pid' => Events2Constants::PAGE_STORAGE,
            'type' => 'exception_time',
            'hidden' => 0,
            'event' => [],
            'exception' => $exceptionRecordNonMatching,
            'weekday' => 0,
        ];

        $dayGeneratorResult = new DayGeneratorResult($eventRecord);
        $dayGeneratorResult->addDateTimeResult(new DateTimeResult($dateTime, false));

        $this->timeRecordServiceMock
            ->expects(self::once())
            ->method('getAllByEventRecord')
            ->with(
                self::identicalTo($eventRecord),
                self::identicalTo(true),
            )
            ->willReturn([$exceptionTimeRecordMatching, $exceptionTimeRecordNonMatching]);

        $this->subject->enrichWithTimeAndBuildDayRecords($dayGeneratorResult);

        foreach ($dayGeneratorResult->getDateTimeResultStorageSorted() as $dateTimeResult) {
            self::assertCount(
                1,
                $dateTimeResult->getTimeResultStorage(),
            );
            foreach ($dateTimeResult->getTimeResultStorage() as $timeResult) {
                self::assertEquals(
                    $exceptionTimeRecordMatching,
                    $timeResult->getTimeRecord(),
                );
            }
        }
    }

    #[Test]
    public function enrichWithOneRemovedExceptionTimeRecordsWillAddTimeResult(): void
    {
        $dateTime = new \DateTimeImmutable('midnight');

        $exceptionRecordMatching = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'hidden' => 0,
            'exception_type' => 'Time',
            'exception_date' => (int)$dateTime->format('U'),
        ];

        $exceptionRecordNonMatching = [
            'uid' => 2,
            'pid' => Events2Constants::PAGE_STORAGE,
            'hidden' => 0,
            'exception_type' => 'Removed',
            'exception_date' => (int)$dateTime->format('U'),
        ];

        $eventRecord = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'exceptions' => [
                1 => $exceptionRecordMatching,
                2 => $exceptionRecordNonMatching,
            ],
        ];

        $exceptionTimeRecordMatching = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'type' => 'exception_time',
            'hidden' => 0,
            'event' => [],
            'exception' => $exceptionRecordMatching,
            'weekday' => 0,
        ];

        $exceptionTimeRecordNonMatching = [
            'uid' => 2,
            'pid' => Events2Constants::PAGE_STORAGE,
            'type' => 'exception_time',
            'hidden' => 0,
            'event' => [],
            'exception' => $exceptionRecordNonMatching,
            'weekday' => 0,
        ];

        $dayGeneratorResult = new DayGeneratorResult($eventRecord);
        $dayGeneratorResult->addDateTimeResult(new DateTimeResult($dateTime, false));

        $this->timeRecordServiceMock
            ->expects(self::once())
            ->method('getAllByEventRecord')
            ->with(
                self::identicalTo($eventRecord),
                self::identicalTo(true),
            )
            ->willReturn([$exceptionTimeRecordMatching, $exceptionTimeRecordNonMatching]);

        $this->subject->enrichWithTimeAndBuildDayRecords($dayGeneratorResult);

        foreach ($dayGeneratorResult->getDateTimeResultStorageSorted() as $dateTimeResult) {
            self::assertCount(
                1,
                $dateTimeResult->getTimeResultStorage(),
            );
            foreach ($dateTimeResult->getTimeResultStorage() as $timeResult) {
                self::assertEquals(
                    $exceptionTimeRecordMatching,
                    $timeResult->getTimeRecord(),
                );
            }
        }
    }

    #[Test]
    public function enrichWithDifferentTimeRecordsNotMatchingWeekdayWillNotAddTimeResult(): void
    {
        $dateTime = new \DateTimeImmutable('midnight');
        $dateTimeYesterday = new \DateTimeImmutable('yesterday midnight');

        $eventRecord = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'exceptions' => [],
        ];

        $differentTimeRecord = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'type' => 'different_times',
            'hidden' => 0,
            'event' => [],
            'exception' => [],
            'weekday' => $dateTimeYesterday->format('l'),
        ];

        $dayGeneratorResult = new DayGeneratorResult($eventRecord);
        $dayGeneratorResult->addDateTimeResult(new DateTimeResult($dateTime, false));

        $this->timeRecordServiceMock
            ->expects(self::once())
            ->method('getAllByEventRecord')
            ->with(
                self::identicalTo($eventRecord),
                self::identicalTo(true),
            )
            ->willReturn([$differentTimeRecord]);

        $this->subject->enrichWithTimeAndBuildDayRecords($dayGeneratorResult);

        foreach ($dayGeneratorResult->getDateTimeResultStorageSorted() as $dateTimeResult) {
            self::assertCount(
                0,
                $dateTimeResult->getTimeResultStorage(),
            );
        }
    }

    #[Test]
    public function enrichWithDifferentTimeRecordsWillAddTimeResult(): void
    {
        $dateTime = new \DateTimeImmutable('midnight');

        $eventRecord = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'exceptions' => [],
        ];

        $differentTimeRecord = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'type' => 'different_times',
            'hidden' => 0,
            'event' => [],
            'exception' => [],
            'weekday' => $dateTime->format('l'),
        ];

        $dayGeneratorResult = new DayGeneratorResult($eventRecord);
        $dayGeneratorResult->addDateTimeResult(new DateTimeResult($dateTime, false));

        $this->timeRecordServiceMock
            ->expects(self::once())
            ->method('getAllByEventRecord')
            ->with(
                self::identicalTo($eventRecord),
                self::identicalTo(true),
            )
            ->willReturn([$differentTimeRecord]);

        $this->subject->enrichWithTimeAndBuildDayRecords($dayGeneratorResult);

        foreach ($dayGeneratorResult->getDateTimeResultStorageSorted() as $dateTimeResult) {
            self::assertCount(
                1,
                $dateTimeResult->getTimeResultStorage(),
            );
            foreach ($dateTimeResult->getTimeResultStorage() as $timeResult) {
                self::assertEquals(
                    $differentTimeRecord,
                    $timeResult->getTimeRecord(),
                );
            }
        }
    }

    #[Test]
    public function enrichWithEventTimeRecordWillAddTimeResult(): void
    {
        $dateTime = new \DateTimeImmutable('midnight');

        $eventRecord = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'exceptions' => [],
        ];

        $eventTimeRecord = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'type' => 'event_time',
            'hidden' => 0,
            'event' => [],
            'exception' => [],
            'weekday' => '',
        ];

        $dayGeneratorResult = new DayGeneratorResult($eventRecord);
        $dayGeneratorResult->addDateTimeResult(new DateTimeResult($dateTime, false));

        $this->timeRecordServiceMock
            ->expects(self::once())
            ->method('getAllByEventRecord')
            ->with(
                self::identicalTo($eventRecord),
                self::identicalTo(true),
            )
            ->willReturn([$eventTimeRecord]);

        $this->subject->enrichWithTimeAndBuildDayRecords($dayGeneratorResult);

        foreach ($dayGeneratorResult->getDateTimeResultStorageSorted() as $dateTimeResult) {
            self::assertCount(
                1,
                $dateTimeResult->getTimeResultStorage(),
            );
            foreach ($dateTimeResult->getTimeResultStorage() as $timeResult) {
                self::assertEquals(
                    $eventTimeRecord,
                    $timeResult->getTimeRecord(),
                );
            }
        }
    }

    #[Test]
    public function enrichWithMultipleTimeRecordsWillAddTimeResult(): void
    {
        $dateTime = new \DateTimeImmutable('midnight');

        $eventRecord = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'exceptions' => [],
        ];

        $multipleTimeRecord = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'type' => 'multiple_times',
            'hidden' => 0,
            'event' => [],
            'exception' => [],
            'weekday' => '',
        ];

        $dayGeneratorResult = new DayGeneratorResult($eventRecord);
        $dayGeneratorResult->addDateTimeResult(new DateTimeResult($dateTime, false));

        $this->timeRecordServiceMock
            ->expects(self::once())
            ->method('getAllByEventRecord')
            ->with(
                self::identicalTo($eventRecord),
                self::identicalTo(true),
            )
            ->willReturn([$multipleTimeRecord]);

        $this->subject->enrichWithTimeAndBuildDayRecords($dayGeneratorResult);

        foreach ($dayGeneratorResult->getDateTimeResultStorageSorted() as $dateTimeResult) {
            self::assertCount(
                1,
                $dateTimeResult->getTimeResultStorage(),
            );
            foreach ($dateTimeResult->getTimeResultStorage() as $timeResult) {
                self::assertEquals(
                    $multipleTimeRecord,
                    $timeResult->getTimeRecord(),
                );
            }
        }
    }

    #[Test]
    public function enrichWithEventTimeAndMultipleTimesWillAddTimeResult(): void
    {
        $dateTime = new \DateTimeImmutable('midnight');

        $eventRecord = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'exceptions' => [],
        ];

        $eventTimeRecord = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'type' => 'event_time',
            'hidden' => 0,
            'event' => [],
            'exception' => [],
            'weekday' => '',
        ];

        $multipleTimeRecord = [
            'uid' => 1,
            'pid' => Events2Constants::PAGE_STORAGE,
            'type' => 'multiple_times',
            'hidden' => 0,
            'event' => [],
            'exception' => [],
            'weekday' => '',
        ];

        $dayGeneratorResult = new DayGeneratorResult($eventRecord);
        $dayGeneratorResult->addDateTimeResult(new DateTimeResult($dateTime, false));

        $this->timeRecordServiceMock
            ->expects(self::once())
            ->method('getAllByEventRecord')
            ->with(
                self::identicalTo($eventRecord),
                self::identicalTo(true),
            )
            ->willReturn([$eventTimeRecord, $multipleTimeRecord]);

        $this->subject->enrichWithTimeAndBuildDayRecords($dayGeneratorResult);

        foreach ($dayGeneratorResult->getDateTimeResultStorageSorted() as $dateTimeResult) {
            self::assertCount(
                2,
                $dateTimeResult->getTimeResultStorage(),
            );
        }
    }
}
