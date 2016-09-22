<?php

namespace JWeiland\Events2\Tests\Unit\Converter;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use JWeiland\Events2\Converter\TimeToStringConverter;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class TimeToStringConverterTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Converter\TimeToStringConverter
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = new TimeToStringConverter();
    }

    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * dataProvider for invalid timestamp.
     *
     * @return array
     */
    public function dataProviderForInvalidTimestamps()
    {
        $timestamps = array();
        $timestamps['array'] = array(array('Test', 'Test'));
        $timestamps['string'] = array('Hello');
        $timestamps['object'] = array(new \stdClass());

        return $timestamps;
    }

    /**
     * @test
     *
     * @param mixed $invalidTimestamp
     * @dataProvider dataProviderForInvalidTimestamps
     */
    public function convertWithInvalidValuesReturnsEmptyString($invalidTimestamp)
    {
        $this->assertSame(
            '',
            $this->subject->convert($invalidTimestamp)
        );
    }

    /**
     * dataProvider for too high integer values.
     *
     * @return array
     */
    public function dataProviderForTooHighIntegerValues()
    {
        $timestamps = array();
        $timestamps['one second too high'] = array(60 * 60 * 24);
        $timestamps['some seconds too high'] = array(60 * 60 * 24 * 7);
        $timestamps['one year too high'] = array(60 * 60 * 24 * 365);

        return $timestamps;
    }

    /**
     * @test
     *
     * @param int $tooHighIntegerValue
     * @dataProvider dataProviderForTooHighIntegerValues
     */
    public function convertWithTooHighIntergerValues($tooHighIntegerValue)
    {
        $this->assertSame(
            '23:59',
            $this->subject->convert($tooHighIntegerValue)
        );
    }

    /**
     * dataProvider for too low integer values.
     *
     * @return array
     */
    public function dataProviderForTooLowIntegerValues()
    {
        $timestamps = array();
        $timestamps['edge case with zero'] = array(0);
        $timestamps['edge case with -1'] = array(-1);
        $timestamps['higher negative value'] = array(-123);

        return $timestamps;
    }

    /**
     * @test
     *
     * @param int $tooLowIntegerValue
     * @dataProvider dataProviderForTooLowIntegerValues
     */
    public function convertWithTooLowIntergerValues($tooLowIntegerValue)
    {
        $this->assertSame(
            '00:00',
            $this->subject->convert($tooLowIntegerValue)
        );
    }

    /**
     * dataProvider for timestamps in allowed range.
     *
     * @return array
     */
    public function dataProviderForTimestampsInAllowedRange()
    {
        $timestamps = array();
        $timestamps['edge case with zero'] = array(0, '00:00');
        $timestamps['edge case with one second'] = array(1, '00:00');
        $timestamps['edge case with one minute'] = array(60, '00:01');
        $timestamps['edge case with midnight'] = array(60 * 60 * 24, '23:59');
        $timestamps['edge case with midnight - 1 second'] = array((60 * 60 * 24) - 1, '23:59');
        $timestamps['timestamp with padded zero in front'] = array(60 * 60 * 5 + 60 * 7, '05:07');
        $timestamps['timestamp in the morning'] = array(60 * 60 * 10 + 60 * 43, '10:43');
        $timestamps['timestamp at launch'] = array(60 * 60 * 12, '12:00');
        $timestamps['timestamp in the afternoon'] = array(60 * 60 * 17 + 60 * 52, '17:52');

        return $timestamps;
    }

    /**
     * @test
     *
     * @param int    $timestampInRange
     * @param string $expectedTime
     * @dataProvider dataProviderForTimestampsInAllowedRange
     */
    public function convertWithIntegersInAllowedRangeResultsInStringInterpretation($timestampInRange, $expectedTime)
    {
        $this->assertSame(
            $expectedTime,
            $this->subject->convert($timestampInRange)
        );
    }
}
