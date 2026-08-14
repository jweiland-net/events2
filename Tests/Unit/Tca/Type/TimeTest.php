<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Tca\Type;

use JWeiland\Events2\Converter\TimeToStringConverter;
use JWeiland\Events2\Tca\Type\Time;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class TimeTest extends UnitTestCase
{
    protected Time $subject;

    protected function setUp(): void
    {
        parent::setUp();

        date_default_timezone_set(Events2Constants::PHP_TIMEZONE);

        $this->subject = new Time(new TimeToStringConverter());
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    /**
     * DataProvider for times with times which will not be modified.
     *
     * @return array<string, array<string>>
     */
    public static function unmodifiedTimesDataProvider(): array
    {
        return ['empty values' => [''], 'earliest two digit time' => ['00:00'], 'latest two digit time' => ['23:59'], 'random two digit time' => ['21:34']];
    }

    #[Test]
    #[DataProvider('unmodifiedTimesDataProvider')]
    public function evaluateWithTimesWhichWillNotBeModified(string $unmodifiedTime): void
    {
        self::assertSame(
            $unmodifiedTime,
            $this->subject->evaluateFieldValue($unmodifiedTime),
        );
    }

    /**
     * @return array<string, array<string>>
     */
    public static function unpaddedTimesDataProvider(): array
    {
        return ['zero values' => ['0:0', '00:00'], 'one digit hour' => ['5:32', '05:32'], 'one digit minute' => ['12:8', '12:08'], 'one digit for both' => ['4:9', '04:09']];
    }

    #[Test]
    #[DataProvider('unpaddedTimesDataProvider')]
    public function evaluateWithTimesWhichWillAddPaddings(string $unpaddedTimes, string $paddedTimes): void
    {
        self::assertSame(
            $paddedTimes,
            $this->subject->evaluateFieldValue($unpaddedTimes),
        );
    }

    /**
     * @return array<string, array<string>>
     */
    public static function tooHighTimesDataProvider(): array
    {
        return ['edge case midnight' => ['24:00', '24:00'], 'edge case one minute after midnight' => ['24:01', '23:01'], 'edge case with 60 minutes' => ['15:60', '15:59'], 'too high hour' => ['67:43', '23:43'], 'too high minute' => ['19:543', '19:59'], 'everything is too high' => ['63:754', '23:59']];
    }

    #[Test]
    #[DataProvider('tooHighTimesDataProvider')]
    public function evaluateWithTooHighTimeValues(string $tooHighTime, string $normalizedTime): void
    {
        self::assertSame(
            $normalizedTime,
            $this->subject->evaluateFieldValue($tooHighTime),
        );
    }

    /**
     * @return array<string, array<string>>
     */
    public static function invalidTimesDataProvider(): array
    {
        return ['invalid value: Stefan' => ['Stefan', ''], 'invalid value: 123Test' => ['123Test', '']];
    }

    #[Test]
    #[DataProvider('invalidTimesDataProvider')]
    public function evaluateWithInvalidValues(string $invalidTime, string $expectedTime): void
    {
        self::assertSame(
            $expectedTime,
            $this->subject->evaluateFieldValue($invalidTime),
        );
    }

    /**
     * @return array<string, array<int|string>>
     */
    public static function dataProviderForVariousIntegerValues(): array
    {
        return ['edge case with zero' => [0, '00:00'], 'integers smaller than 24 will be interpreted as hours' => [3, '03:00'], 'integers smaller than 60 will be corrected to 23 hours' => [55, '23:00'], '60 will be interpreted as one minute' => [60, '00:01'], 'edge case with midnight' => [60 * 60 * 24, '23:59'], 'edge case with midnight - 1 second' => [(60 * 60 * 24) - 1, '23:59'], 'timestamp with padded zero in front' => [60 * 60 * 7 + 60 * 3, '07:03'], 'timestamp in the morning' => [60 * 60 * 8 + 60 * 27, '08:27'], 'timestamp at launch' => [60 * 60 * 12 + 60 * 12, '12:12'], 'timestamp in the afternoon' => [60 * 60 * 19 + 60 * 13, '19:13']];
    }

    #[Test]
    #[DataProvider('dataProviderForVariousIntegerValues')]
    public function evaluateWithInteger(int $intValue, string $expectedTime): void
    {
        self::assertSame(
            $expectedTime,
            $this->subject->evaluateFieldValue($intValue),
        );
    }
}
