<?php

namespace JWeiland\Events2\Tests\Unit\Domain\Model;

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
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Time;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class ExceptionTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Domain\Model\Exception
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = new Exception();
    }

    /**
     * tear down.
     */
    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getExceptionTypeInitiallyReturnsEmptyString()
    {
        $this->assertSame(
            '',
            $this->subject->getExceptionType()
        );
    }

    /**
     * @test
     */
    public function setExceptionTypeSetsExceptionType()
    {
        $this->subject->setExceptionType('foo bar');

        $this->assertSame(
            'foo bar',
            $this->subject->getExceptionType()
        );
    }

    /**
     * @test
     */
    public function setExceptionTypeWithIntegerResultsInString()
    {
        $this->subject->setExceptionType(123);
        $this->assertSame('123', $this->subject->getExceptionType());
    }

    /**
     * @test
     */
    public function setExceptionTypeWithBooleanResultsInString()
    {
        $this->subject->setExceptionType(true);
        $this->assertSame('1', $this->subject->getExceptionType());
    }

    /**
     * @test
     */
    public function getExceptionDateInitiallyReturnsNull()
    {
        $this->assertNull(
            $this->subject->getExceptionDate()
        );
    }

    /**
     * @test
     */
    public function setExceptionDateSetsExceptionDate()
    {
        $date = new \DateTime();
        $this->subject->setExceptionDate($date);

        $this->assertEquals(
            $date,
            $this->subject->getExceptionDate()
        );
    }

    /**
     * @return array
     */
    public function dataProviderForSetExceptionDate()
    {
        $arguments = array();
        $arguments['set ExceptionDate with Null'] = array(null);
        $arguments['set ExceptionDate with Integer'] = array(1234567890);
        $arguments['set ExceptionDate with Integer as String'] = array('1234567890');
        $arguments['set ExceptionDate with String'] = array('Hi all together');

        return $arguments;
    }

    /**
     * @test
     *
     * @param mixed $argument
     * @dataProvider dataProviderForSetExceptionDate
     * @expectedException \PHPUnit_Framework_Error
     */
    public function setExceptionDateWithInvalidValuesResultsInException($argument)
    {
        $this->subject->setExceptionDate($argument);
    }

    /**
     * @test
     */
    public function getExceptionTimeInitiallyReturnsNull()
    {
        $this->assertNull($this->subject->getExceptionTime());
    }

    /**
     * @test
     */
    public function setExceptionTimeSetsExceptionTime()
    {
        $instance = new Time();
        $this->subject->setExceptionTime($instance);

        $this->assertSame(
            $instance,
            $this->subject->getExceptionTime()
        );
    }

    /**
     * @test
     */
    public function getExceptionDetailsInitiallyReturnsEmptyString()
    {
        $this->assertSame(
            '',
            $this->subject->getExceptionDetails()
        );
    }

    /**
     * @test
     */
    public function setExceptionDetailsSetsExceptionDetails()
    {
        $this->subject->setExceptionDetails('foo bar');

        $this->assertSame(
            'foo bar',
            $this->subject->getExceptionDetails()
        );
    }

    /**
     * @test
     */
    public function setExceptionDetailsWithIntegerResultsInString()
    {
        $this->subject->setExceptionDetails(123);
        $this->assertSame('123', $this->subject->getExceptionDetails());
    }

    /**
     * @test
     */
    public function setExceptionDetailsWithBooleanResultsInString()
    {
        $this->subject->setExceptionDetails(true);
        $this->assertSame('1', $this->subject->getExceptionDetails());
    }
}
