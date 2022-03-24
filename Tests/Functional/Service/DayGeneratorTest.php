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
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Service\DayGenerator;
use JWeiland\Events2\Utility\DateTimeUtility;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Test case for class \JWeiland\Events2\Service\DayGenerator.
 */
class DayGeneratorTest extends FunctionalTestCase
{
    protected DayGenerator $subject;

    protected ExtConf $extConf;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2'
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // We have to use GeneralUtility::makeInstance here because of ExtensionConfiguration class
        $this->extConf = GeneralUtility::makeInstance(ExtConf::class);
        $this->extConf->setRecurringFuture(12);

        // We have to use GeneralUtility::makeInstance here because of LoggerAwareInterface
        $this->subject = GeneralUtility::makeInstance(
            DayGenerator::class,
            GeneralUtility::makeInstance(EventDispatcher::class),
            $this->extConf,
            new DateTimeUtility()
        );
    }

    protected function tearDown(): void
    {
        unset($this->subject);

        parent::tearDown();
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function getDateTimeStorageForEventWithInvalidEventRecordResultsInEmptyDateTimeStorage(): void
    {
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
     *
     * @throws \Exception
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

        $days = $this->subject->getDateTimeStorageForEvent($eventRecord);
        foreach ($days as $day) {
            self::assertSame(
                '00:00:00',
                $day->format('H:i:s')
            );
        }
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function getDateTimeStorageForEventWithRecurringOverEachWeekAddsThreeDaysToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin = $eventBegin->modify('midnight');
        $nextWeek = clone $eventBegin;
        $nextWeek = $nextWeek->modify('+7 days');
        $recurringEnd = clone $eventBegin;
        $recurringEnd = $recurringEnd->modify('+14 days');

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
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$nextWeek->format('U')] = $nextWeek;
        $expectedDays[$recurringEnd->format('U')] = $recurringEnd;

        $days = $this->subject->getDateTimeStorageForEvent($eventRecord);
        self::assertEquals(
            $expectedDays,
            $days
        );

        // test for correct TimezoneType, else times are not DST save
        foreach ($days as $day) {
            self::assertIsArray($day->getTimezone()->getLocation());
        }
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function getDateTimeStorageForEventWithRecurringOverTwoWeeksAddsDaysToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin = $eventBegin->modify('midnight');
        $recurringEnd = clone $eventBegin;
        $recurringEnd = $recurringEnd->modify('+14 days');

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
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$recurringEnd->format('U')] = $recurringEnd;

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * As the earliest date for summer- to wintertime switches is the 25. october
     * calculated for the next 30 year, I have chosen the 20.10. to be safe.
     *
     * @test
     *
     * @throws \Exception
     */
    public function getDateTimeStorageForEventWithRecurringWeeksWillKeepDaylightSavingTime(): void
    {
        $timestamp = mktime(0, 0, 0, 10, 20, 2017);
        // this test has to build days in the past. To allow this we have to set recurring past to a high value
        // Maybe you have to update this value or the year above in future
        $this->extConf->setRecurringPast(60);

        // These dates will be created with timezone_type = 1, which does know the timezone (+02:00) only from the current date
        $eventBegin = new \DateTimeImmutable(date('c', $timestamp));
        $recurringEnd = clone $eventBegin;
        $recurringEnd = $recurringEnd->modify('+14 days');

        // Adding the correct timezone (Europe/Berlin). It will know the timezone from now on until forever
        $expectedBegin = clone $eventBegin;
        $expectedBegin = $expectedBegin->setTimezone(new \DateTimeZone('Europe/Berlin'));
        $expectedEnd = clone $expectedBegin;
        $expectedEnd = $expectedEnd->modify('+14 days');

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
        $expectedDays[$expectedBegin->format('U')] = $expectedBegin;
        $expectedDays[$expectedEnd->format('U')] = $expectedEnd;

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function getDateTimeStorageForEventWithRecurringOverEachMonthAddsThreeDaysToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin = $eventBegin->modify('midnight')->modify('first day of this month')->modify('+25 days');
        $nextMonth = clone $eventBegin;
        $nextMonth = $nextMonth->modify('+1 months');
        $recurringEnd = clone $eventBegin;
        $recurringEnd = $recurringEnd->modify('+2 months');

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
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$nextMonth->format('U')] = $nextMonth;
        $expectedDays[$recurringEnd->format('U')] = $recurringEnd;

        $this->extConf->setRecurringPast(1);

        $days = $this->subject->getDateTimeStorageForEvent($eventRecord);
        self::assertEquals(
            $expectedDays,
            $days
        );

        // test for correct TimezoneType, else times are not DST save
        foreach ($days as $day) {
            self::assertIsArray($day->getTimezone()->getLocation());
        }
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function getDateTimeStorageForEventWithRecurringOverTwoMonthsAddsDaysToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin = $eventBegin->modify('midnight');
        $recurringEnd = clone $eventBegin;
        $recurringEnd = $recurringEnd->modify('+2 months');

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
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$recurringEnd->format('U')] = $recurringEnd;

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * There is a special condition in DayGenerator::getDateToStartCalculatingFrom why we have to do this test
     *
     * @test
     *
     * @throws \Exception
     */
    public function getDateTimeStorageForEventWithRecurringOverTwoMonthsAndVeryEarlyEventDateAddsDayToStorage(): void
    {
        $this->extConf->setRecurringPast(3);

        $eventBegin = new \DateTimeImmutable();
        $eventBegin = $eventBegin->modify('-4 months');
        $eventBegin = $eventBegin->modify('midnight');
        $recurringEnd = clone $eventBegin;
        $recurringEnd = $recurringEnd->modify('+2 months');

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
        $expectedDays[$recurringEnd->format('U')] = $recurringEnd;

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function getDateTimeStorageForEventWithRecurringOverEachMonthAndTwoWeeksAddsDaysToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin = $eventBegin->modify('midnight');
        $nextEvent = clone $eventBegin;
        $nextEvent = $nextEvent->modify('+1 months');
        $nextEvent = $nextEvent->modify('+2 weeks');
        $lastEvent = clone $nextEvent;
        $lastEvent = $lastEvent->modify('+1 months');
        $lastEvent = $lastEvent->modify('+2 weeks');
        $recurringEnd = clone $eventBegin;
        $recurringEnd = $recurringEnd->modify('+3 months');

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
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$nextEvent->format('U')] = $nextEvent;
        $expectedDays[$lastEvent->format('U')] = $lastEvent;

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function getDateTimeStorageForEventWithRecurringAndEqualEventBeginAndEventEndResultsInOneDayInStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin = $eventBegin->modify('midnight');
        $recurringEnd = clone $eventBegin;

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

        self::assertEquals(
            [$eventBegin->format('U') => $eventBegin],
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function getDateTimeStorageForEventWithGivenWeekdaysResultsInAddedDaysInStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin = $eventBegin->modify('midnight');
        $eventBegin = $eventBegin->modify('next saturday');
        $recurringEnd = clone $eventBegin;
        $recurringEnd = $recurringEnd->modify('+8 days');

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

        $tempDate = clone $eventBegin;
        $expectedDays = [];
        $tempDate = $tempDate->modify('+1 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add sunday
        $tempDate = $tempDate->modify('+1 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add monday
        $tempDate = $tempDate->modify('+1 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add tuesday
        $tempDate = $tempDate->modify('+1 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add wednesday
        $tempDate = $tempDate->modify('+2 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add friday
        $tempDate = $tempDate->modify('+2 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add sunday
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
     *
     * @throws \Exception
     */
    public function getDateTimeStorageForEventWithGivenXthsResultsInAddedDaysInStorage(): void
    {
        // $eventBegin has to start with a month beginning with a thursday
        $eventBegin = new \DateTimeImmutable('now');
        $eventBegin = $eventBegin->modify('first day of next month');
        while ((int)$eventBegin->format('N') !== 4) {
            $eventBegin = $eventBegin->modify('next month');
        }
        $eventBegin = $eventBegin->modify('midnight');
        $eventBegin = $eventBegin->modify('+16 days'); // first day of month (1) + 16 = 17. of month. Must be saturday
        $recurringEnd = clone $eventBegin;
        $recurringEnd = $recurringEnd->modify('+22 days'); // 07th or 08th of next month. Regarding, if month has 30 or 31 days

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

        $tempDate = clone $eventBegin;
        $expectedDays = [];
        $tempDate = $tempDate->modify('+3 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add 3rd tuesday 20th of month
        $tempDate = $tempDate->modify('+10 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add 5th friday 30th of month
        $tempDate = $tempDate->modify('+4 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add 1st tuesday 3rd of next month
        $tempDate = $tempDate->modify('+3 day');
        $expectedDays[$tempDate->format('U')] = clone $tempDate; // add 1st friday 6th of next month
        ksort($expectedDays);

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     *
     * @throws \Exception
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

        $tempDate = clone $eventBegin;
        $expectedDays = [];
        for ($i = 0; $i < 5; ++$i) {
            $expectedDays[$tempDate->format('U')] = clone $tempDate;
            $tempDate = $tempDate->modify('+1 day');
        }

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     *
     * @throws \Exception
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

        $expectedDays = [$eventBegin->format('U') => $eventBegin];

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function getDateTimeStorageForEventWithExceptionsInEventCallsAddExceptions(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin = $eventBegin->modify('midnight');

        $exception = new Exception();
        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'duration',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => 0,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => 1,
        ];

        self::assertSame(
            [],
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function getDateTimeStorageForEventWithAddExceptionAddsOneDayInStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin = $eventBegin->modify('midnight');
        $recurringEnd = new \DateTimeImmutable();
        $recurringEnd = $recurringEnd->modify('midnight');
        $recurringEnd = $recurringEnd->modify('+4 days');
        $tomorrow = clone $eventBegin;
        $tomorrow = $tomorrow->modify('tomorrow');

        $exceptionTime = new Time();
        $exceptionTime->setTimeBegin('18:00');

        $exception = new Exception();
        $exception->setExceptionType('Add');
        $exception->setExceptionDate($tomorrow);
        $exception->setExceptionTime($exceptionTime);
        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

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
            'exceptions' => 1,
        ];

        $tempDate = clone $eventBegin;
        $expectedDays = [];
        for ($i = 0; $i < 5; ++$i) {
            $expectedDays[$tempDate->format('U')] = clone $tempDate;
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
    public function getDateTimeStorageForEventWithAddExceptionOutOfAllowedRangeDoesNotAddDayInStorage(): void
    {
        $timestamp = mktime(0, 0, 0);

        // These dates will be created with timezone_type = 1, which does know the timezone (+02:00) only from the current date
        $eventBegin = new \DateTimeImmutable(date('c', $timestamp));
        $lastYear = clone $eventBegin;
        $lastYear = $lastYear->modify('-1 year');

        $exceptionTime = new Time();
        $exceptionTime->setTimeBegin('18:00');

        $exception = new Exception();
        $exception->setExceptionType('Add');
        $exception->setExceptionDate($lastYear);
        $exception->setExceptionTime($exceptionTime);
        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

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
            'exceptions' => 1,
        ];

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = $eventBegin;

        $dateTimeStorage = $this->subject->getDateTimeStorageForEvent($eventRecord);

        // assertEquals will only check for correct dates, but not for different timezoneTypes
        self::assertEquals(
            $expectedDays,
            $dateTimeStorage
        );

        foreach ($dateTimeStorage as $dateTime) {
            self::assertIsArray($dateTime->getTimezone()->getLocation());
        }
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function getDateTimeStorageForEventWithRemoveExceptionRemovesOneDayFromStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin = $eventBegin->modify('midnight');
        $tomorrow = clone $eventBegin;
        $tomorrow = $tomorrow->modify('tomorrow');

        $exception = new Exception();
        $exception->setExceptionType('Remove');
        $exception->setExceptionDate($eventBegin);
        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

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
            'exceptions' => 1,
        ];

        $expectedDays = [];
        $expectedDays[$tomorrow->format('U')] = $tomorrow;

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function getDateTimeStorageForEventWithTimeExceptionDoNotAddDayToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin = $eventBegin->modify('midnight');
        $tomorrow = clone $eventBegin;
        $tomorrow = $tomorrow->modify('tomorrow');

        $exception = new Exception();
        $exception->setExceptionType('Time');
        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

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
            'exceptions' => 1,
        ];

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$tomorrow->format('U')] = $tomorrow;

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function getDateTimeStorageForEventWithInfoExceptionDoNotAddDayToStorage(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin = $eventBegin->modify('midnight');
        $tomorrow = clone $eventBegin;
        $tomorrow = $tomorrow->modify('tomorrow');

        $exception = new Exception();
        $exception->setExceptionType('Info');
        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

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
            'exceptions' => 1,
        ];

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$tomorrow->format('U')] = $tomorrow;

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     *
     * @expectedException \Exception
     */
    public function getDateTimeStorageForEventWithInvalidExceptionThrowsException(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin = $eventBegin->modify('midnight');
        $tomorrow = clone $eventBegin;
        $tomorrow = $tomorrow->modify('tomorrow');

        $exception = new Exception();
        $exception->setExceptionType('Invalid Value');
        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

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
            'exceptions' => 1,
        ];

        $expectedDays = [];
        $expectedDays[$eventBegin->format('U')] = $eventBegin;
        $expectedDays[$tomorrow->format('U')] = $tomorrow;

        self::assertEquals(
            $expectedDays,
            $this->subject->getDateTimeStorageForEvent($eventRecord)
        );
    }
}
