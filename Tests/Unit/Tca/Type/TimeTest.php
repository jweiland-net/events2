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
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class TimeTest extends UnitTestCase
{
    protected Time $subject;

    protected function setUp(): void
    {
        $this->subject = new Time(new TimeToStringConverter());
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject
        );

        parent::tearDown();
    }

    /**
     * DataProvider for times with times which will not be modified.
     *
     * @return array<string, array<string>>
     */
    public function unmodifiedTimesDataProvider(): array
    {
        $times = [];
        $times['empty values'] = [''];
        $times['earliest two digit time'] = ['00:00'];
        $times['latest two digit time'] = ['23:59'];
        $times['random two digit time'] = ['21:34'];

        return $times;
    }

    /**
     * @test
     *
     * @param $unmodifiedTime
     * @dataProvider unmodifiedTimesDataProvider
     */
    public function evaluateWithTimesWhichWillNotBeModified($unmodifiedTime): void
    {
        self::assertSame(
            $unmodifiedTime,
            $this->subject->evaluateFieldValue($unmodifiedTime),
        );
    }

    /**
     * @return array<string, array<string>>
     */
    public function unpaddedTimesDataProvider(): array
    {
        $times = [];
        $times['zero values'] = ['0:0', '00:00'];
        $times['one digit hour'] = ['5:32', '05:32'];
        $times['one digit minute'] = ['12:8', '12:08'];
        $times['one digit for both'] = ['4:9', '04:09'];

        return $times;
    }

    /**
     * @test
     *
     * @param $unpaddedTimes
     * @param $paddedTimes
     * @dataProvider unpaddedTimesDataProvider
     */
    public function evaluateWithTimesWhichWillAddPaddings($unpaddedTimes, $paddedTimes): void
    {
        self::assertSame(
            $paddedTimes,
            $this->subject->evaluateFieldValue($unpaddedTimes),
        );
    }

    /**
     * @return array<string, array<string>>
     */
    public function tooHighTimesDataProvider(): array
    {
        $times = [];
        $times['edge case midnight'] = ['24:00', '24:00'];
        $times['edge case one minute after midnight'] = ['24:01', '23:01'];
        $times['edge case with 60 minutes'] = ['15:60', '15:59'];
        $times['too high hour'] = ['67:43', '23:43'];
        $times['too high minute'] = ['19:543', '19:59'];
        $times['everything is too high'] = ['63:754', '23:59'];

        return $times;
    }

    /**
     * @test
     *
     * @dataProvider tooHighTimesDataProvider
     */
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
    public function invalidTimesDataProvider(): array
    {
        $times = [];
        $times['invalid value: Stefan'] = ['Stefan', ''];
        $times['invalid value: 123Test'] = ['123Test', ''];

        return $times;
    }

    /**
     * @test
     *
     * @dataProvider invalidTimesDataProvider
     */
    public function evaluateWithInvalidValues($invalidTime, $expectedTime): void
    {
        self::assertSame(
            $expectedTime,
            $this->subject->evaluateFieldValue($invalidTime),
        );
    }

    /**
     * @return array<string, array<int|string>>
     */
    public function dateProviderForVariousIntegerValues(): array
    {
        $timestamps = [];
        $timestamps['edge case with zero'] = [0, '00:00'];
        $timestamps['integers smaller than 24 will be interpreted as hours'] = [3, '03:00'];
        $timestamps['integers smaller than 60 will be corrected to 23 hours'] = [55, '23:00'];
        $timestamps['60 will be interpreted as one minute'] = [60, '00:01'];
        $timestamps['edge case with midnight'] = [60 * 60 * 24, '23:59'];
        $timestamps['edge case with midnight - 1 second'] = [(60 * 60 * 24) - 1, '23:59'];
        $timestamps['timestamp with padded zero in front'] = [60 * 60 * 7 + 60 * 3, '07:03'];
        $timestamps['timestamp in the morning'] = [60 * 60 * 8 + 60 * 27, '08:27'];
        $timestamps['timestamp at launch'] = [60 * 60 * 12 + 60 * 12, '12:12'];
        $timestamps['timestamp in the afternoon'] = [60 * 60 * 19 + 60 * 13, '19:13'];

        return $timestamps;
    }

    /**
     * @test
     *
     * @dataProvider dateProviderForVariousIntegerValues
     */
    public function evaluateWithInteger(int $intValue, string $expectedTime): void
    {
        self::assertSame(
            $expectedTime,
            $this->subject->evaluateFieldValue($intValue),
        );
    }
}
