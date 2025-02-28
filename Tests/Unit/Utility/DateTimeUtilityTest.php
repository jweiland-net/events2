<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Utility;

use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class DateTimeUtilityTest extends UnitTestCase
{
    protected DateTimeUtility $subject;

    protected function setUp(): void
    {
        parent::setUp();

        // The resulting dates expecting dates for germany
        date_default_timezone_set('Europe/Berlin');

        $this->subject = new DateTimeUtility();
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    public static function emptyDatesDataProvider(): array
    {
        $emptyDate = [];
        $emptyDate['empty value: null'] = [null];
        $emptyDate['empty value: 0000-00-00'] = ['0000-00-00'];
        $emptyDate['empty value: 0000-00-00 00:00:00'] = ['0000-00-00 00:00:00'];

        return $emptyDate;
    }

    #[Test]
    #[DataProvider('emptyDatesDataProvider')]
    public function convertEmptyDatesResultsInNull(?string $emptyDate): void
    {
        self::assertNull(
            $this->subject->convert($emptyDate),
        );
    }

    /**
     * dataProvider with invalid values for DateTime objects.
     */
    public static function dataProviderWithInvalidValuesForDateTimeObjects(): array
    {
        $invalidValues = [];
        $invalidValues['string'] = ['Hello'];
        $invalidValues['boolean'] = [true];
        $invalidValues['object'] = [new \stdClass()];
        $invalidValues['null'] = [null];
        $invalidValues['array'] = [[123]];

        return $invalidValues;
    }

    #[Test]
    #[DataProvider('dataProviderWithInvalidValuesForDateTimeObjects')]
    public function convertInvalidDates($invalidValue): void
    {
        self::assertNull(
            $this->subject->convert($invalidValue),
        );
    }

    public static function stringDatesDataProvider(): array
    {
        $dateTimeZone = new \DateTimeZone('Europe/Berlin');

        $dateStrings = [];

        $midnight = new \DateTimeImmutable('midnight', $dateTimeZone);
        $dateStrings['midnight'] = ['midnight', $midnight];

        $tomorrow = new \DateTimeImmutable('tomorrow midnight', $dateTimeZone);
        $dateStrings['tomorrow'] = ['tomorrow', $tomorrow];

        $lastDayOfMonth = new \DateTimeImmutable('last day of this month midnight', $dateTimeZone);
        $dateStrings['last day of this month'] = ['last day of this month', $lastDayOfMonth];

        return $dateStrings;
    }

    #[Test]
    #[DataProvider('stringDatesDataProvider')]
    public function convertWithStringDates($stringDate, $expectedDate): void
    {
        self::assertEquals(
            $expectedDate,
            $this->subject->convert($stringDate),
        );
    }

    /**
     * @return array
     */
    public static function timestampDataProvider(): array
    {
        $dateTimeZone = new \DateTimeZone('Europe/Berlin');

        $timestamps = [];
        $timestamps['timestamp: 0'] = [0, null];
        $timestamps['timestamp: 1'] = [1, \DateTimeImmutable::createFromFormat('d.m.Y H:i:s', '01.01.1970 00:00:00', $dateTimeZone)];
        $timestamps['timestamp: 12345'] = [12345, \DateTimeImmutable::createFromFormat('d.m.Y H:i:s', '01.01.1970 00:00:00', $dateTimeZone)];
        $timestamps['timestamp: 123456789'] = [123456789, \DateTimeImmutable::createFromFormat('d.m.Y H:i:s', '29.11.1973 00:00:00', $dateTimeZone)];
        $timestamps['timestamp: 1234567890'] = [1234567890, \DateTimeImmutable::createFromFormat('d.m.Y H:i:s', '14.02.2009 00:00:00', $dateTimeZone)];
        if (strlen(decbin(~0)) === 64) {
            // this is only for 64bit OS
            $timestamps['timestamp: 13000000000'] = [13000000000, \DateTimeImmutable::createFromFormat('d.m.Y H:i:s', '15.12.2381 00:00:00', $dateTimeZone)];
            $timestamps['timestamp: 15000000000'] = [15000000000, \DateTimeImmutable::createFromFormat('d.m.Y H:i:s', '01.05.2445 00:00:00', $dateTimeZone)];
        }

        return $timestamps;
    }

    #[Test]
    #[DataProvider('timestampDataProvider')]
    public function convertTimestamps($timestamp, $expectedDate): void
    {
        $convertedResult = $this->subject->convert($timestamp);
        if (!$convertedResult instanceof \DateTimeImmutable) {
            self::assertSame($expectedDate, $convertedResult);
        } else {
            self::assertEquals(
                /* @var \DateTimeImmutable $expectedDate */
                $expectedDate->format('U'),
                $convertedResult->format('U'),
            );
        }
    }
}
