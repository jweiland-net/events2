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
use JWeiland\Events2\Service\DayGeneratorService;
use JWeiland\Events2\Utility\DateTimeUtility;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case for class \JWeiland\Events2\Service\DayGeneratorService.
 */
class DayGeneratorServiceTest extends FunctionalTestCase
{
    use ProphecyTrait;

    protected DayGeneratorService $subject;

    protected ExtConf $extConf;

    /**
     * @var LoggerInterface|ObjectProphecy
     */
    protected $loggerProphecy;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2'
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // We have to use GeneralUtility::makeInstance here because constructor arguments
        $this->extConf = GeneralUtility::makeInstance(ExtConf::class);
        $this->extConf->setRecurringFuture(12);

        $this->loggerProphecy = $this->prophesize(Logger::class);

        // We have to use GeneralUtility::makeInstance here because of LoggerAwareInterface
        $this->subject = new DayGeneratorService(
            GeneralUtility::makeInstance(EventDispatcher::class),
            $this->extConf,
            new DateTimeUtility()
        );

        $this->subject->setLogger($this->loggerProphecy->reveal());
    }

    protected function tearDown(): void
    {
        unset($this->subject);

        parent::tearDown();
    }

    /**
     * @test
     */
    public function getDateTimeStorageForEventWithMissingEventTypeThrowsException(): void
    {
        $this->loggerProphecy
            ->error(
                Argument::allOf(
                    Argument::containingString('Error occurred while building DateTime objects'),
                    Argument::containingString('It does not contain mandatory property: event_type'),
                )
            )
            ->shouldBeCalled();

        $eventRecord = [
            'uid' => 123
        ];

        self::assertSame(
            [],
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     */
    public function getDateTimeStorageForEventWithMissingEventBeginThrowsException(): void
    {
        $this->loggerProphecy
            ->error(
                Argument::allOf(
                    Argument::containingString('Error occurred while building DateTime objects'),
                    Argument::containingString('It does not contain mandatory property: event_begin'),
                )
            )
            ->shouldBeCalled();

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'duration'
        ];

        self::assertSame(
            [],
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     */
    public function getDateTimeStorageForEventWithEmptyEventTypeThrowsException(): void
    {
        $this->loggerProphecy
            ->error(
                Argument::allOf(
                    Argument::containingString('Error occurred while building DateTime objects'),
                    Argument::containingString('Column "event_type" can not be empty'),
                )
            )
            ->shouldBeCalled();

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
            'exceptions' => 0,
        ];

        self::assertSame(
            [],
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     */
    public function getDateTimeStorageForEventWithEmptyEventEndForDurationalEventsThrowsException(): void
    {
        $this->loggerProphecy
            ->error(
                Argument::allOf(
                    Argument::containingString('Error occurred while building DateTime objects'),
                    Argument::containingString('If event_type is set to "duration" column "event_end" has to be set'),
                )
            )
            ->shouldBeCalled();

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
            'exceptions' => 0,
        ];

        self::assertSame(
            [],
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     */
    public function getDateTimeStorageForEventWithEmptyEventBeginThrowsException(): void
    {
        $this->loggerProphecy
            ->error(
                Argument::allOf(
                    Argument::containingString('Error occurred while building DateTime objects'),
                    Argument::containingString('Column "event_begin" can not be empty'),
                )
            )
            ->shouldBeCalled();

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
            'exceptions' => 0,
        ];

        self::assertSame(
            [],
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     */
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
            'exceptions' => 0,
        ];

        $dateTimeEntries = $this->subject->getDateTimeStorageForEvent($eventRecord);
        foreach ($dateTimeEntries as $dateTimeEntry) {
            self::assertSame(
                '00:00:00',
                $dateTimeEntry->getDate()->format('H:i:s')
            );
        }
    }

    /**
     * @test
     */
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
            'exceptions' => 0,
        ];

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = new DateTimeEntry($eventBegin, false);
        $expectedDays[$nextWeek->format('U')] = new DateTimeEntry($nextWeek, false);
        $expectedDays[$recurringEnd->format('U')] = new DateTimeEntry($recurringEnd, false);

        $dateTimeEntries = $this->subject->getDateTimeStorageForEvent($eventRecord);
        self::assertEquals(
            $expectedDays,
            $dateTimeEntries
        );
    }

    /**
     * @test
     */
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
            'exceptions' => 0,
        ];

        $dateTimeEntries = $this->subject->getDateTimeStorageForEvent($eventRecord);
        foreach ($dateTimeEntries as $dateTimeEntry) {
            self::assertIsArray($dateTimeEntry->getDate()->getTimezone()->getLocation());
        }
    }

    /**
     * @test
     */
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
            'exceptions' => 0,
        ];

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = new DateTimeEntry($eventBegin, false);
        $expectedDays[$recurringEnd->format('U')] = new DateTimeEntry($recurringEnd, false);

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * As the earliest date for summer- to wintertime switches is the 25. october,
     * calculated for the next 30 year, I have chosen the 20.10. to be safe.
     *
     * @test
     */
    public function getDateTimeStorageForEventWithRecurringWeeksWillKeepDaylightSavingTime(): void
    {
        $timestampEventBeginSummerTime = mktime(0, 0, 0, 10, 20, 2017);
        $timestampRecurringEndWinterTime = mktime(0, 0, 0, 11, 3, 2017);

        // This test has to build days in the past. To allow this we have to set recurring past to a high value.
        // Maybe you have to update this value or the year above in future
        // ({current year} - {year from above: 2017}) * 12 months + add some extra months = value for recurring past
        $this->extConf->setRecurringPast(100);

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
            'exceptions' => 0,
        ];

        // Adding the correct timezone (Europe/Berlin). It will know the timezone from now on until forever
        $expectedBegin = new \DateTimeImmutable(date('c', $timestampEventBeginSummerTime));
        $expectedBegin = $expectedBegin->setTimezone(new \DateTimeZone('Europe/Berlin'));
        $expectedEnd = $expectedBegin->modify('+14 days');

        self::assertEquals(
            [
                $expectedBegin->format('U') => new DateTimeEntry($expectedBegin, false),
                $expectedEnd->format('U') => new DateTimeEntry($expectedEnd, false)
            ],
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     */
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
            'exceptions' => 0,
        ];

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = new DateTimeEntry($eventBegin, false);
        $expectedDays[$nextMonth->format('U')] = new DateTimeEntry($nextMonth, false);
        $expectedDays[$recurringEnd->format('U')] = new DateTimeEntry($recurringEnd, false);

        $this->extConf->setRecurringPast(1);

        $dateTimeEntries = $this->subject->getDateTimeStorageForEvent($eventRecord);
        self::assertEquals(
            $expectedDays,
            $dateTimeEntries
        );

        // test for correct TimezoneType, else times are not DST save
        foreach ($dateTimeEntries as $dateTimeEntry) {
            self::assertIsArray($dateTimeEntry->getDate()->getTimezone()->getLocation());
        }
    }

    /**
     * @test
     */
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
            'exceptions' => 0,
        ];

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = new DateTimeEntry($eventBegin, false);
        $expectedDays[$recurringEnd->format('U')] = new DateTimeEntry($recurringEnd, false);

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * There is a special condition in DayGenerator::getDateToStartCalculatingFrom why we have to do this test
     *
     * @test
     */
    public function getDateTimeStorageForEventWithRecurringOverTwoMonthsAndVeryEarlyEventDateAddsDayToStorage(): void
    {
        $this->extConf->setRecurringPast(3);

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
            'exceptions' => 0,
        ];

        $expectedDays = [];
        $expectedDays[$recurringEnd->format('U')] = new DateTimeEntry($recurringEnd, false);

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     */
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
            'exceptions' => 0,
        ];

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = new DateTimeEntry($eventBegin, false);
        $expectedDays[$nextEvent->format('U')] = new DateTimeEntry($nextEvent, false);
        $expectedDays[$lastEvent->format('U')] = new DateTimeEntry($lastEvent, false);

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     */
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
            'exceptions' => 0,
        ];

        self::assertEquals(
            [
                $eventBegin->format('U') => new DateTimeEntry($eventBegin, false)
            ],
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     */
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
            'exceptions' => 0,
        ];

        $tempDate = $eventBegin;
        $expectedDays = [];
        $tempDate = $tempDate->modify('+1 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeEntry($tempDate, false); // add sunday
        $tempDate = $tempDate->modify('+1 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeEntry($tempDate, false); // add monday
        $tempDate = $tempDate->modify('+1 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeEntry($tempDate, false); // add tuesday
        $tempDate = $tempDate->modify('+1 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeEntry($tempDate, false); // add wednesday
        $tempDate = $tempDate->modify('+2 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeEntry($tempDate, false); // add friday
        $tempDate = $tempDate->modify('+2 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeEntry($tempDate, false); // add sunday
        ksort($expectedDays);

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * This test also checks for leap years.
     *
     * @test
     */
    public function getDateTimeStorageForEventWithGivenXthsResultsInAddedDaysInStorage(): void
    {
        // $eventBegin has to start with a month beginning with a thursday
        $eventBegin = new \DateTimeImmutable('first day of next month midnight');
        while ((int)$eventBegin->format('N') !== 4) {
            $eventBegin = $eventBegin->modify('next month');
        }
        $eventBegin = $eventBegin->modify('+16 days'); // first day of month (1) + 16 = 17. of month. Must be saturday
        $recurringEnd = $eventBegin->modify('+22 days'); // 07th or 08th of next month. Regarding, if month has 30 or 31 days

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'recurring',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => (int)$recurringEnd->format('U'),
            'xth' => 21, // 1st, 3rd, 5th
            'weekday' => 18, // tuesday, friday
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => 0,
        ];

        $tempDate = $eventBegin;
        $expectedDays = [];
        $tempDate = $tempDate->modify('+3 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeEntry($tempDate, false); // add 3rd tuesday 20th of month
        $tempDate = $tempDate->modify('+10 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeEntry($tempDate, false); // add 5th friday 30th of month
        $tempDate = $tempDate->modify('+4 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeEntry($tempDate, false); // add 1st tuesday 3rd of next month
        $tempDate = $tempDate->modify('+3 day');
        $expectedDays[$tempDate->format('U')] = new DateTimeEntry($tempDate, false); // add 1st friday 6th of next month
        ksort($expectedDays);

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     */
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
            'exceptions' => 0,
        ];

        $tempDate = $eventBegin;
        $expectedDays = [];
        for ($i = 0; $i < 5; ++$i) {
            $expectedDays[$tempDate->format('U')] = new DateTimeEntry($tempDate, false);
            $tempDate = $tempDate->modify('+1 day');
        }

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     */
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
            'exceptions' => 0,
        ];

        self::assertEquals(
            [
                $eventBegin->format('U') => new DateTimeEntry($eventBegin, false)
            ],
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     */
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
                ]
            ],
        ];

        self::assertEquals(
            [
                $eventBegin->format('U') => new DateTimeEntry($eventBegin, false),
                $tomorrow->format('U') => new DateTimeEntry($tomorrow, false),
                $recurringEnd->format('U') => new DateTimeEntry($recurringEnd, false),
            ],
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     */
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
                ]
            ],
        ];

        self::assertEquals(
            [
                $eventBegin->format('U') => new DateTimeEntry($eventBegin, false),
                $recurringEnd->format('U') => new DateTimeEntry($recurringEnd, false)
            ],
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     */
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
                ]
            ],
        ];

        $dateTimeEntries = $this->subject->getDateTimeStorageForEvent($eventRecord);

        // assertEquals will only check for correct dates, but not for different timezoneTypes
        self::assertEquals(
            [
                $eventBegin->format('U') => new DateTimeEntry($eventBegin, false)
            ],
            $dateTimeEntries
        );

        foreach ($dateTimeEntries as $dateTimeEntry) {
            self::assertIsArray($dateTimeEntry->getDate()->getTimezone()->getLocation());
        }
    }

    /**
     * @test
     */
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
                ]
            ],
        ];

        self::assertEquals(
            [
                $tomorrow->format('U') => new DateTimeEntry($tomorrow, false)
            ],
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     */
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
                ]
            ],
        ];

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = new DateTimeEntry($eventBegin, false);
        $expectedDays[$tomorrow->format('U')] = new DateTimeEntry($tomorrow, false);

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     */
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
                ]
            ],
        ];

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = new DateTimeEntry($eventBegin, false);
        $expectedDays[$tomorrow->format('U')] = new DateTimeEntry($tomorrow, false);

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     */
    public function getDateTimeStorageForEventWithInvalidExceptionThrowsException(): void
    {
        $this->loggerProphecy
            ->error(
                Argument::allOf(
                    Argument::containingString('Error occurred while building DateTime objects'),
                    Argument::containingString('Type "Invalid value" is no valid exception type'),
                )
            )
            ->shouldBeCalled();

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
                ]
            ],
        ];

        self::assertEquals(
            [],
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }
}
