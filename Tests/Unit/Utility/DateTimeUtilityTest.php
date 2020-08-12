<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Utility;

use JWeiland\Events2\Utility\DateTimeUtility;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test case.
 */
class DateTimeUtilityTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Utility\DateTimeUtility
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = new DateTimeUtility();
    }

    /**
     * tear down.
     */
    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @return array
     */
    public function emptyDatesDataProvider()
    {
        $emptyDate = [];
        $emptyDate['empty value: null'] = [null, null];
        $emptyDate['empty value: 0000-00-00'] = ['0000-00-00', null];
        $emptyDate['empty value: 0000-00-00 00:00:00'] = ['0000-00-00 00:00:00', null];

        return $emptyDate;
    }

    /**
     * @test
     *
     * @param $emptyDate
     * @param $expectedDate
     * @dataProvider emptyDatesDataProvider
     */
    public function convertEmptyDatesResultsInNull($emptyDate, $expectedDate)
    {
        self::assertSame(
            $expectedDate,
            $this->subject->convert($emptyDate)
        );
    }

    /**
     * dataProvider with invalid values for DateTime objects.
     *
     * @return array
     */
    public function dataProviderWithInvalidValuesForDateTimeObjects()
    {
        $invalidValues = [];
        $invalidValues['string'] = ['Hello'];
        $invalidValues['boolean'] = [true];
        $invalidValues['object'] = [new \stdClass()];
        $invalidValues['null'] = [null];
        $invalidValues['array'] = [[123]];

        return $invalidValues;
    }

    /**
     * @test
     *
     * @param mixed $invalidValue
     * @dataProvider dataProviderWithInvalidValuesForDateTimeObjects
     */
    public function convertInvalidDates($invalidValue)
    {
        self::assertNull($this->subject->convert($invalidValue));
    }

    /**
     * @return array
     */
    public function stringDatesDataProvider()
    {
        $dateStrings = [];

        $midnight = new \DateTime();
        $midnight->modify('midnight');
        $dateStrings['midnight'] = ['midnight', $midnight];

        $tomorrow = new \DateTime();
        $tomorrow->modify('tomorrow')->modify('midnight');
        $dateStrings['tomorrow'] = ['tomorrow', $tomorrow];

        $lastDayOfMonth = new \DateTime();
        $lastDayOfMonth->modify('last day of this month')->modify('midnight');
        $dateStrings['last day of this month'] = ['last day of this month', $lastDayOfMonth];

        return $dateStrings;
    }

    /**
     * @test
     *
     * @param $stringDate
     * @param $expectedDate
     * @dataProvider stringDatesDataProvider
     */
    public function convertWithStringDates($stringDate, $expectedDate)
    {
        self::assertEquals(
            $expectedDate,
            $this->subject->convert($stringDate)
        );
    }

    /**
     * @return array
     */
    public function timestampDataProvider()
    {
        $timestamps = [];
        $timestamps['timestamp: 0'] = [0, null];
        $timestamps['timestamp: 1'] = [1, \DateTime::createFromFormat('d.m.Y H:i:s', '01.01.1970 00:00:00')];
        $timestamps['timestamp: 12345'] = [12345, \DateTime::createFromFormat('d.m.Y H:i:s', '01.01.1970 00:00:00')];
        $timestamps['timestamp: 123456789'] = [123456789, \DateTime::createFromFormat('d.m.Y H:i:s', '29.11.1973 00:00:00')];
        $timestamps['timestamp: 1234567890'] = [1234567890, \DateTime::createFromFormat('d.m.Y H:i:s', '14.02.2009 00:00:00')];
        if (strlen(decbin(~0)) === 64) {
            // this is only for 64bit OS
            $timestamps['timestamp: 13000000000'] = [13000000000, \DateTime::createFromFormat('d.m.Y H:i:s', '15.12.2381 00:00:00')];
            $timestamps['timestamp: 15000000000'] = [15000000000, \DateTime::createFromFormat('d.m.Y H:i:s', '01.05.2445 00:00:00')];
        }

        return $timestamps;
    }

    /**
     * @test
     *
     * @param $timestamp
     * @param $expectedDate
     * @dataProvider timestampDataProvider
     */
    public function convertTimestamps($timestamp, $expectedDate)
    {
        $convertedResult = $this->subject->convert($timestamp);
        if ($convertedResult === null) {
            self::assertSame($expectedDate, $convertedResult);
        } else {
            self::assertEquals(
            /* @var \DateTime $expectedDate */
                $expectedDate->format('U'),
                $convertedResult->format('U')
            );
        }
    }
}
