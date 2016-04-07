<?php

namespace JWeiland\Events2\Tests\Unit\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Stefan Froemken <projects@jweiland.net>, jweiland.net
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Core\Tests\UnitTestCase;
use JWeiland\Events2\Utility\DateTimeUtility;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
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
        $emptyDate = array();
        $emptyDate['empty value: null'] = array(null, null);
        $emptyDate['empty value: 0000-00-00'] = array('0000-00-00', null);
        $emptyDate['empty value: 0000-00-00 00:00:00'] = array('0000-00-00 00:00:00', null);

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
        $this->assertSame(
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
        $invalidValues = array();
        $invalidValues['string'] = array('Hello');
        $invalidValues['boolean'] = array(true);
        $invalidValues['object'] = array(new \stdClass());
        $invalidValues['null'] = array(null);
        $invalidValues['array'] = array(array(123));

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
        $this->assertNull($this->subject->convert($invalidValue));
    }

    /**
     * @return array
     */
    public function stringDatesDataProvider()
    {
        $dateStrings = array();

        $midnight = new \DateTime();
        $midnight->modify('midnight');
        $dateStrings['midnight'] = array('midnight', $midnight);

        $tomorrow = new \DateTime();
        $tomorrow->modify('tomorrow')->modify('midnight');
        $dateStrings['tomorrow'] = array('tomorrow', $tomorrow);

        $lastDayOfMonth = new \DateTime();
        $lastDayOfMonth->modify('last day of this month')->modify('midnight');
        $dateStrings['last day of this month'] = array('last day of this month', $lastDayOfMonth);

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
        $this->assertEquals(
            $expectedDate,
            $this->subject->convert($stringDate)
        );
    }

    /**
     * @return array
     */
    public function timestampDataProvider()
    {
        $timestamps = array();
        $timestamps['timestamp: 0'] = array(0, null);
        $timestamps['timestamp: 1'] = array(1, \DateTime::createFromFormat('d.m.Y H:i:s', '01.01.1970 00:00:00'));
        $timestamps['timestamp: 12345'] = array(12345, \DateTime::createFromFormat('d.m.Y H:i:s', '01.01.1970 00:00:00'));
        $timestamps['timestamp: 123456789'] = array(123456789, \DateTime::createFromFormat('d.m.Y H:i:s', '29.11.1973 00:00:00'));
        $timestamps['timestamp: 1234567890'] = array(1234567890, \DateTime::createFromFormat('d.m.Y H:i:s', '14.02.2009 00:00:00'));
        if (strlen(decbin(~0)) === 64) {
            // this is only for 64bit OS
            $timestamps['timestamp: 13000000000'] = array(13000000000, \DateTime::createFromFormat('d.m.Y H:i:s', '15.12.2381 00:00:00'));
            $timestamps['timestamp: 15000000000'] = array(15000000000, \DateTime::createFromFormat('d.m.Y H:i:s', '01.05.2445 00:00:00'));
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
            $this->assertSame($expectedDate, $convertedResult);
        } else {
            $this->assertEquals(
            /* @var \DateTime $expectedDate */
                $expectedDate->format('U'),
                $convertedResult->format('U')
            );
        }
    }
}
