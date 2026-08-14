<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Converter;

use JWeiland\Events2\Converter\TimeToStringConverter;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class TimeToStringConverterTest extends UnitTestCase
{
    protected TimeToStringConverter $subject;

    protected function setUp(): void
    {
        parent::setUp();

        date_default_timezone_set(Events2Constants::PHP_TIMEZONE);

        $this->subject = new TimeToStringConverter();
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    /**
     * dataProvider for too high int values.
     *
     * @return array<string, array<int>>
     */
    public static function dataProviderForTooHighIntegerValues(): array
    {
        return ['one second too high' => [60 * 60 * 24], 'some seconds too high' => [60 * 60 * 24 * 7], 'one year too high' => [60 * 60 * 24 * 365]];
    }

    #[Test]
    #[DataProvider('dataProviderForTooHighIntegerValues')]
    public function convertWithTooHighIntegerValues(int $tooHighIntegerValue): void
    {
        self::assertSame(
            '23:59',
            $this->subject->convert($tooHighIntegerValue),
        );
    }

    /**
     * dataProvider for too low int values.
     *
     * @return array<string, array<int>>
     */
    public static function dataProviderForTooLowIntegerValues(): array
    {
        return ['edge case with zero' => [0], 'edge case with -1' => [-1], 'higher negative value' => [-123]];
    }

    #[Test]
    #[DataProvider('dataProviderForTooLowIntegerValues')]
    public function convertWithTooLowIntergerValues(int $tooLowIntegerValue): void
    {
        self::assertSame(
            '00:00',
            $this->subject->convert($tooLowIntegerValue),
        );
    }

    /**
     * dataProvider for timestamps in allowed range.
     *
     * @return array<string, array<int|string>>
     */
    public static function dataProviderForTimestampsInAllowedRange(): array
    {
        return ['edge case with zero' => [0, '00:00'], 'edge case with one second' => [1, '00:00'], 'edge case with one minute' => [60, '00:01'], 'edge case with midnight' => [60 * 60 * 24, '23:59'], 'edge case with midnight - 1 second' => [(60 * 60 * 24) - 1, '23:59'], 'timestamp with padded zero in front' => [60 * 60 * 5 + 60 * 7, '05:07'], 'timestamp in the morning' => [60 * 60 * 10 + 60 * 43, '10:43'], 'timestamp at launch' => [60 * 60 * 12, '12:00'], 'timestamp in the afternoon' => [60 * 60 * 17 + 60 * 52, '17:52']];
    }

    #[Test]
    #[DataProvider('dataProviderForTimestampsInAllowedRange')]
    public function convertWithIntegersInAllowedRangeResultsInStringInterpretation(
        int $timestampInRange,
        string $expectedTime,
    ): void {
        self::assertSame(
            $expectedTime,
            $this->subject->convert($timestampInRange),
        );
    }
}
