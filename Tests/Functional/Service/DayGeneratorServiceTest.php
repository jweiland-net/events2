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
use JWeiland\Events2\Service\Result\DateTimeResult;
use JWeiland\Events2\Service\DayGeneratorService;
use JWeiland\Events2\Service\TimeService;
use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for class \JWeiland\Events2\Service\DayGeneratorService.
 */
class DayGeneratorServiceTest extends FunctionalTestCase
{
    protected DayGeneratorService $subject;

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

        $this->loggerMock = $this->createMock(Logger::class);

        $this->assignSubject(new ExtConf(
            recurringFuture: 12,
        ));
    }

    /**
     * As we use original extConf instead of MockObject we cannot change this object afterward.
     * Use this method with your preferred ExtConf to assign it again to $this->subject
     */
    protected function assignSubject(ExtConf $extConf): void
    {
        $this->subject = new DayGeneratorService(
            $this->createMock(TimeService::class),
            $extConf,
            new DateTimeUtility(),
            GeneralUtility::makeInstance(EventDispatcher::class),
            $this->loggerMock,
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->loggerMock,
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function getDateTimeStorageForEventWithMissingEventTypeThrowsException(): void
    {
        $this->loggerMock
            ->expects(self::atLeastOnce())
            ->method('error')
            ->willReturnMap([
                [self::stringContains('Error occurred while building DateTime objects'), null],
                [self::stringContains('It does not contain mandatory property: event_type'), null],
            ]);

        $eventRecord = [
            'uid' => 123,
        ];

        self::assertSame(
            [],
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    #[Test]
    public function getDateTimeStorageForEventWithMissingEventBeginThrowsException(): void
    {
        $this->loggerMock
            ->expects(self::atLeastOnce())
            ->method('error')
            ->willReturnMap([
                [self::stringContains('Error occurred while building DateTime objects'), null],
                [self::stringContains('It does not contain mandatory property: event_begin'), null],
            ]);

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'duration',
        ];

        self::assertSame(
            [],
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    #[Test]
    public function getDateTimeStorageForEventWithEmptyEventTypeThrowsException(): void
    {
        $this->loggerMock
            ->expects(self::atLeastOnce())
            ->method('error')
            ->willReturnMap([
                [self::stringContains('Error occurred while building DateTime objects'), null],
                [self::stringContains('Column "event_type" can not be empty'), null],
            ]);

        $eventRecord = [
            'uid' => 123,
            'event_type' => '',
            'event_begin' => 0,
            'event_end' => 0,
            'recurring_end' => 0,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => [],
        ];

        self::assertSame(
            [],
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    #[Test]
    public function getDateTimeStorageForEventWithEmptyEventEndForDurationalEventsThrowsException(): void
    {
        $this->loggerMock
            ->expects(self::atLeastOnce())
            ->method('error')
            ->willReturnMap([
                [self::stringContains('Error occurred while building DateTime objects'), null],
                [self::stringContains('If event_type is set to "duration" column "event_end" has to be set'), null],
            ]);

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'duration',
            'event_begin' => 0,
            'event_end' => 0,
            'recurring_end' => 0,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => [],
        ];

        self::assertSame(
            [],
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    #[Test]
    public function getDateTimeStorageForEventWithEmptyEventBeginThrowsException(): void
    {
        $this->loggerMock
            ->expects(self::atLeastOnce())
            ->method('error')
            ->willReturnMap([
                [self::stringContains('Error occurred while building DateTime objects'), null],
                [self::stringContains('Column "event_begin" can not be empty'), null],
            ]);

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'single',
            'event_begin' => 0,
            'event_end' => 0,
            'recurring_end' => 0,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => [],
        ];

        self::assertSame(
            [],
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    #[Test]
    public function getDateTimeStorageForEventWithRecurringWeeksResetsTimeToMidnight(): void
    {
        $eventBegin = new \DateTimeImmutable('yesterday 15:38:24');
        $recurringEnd = new \DateTimeImmutable('+6 weeks 15:38:24');
        $eventRecord = [
            'uid' => 123,
            'event_type' => 'recurring',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => (int)$recurringEnd->format('U'),
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 1,
            'each_months' => 0,
            'exceptions' => [],
        ];

        $dateTimeEntries = $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy();

        foreach ($dateTimeEntries as $dateTimeResult) {
            self::assertSame(
                '00:00:00',
                $dateTimeResult->getDate()->format('H:i:s'),
            );
        }
    }

    #[Test]
    public function getDateTimeStorageForEventWithRecurringOverEachWeekAddsThreeDaysToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');
        $nextWeek = $eventBegin->modify('+7 days');
        $recurringEnd = $eventBegin->modify('+14 days');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'recurring',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => (int)$recurringEnd->format('U'),
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 1,
            'each_months' => 0,
            'exceptions' => [],
        ];

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = new DateTimeResult($eventBegin, false);
        $expectedDays[$nextWeek->format('U')] = new DateTimeResult($nextWeek, false);
        $expectedDays[$recurringEnd->format('U')] = new DateTimeResult($recurringEnd, false);

        $dateTimeEntries = $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy();
        self::assertEquals(
            $expectedDays,
            $dateTimeEntries,
        );
    }

    #[Test]
    public function getDateTimeStorageForEventWithRecurringOverEachWeekReturnsDateTimesWithTimezoneType3(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');
        $recurringEnd = $eventBegin->modify('+14 days');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'recurring',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => (int)$recurringEnd->format('U'),
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 1,
            'each_months' => 0,
            'exceptions' => [],
        ];

        $dateTimeEntries = $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy();
        foreach ($dateTimeEntries as $dateTimeResult) {
            self::assertIsArray($dateTimeResult->getDate()->getTimezone()->getLocation());
        }
    }

    #[Test]
    public function getDateTimeStorageForEventWithRecurringOverTwoWeeksAddsDaysToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');
        $recurringEnd = $eventBegin->modify('+14 days');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'recurring',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => (int)$recurringEnd->format('U'),
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 2,
            'each_months' => 0,
            'exceptions' => [],
        ];

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = new DateTimeResult($eventBegin, false);
        $expectedDays[$recurringEnd->format('U')] = new DateTimeResult($recurringEnd, false);

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    /**
     * As the earliest date for summer- to wintertime switches is the 25. October,
     * calculated for the next 30 years, I have chosen the 20.10. To be safe.
     */
    #[Test]
    public function getDateTimeStorageForEventWithRecurringWeeksWillKeepDaylightSavingTime(): void
    {
        $timestampEventBeginSummerTime = mktime(0, 0, 0, 10, 27, 2024);
        $timestampRecurringEndWinterTime = mktime(0, 0, 0, 11, 10, 2024);

        // Note: Either increase the "recurringPast" value to allow event generation for more months in the past,
        // or update the test's initial event dates (e.g., to a more recent year or to properly reflect daylight saving time changes).
        // Reason: As time progresses, the current date moves forward and the gap between 'now' and the hardcoded start date widens.
        // If (current year - event start year) * 12 exceeds the configured "recurringPast" limit (e.g., after 9 years = 108 months),
        // the system will not generate daily records for past events, causing this test to fail due to insufficient history coverage.
        $this->assignSubject(new ExtConf(
            recurringPast: 12,
            recurringFuture: 12,
        ));

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'recurring',
            'event_begin' => (int)$timestampEventBeginSummerTime,
            'event_end' => 0,
            'recurring_end' => (int)$timestampRecurringEndWinterTime,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 2,
            'each_months' => 0,
            'exceptions' => [],
        ];

        $expectedBegin = new \DateTimeImmutable(date('c', $timestampEventBeginSummerTime));
        $expectedBegin = $expectedBegin->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $expectedEnd = $expectedBegin->modify('+14 days');

        self::assertEquals(
            [
                $expectedBegin->format('U') => new DateTimeResult($expectedBegin, false),
                $expectedEnd->format('U') => new DateTimeResult($expectedEnd, false),
            ],
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    #[Test]
    public function getDateTimeStorageForEventWithRecurringOverEachMonthAddsThreeDaysToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable('first day of this month midnight');
        $eventBegin = $eventBegin->modify('+25 days');
        $nextMonth = $eventBegin->modify('+1 months');
        $recurringEnd = $eventBegin->modify('+2 months');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'recurring',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => (int)$recurringEnd->format('U'),
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'each_months' => 1,
            'exceptions' => [],
        ];

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = new DateTimeResult($eventBegin, false);
        $expectedDays[$nextMonth->format('U')] = new DateTimeResult($nextMonth, false);
        $expectedDays[$recurringEnd->format('U')] = new DateTimeResult($recurringEnd, false);

        $this->assignSubject(new ExtConf(
            recurringPast: 1,
            recurringFuture: 12,
        ));

        $dateTimeEntries = $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy();
        self::assertEquals(
            $expectedDays,
            $dateTimeEntries,
        );

        // test for the correct TimezoneType, else times are not DST save
        foreach ($dateTimeEntries as $dateTimeResult) {
            self::assertIsArray($dateTimeResult->getDate()->getTimezone()->getLocation());
        }
    }

    #[Test]
    public function getDateTimeStorageForEventWithRecurringOverTwoMonthsAddsDaysToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');
        $recurringEnd = $eventBegin->modify('+2 months');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'recurring',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => (int)$recurringEnd->format('U'),
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'each_months' => 2,
            'exceptions' => [],
        ];

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = new DateTimeResult($eventBegin, false);
        $expectedDays[$recurringEnd->format('U')] = new DateTimeResult($recurringEnd, false);

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    /**
     * There is a special condition in DayGenerator::getDateToStartCalculatingFrom why we have to do this test
     */
    #[Test]
    public function getDateTimeStorageForEventWithRecurringOverTwoMonthsAndVeryEarlyEventDateAddsDayToStorage(): void
    {
        $this->assignSubject(new ExtConf(
            recurringPast: 3,
            recurringFuture: 12,
        ));

        $eventBegin = new \DateTimeImmutable('-4 months midnight');
        $recurringEnd = $eventBegin->modify('+2 months');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'recurring',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => (int)$recurringEnd->format('U'),
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'each_months' => 2,
            'exceptions' => [],
        ];

        $expectedDays = [];
        $expectedDays[$recurringEnd->format('U')] = new DateTimeResult($recurringEnd, false);

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    #[Test]
    public function getDateTimeStorageForEventWithRecurringOverEachMonthAndTwoWeeksAddsDaysToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');
        $nextEvent = $eventBegin->modify('+1 months +2 weeks');
        $lastEvent = $nextEvent->modify('+1 months +2 weeks');
        $recurringEnd = $eventBegin->modify('+3 months');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'recurring',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => (int)$recurringEnd->format('U'),
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 2,
            'each_months' => 1,
            'exceptions' => [],
        ];

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = new DateTimeResult($eventBegin, false);
        $expectedDays[$nextEvent->format('U')] = new DateTimeResult($nextEvent, false);
        $expectedDays[$lastEvent->format('U')] = new DateTimeResult($lastEvent, false);

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    #[Test]
    public function getDateTimeStorageForEventWithRecurringAndEqualEventBeginAndEventEndResultsInOneDayInStorage(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'recurring',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => (int)$eventBegin->format('U'),
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => [],
        ];

        self::assertEquals(
            [
                $eventBegin->format('U') => new DateTimeResult($eventBegin, false),
            ],
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    #[Test]
    public function getDateTimeStorageForEventWithGivenWeekdaysResultsInAddedDaysInStorage(): void
    {
        $eventBegin = new \DateTimeImmutable('next saturday midnight');
        $recurringEnd = $eventBegin->modify('+8 days');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'recurring',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => (int)$recurringEnd->format('U'),
            'xth' => 31, // all
            'weekday' => 87, // mo, tu, we, fr, su
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => [],
        ];

        $tempDate = $eventBegin;
        $expectedDays = [];
        $tempDate = $tempDate->modify('+1 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeResult($tempDate, false); // add sunday
        $tempDate = $tempDate->modify('+1 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeResult($tempDate, false); // add monday
        $tempDate = $tempDate->modify('+1 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeResult($tempDate, false); // add tuesday
        $tempDate = $tempDate->modify('+1 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeResult($tempDate, false); // add wednesday
        $tempDate = $tempDate->modify('+2 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeResult($tempDate, false); // add friday
        $tempDate = $tempDate->modify('+2 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeResult($tempDate, false); // add sunday
        ksort($expectedDays);

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    /**
     * This test also checks for leap years.
     */
    #[Test]
    public function getDateTimeStorageForEventWithGivenXthsResultsInAddedDaysInStorage(): void
    {
        // $eventBegin has to start with a month beginning with a Thursday
        $eventBegin = new \DateTimeImmutable('first day of next month midnight');
        while (
            (int)$eventBegin->format('N') !== 4
            || (int)$eventBegin->format('n') === 2
        ) {
            $eventBegin = $eventBegin->modify('next month');
        }
        $eventBegin = $eventBegin->modify('+16 days'); // First day of the month (1) + 16 = 17. Of month. Must be Saturday
        $recurringEnd = $eventBegin->modify('+22 days'); // 07th or 08th of next month. Regarding if the month has 30 or 31 days

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'recurring',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => (int)$recurringEnd->format('U'),
            'xth' => 21, // 1st, 3rd, 5th
            'weekday' => 18, // Tuesday, Friday
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => [],
        ];

        $tempDate = $eventBegin;
        $expectedDays = [];
        $tempDate = $tempDate->modify('+3 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeResult($tempDate, false); // add 3rd Tuesday 20th of the month
        $tempDate = $tempDate->modify('+10 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeResult($tempDate, false); // add 5th Friday 30th of the month
        $tempDate = $tempDate->modify('+4 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeResult($tempDate, false); // add 1st Tuesday 3rd of the next month
        $tempDate = $tempDate->modify('+3 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeResult($tempDate, false); // add 1st Friday 6th of the next month
        ksort($expectedDays);

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    #[Test]
    public function getDateTimeStorageForEventWithEventEndAddsDaysToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin = $eventBegin->modify('midnight');
        $recurringEnd = new \DateTimeImmutable();
        $recurringEnd = $recurringEnd->modify('midnight');
        $recurringEnd = $recurringEnd->modify('+4 days');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'recurring',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => (int)$recurringEnd->format('U'),
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => [],
        ];

        $tempDate = $eventBegin;
        $expectedDays = [];
        for ($i = 0; $i < 5; ++$i) {
            $expectedDays[$tempDate->format('U')] = new DateTimeResult($tempDate, false);
            $tempDate = $tempDate->modify('+1 day');
        }

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    #[Test]
    public function getDateTimeStorageForEventWithEventBeginAddsOneDayToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin = $eventBegin->modify('midnight');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'single',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => 0,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => [],
        ];

        self::assertEquals(
            [
                $eventBegin->format('U') => new DateTimeResult($eventBegin, false),
            ],
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    #[Test]
    public function getDateTimeStorageForEventWithAddExceptionAddsOneDayInStorage(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');
        $recurringEnd = $eventBegin->modify('+7 days');
        $tomorrow = $eventBegin->modify('tomorrow');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'recurring',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => (int)$recurringEnd->format('U'),
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 1,
            'each_months' => 0,
            'exceptions' => [
                1 => [
                    'hidden' => 0,
                    'exception_type' => 'Add',
                    'exception_date' => (int)$tomorrow->format('U'),
                    'show_anyway' => 0,
                ],
            ],
        ];

        self::assertEquals(
            [
                $eventBegin->format('U') => new DateTimeResult($eventBegin, false),
                $tomorrow->format('U') => new DateTimeResult($tomorrow, false),
                $recurringEnd->format('U') => new DateTimeResult($recurringEnd, false),
            ],
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    #[Test]
    public function getDateTimeStorageForEventWithHiddenAddExceptionWillNotAddDayInStorage(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');
        $recurringEnd = $eventBegin->modify('+7 days');
        $tomorrow = $eventBegin->modify('tomorrow');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'recurring',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => (int)$recurringEnd->format('U'),
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 1,
            'each_months' => 0,
            'exceptions' => [
                1 => [
                    'hidden' => 1,
                    'exception_type' => 'Add',
                    'exception_date' => (int)$tomorrow->format('U'),
                    'show_anyway' => 0,
                ],
            ],
        ];

        self::assertEquals(
            [
                $eventBegin->format('U') => new DateTimeResult($eventBegin, false),
                $recurringEnd->format('U') => new DateTimeResult($recurringEnd, false),
            ],
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    #[Test]
    public function getDateTimeStorageForEventWithAddExceptionOutOfAllowedRangeDoesNotAddDayInStorage(): void
    {
        $eventBegin = new \DateTimeImmutable('today midnight');
        $lastYear = $eventBegin->modify('-1 year');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'single',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => 0,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => [
                1 => [
                    'hidden' => 0,
                    'exception_type' => 'Add',
                    'exception_date' => (int)$lastYear->format('U'),
                    'show_anyway' => 0,
                ],
            ],
        ];

        $dateTimeEntries = $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy();

        // assertEquals will only check for correct dates, but not for different timezoneTypes
        self::assertEquals(
            [
                $eventBegin->format('U') => new DateTimeResult($eventBegin, false),
            ],
            $dateTimeEntries,
        );

        foreach ($dateTimeEntries as $dateTimeResult) {
            self::assertIsArray($dateTimeResult->getDate()->getTimezone()->getLocation());
        }
    }

    #[Test]
    public function getDateTimeStorageForEventWithRemoveExceptionRemovesOneDayFromStorage(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');
        $tomorrow = $eventBegin->modify('tomorrow');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'duration',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => (int)$tomorrow->format('U'),
            'recurring_end' => 0,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => [
                1 => [
                    'hidden' => 0,
                    'exception_type' => 'Remove',
                    'exception_date' => (int)$eventBegin->format('U'),
                    'show_anyway' => 0,
                ],
            ],
        ];

        self::assertEquals(
            [
                $tomorrow->format('U') => new DateTimeResult($tomorrow, false),
            ],
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    #[Test]
    public function getDateTimeStorageForEventWithRemoveExceptionButShowAnywayWillNotRemoveDayFromStorage(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');
        $tomorrow = $eventBegin->modify('tomorrow');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'duration',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => (int)$tomorrow->format('U'),
            'recurring_end' => 0,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => [
                1 => [
                    'hidden' => 0,
                    'exception_type' => 'Remove',
                    'exception_date' => (int)$eventBegin->format('U'),
                    'show_anyway' => 1,
                ],
            ],
        ];

        self::assertEquals(
            [
                $eventBegin->format('U') => new DateTimeResult($eventBegin, true),
                $tomorrow->format('U') => new DateTimeResult($tomorrow, false),
            ],
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    #[Test]
    public function getDateTimeStorageForEventWithTimeExceptionWillNotAddDateTimeToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');
        $tomorrow = $eventBegin->modify('tomorrow');
        $exceptionDate = new \DateTimeImmutable('+4 days midnight');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'duration',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => (int)$tomorrow->format('U'),
            'recurring_end' => 0,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => [
                1 => [
                    'hidden' => 0,
                    'exception_type' => 'Time',
                    'exception_date' => (int)$exceptionDate->format('U'),
                    'show_anyway' => 0,
                ],
            ],
        ];

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = new DateTimeResult($eventBegin, false);
        $expectedDays[$tomorrow->format('U')] = new DateTimeResult($tomorrow, false);

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    #[Test]
    public function getDateTimeStorageForEventWithInfoExceptionWillNotAddDateTimeToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');
        $tomorrow = $eventBegin->modify('tomorrow');
        $exceptionDate = new \DateTimeImmutable('+4 days midnight');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'duration',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => (int)$tomorrow->format('U'),
            'recurring_end' => 0,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => [
                1 => [
                    'hidden' => 0,
                    'exception_type' => 'Info',
                    'exception_date' => (int)$exceptionDate->format('U'),
                    'show_anyway' => 0,
                ],
            ],
        ];

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = new DateTimeResult($eventBegin, false);
        $expectedDays[$tomorrow->format('U')] = new DateTimeResult($tomorrow, false);

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }

    #[Test]
    public function getDateTimeStorageForEventWithInvalidExceptionThrowsException(): void
    {
        $this->loggerMock
            ->expects(self::atLeastOnce())
            ->method('error')
            ->willReturnMap([
                [self::stringContains('Error occurred while building DateTime objects'), null],
                [self::stringContains('Type "Invalid value" is no valid exception type'), null],
            ]);

        $eventBegin = new \DateTimeImmutable('midnight');
        $tomorrow = $eventBegin->modify('tomorrow');
        $exceptionDate = new \DateTimeImmutable('+4 days midnight');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'duration',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => (int)$tomorrow->format('U'),
            'recurring_end' => 0,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => [
                1 => [
                    'hidden' => 0,
                    'exception_type' => 'Invalid value',
                    'exception_date' => (int)$exceptionDate->format('U'),
                    'show_anyway' => 0,
                ],
            ],
        ];

        self::assertEquals(
            [],
            $this->subject->getDateTimeStorageForEventRecord($eventRecord)->getDateTimeResultStorageSorted()->getArrayCopy(),
        );
    }
}
