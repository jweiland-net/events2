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
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test case.
 */
class TimeToStringConverterTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Converter\TimeToStringConverter
     */
    protected $subject;

    public function setUp(): void
    {
        $this->subject = new TimeToStringConverter();
    }

    public function tearDown(): void
    {
        unset($this->subject);
    }

    /**
     * dataProvider for too high int values.
     *
     * @return array
     */
    public function dataProviderForTooHighIntegerValues(): array
    {
        $timestamps = [];
        $timestamps['one second too high'] = [60 * 60 * 24];
        $timestamps['some seconds too high'] = [60 * 60 * 24 * 7];
        $timestamps['one year too high'] = [60 * 60 * 24 * 365];

        return $timestamps;
    }

    /**
     * @test
     *
     * @param int $tooHighIntegerValue
     * @dataProvider dataProviderForTooHighIntegerValues
     */
    public function convertWithTooHighIntergerValues($tooHighIntegerValue): void
    {
        self::assertSame(
            '23:59',
            $this->subject->convert($tooHighIntegerValue)
        );
    }

    /**
     * dataProvider for too low int values.
     *
     * @return array
     */
    public function dataProviderForTooLowIntegerValues(): array
    {
        $timestamps = [];
        $timestamps['edge case with zero'] = [0];
        $timestamps['edge case with -1'] = [-1];
        $timestamps['higher negative value'] = [-123];

        return $timestamps;
    }

    /**
     * @test
     *
     * @param int $tooLowIntegerValue
     * @dataProvider dataProviderForTooLowIntegerValues
     */
    public function convertWithTooLowIntergerValues($tooLowIntegerValue): void
    {
        self::assertSame(
            '00:00',
            $this->subject->convert($tooLowIntegerValue)
        );
    }

    /**
     * dataProvider for timestamps in allowed range.
     *
     * @return array
     */
    public function dataProviderForTimestampsInAllowedRange(): array
    {
        $timestamps = [];
        $timestamps['edge case with zero'] = [0, '00:00'];
        $timestamps['edge case with one second'] = [1, '00:00'];
        $timestamps['edge case with one minute'] = [60, '00:01'];
        $timestamps['edge case with midnight'] = [60 * 60 * 24, '23:59'];
        $timestamps['edge case with midnight - 1 second'] = [(60 * 60 * 24) - 1, '23:59'];
        $timestamps['timestamp with padded zero in front'] = [60 * 60 * 5 + 60 * 7, '05:07'];
        $timestamps['timestamp in the morning'] = [60 * 60 * 10 + 60 * 43, '10:43'];
        $timestamps['timestamp at launch'] = [60 * 60 * 12, '12:00'];
        $timestamps['timestamp in the afternoon'] = [60 * 60 * 17 + 60 * 52, '17:52'];

        return $timestamps;
    }

    /**
     * @test
     *
     * @param int    $timestampInRange
     * @param string $expectedTime
     * @dataProvider dataProviderForTimestampsInAllowedRange
     */
    public function convertWithIntegersInAllowedRangeResultsInStringInterpretation(
        $timestampInRange,
        $expectedTime
    ): void {
        self::assertSame(
            $expectedTime,
            $this->subject->convert($timestampInRange)
        );
    }
}
