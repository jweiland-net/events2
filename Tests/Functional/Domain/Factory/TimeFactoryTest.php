<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Domain\Factory;

use JWeiland\Events2\Domain\Factory\TimeFactory;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Time;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Functional test for TimeFactory
 */
class TimeFactoryTest extends FunctionalTestCase
{
    /**
     * @var TimeFactory
     */
    protected $subject;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2'
    ];

    public function setUp()
    {
        parent::setUp();

        $this->subject = new TimeFactory();
    }

    public function tearDown()
    {
        unset(
            $this->subject
        );
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getTimesForDateWithoutAnyTimesReturnsNoTimes()
    {
        $firstDate = new \DateTime('midnight');
        $secondDate = new \DateTime('midnight');
        $secondDate->modify('tomorrow');

        $firstAddException = new Exception();
        $firstAddException->setExceptionType('Add');
        $firstAddException->setExceptionDate($firstDate);
        $secondAddException = new Exception();
        $secondAddException->setExceptionType('Add');
        $secondAddException->setExceptionDate($secondDate);

        $exceptions = new ObjectStorage();
        $exceptions->attach($firstAddException);
        $exceptions->attach($secondAddException);

        $event = new Event();
        $event->setExceptions($exceptions);

        $expectedTimes = new \SplObjectStorage();

        self::assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate)
        );
    }

    /**
     * @test
     */
    public function getTimesForDateWithExceptionsWithTimeReturnsTimes()
    {
        $firstDate = new \DateTime('midnight');
        $secondDate = new \DateTime('midnight');
        $secondDate->modify('tomorrow');

        $time = new Time();
        $time->setTimeBegin('10:30');

        $firstAddException = new Exception();
        $firstAddException->setExceptionType('Add');
        $firstAddException->setExceptionDate($firstDate);
        $firstAddException->setExceptionTime($time);
        $secondAddException = new Exception();
        $secondAddException->setExceptionType('Add');
        $secondAddException->setExceptionDate($secondDate);
        $secondAddException->setExceptionTime($time);

        $exceptions = new ObjectStorage();
        $exceptions->attach($firstAddException);
        $exceptions->attach($secondAddException);

        $event = new Event();
        $event->setExceptions($exceptions);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);

        self::assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate)
        );
    }

    /**
     * @test
     */
    public function getTimesForDateWithDifferentExceptionTypesWithTimeReturnsTimes()
    {
        $firstDate = new \DateTime('midnight');

        $time = new Time();
        $time->setTimeBegin('10:30');

        $firstAddException = new Exception();
        $firstAddException->setExceptionType('Add');
        $firstAddException->setExceptionDate($firstDate);
        $firstAddException->setExceptionTime($time);
        $secondAddException = new Exception();
        $secondAddException->setExceptionType('Info');
        $secondAddException->setExceptionDate($firstDate);
        $secondAddException->setExceptionTime($time);

        $exceptions = new ObjectStorage();
        $exceptions->attach($firstAddException);
        $exceptions->attach($secondAddException);

        $event = new Event();
        $event->setExceptions($exceptions);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);
        $expectedTimes->attach($time);

        self::assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate)
        );
    }

    /**
     * @test
     */
    public function getTimesForDateWithDifferentTimesAndSingleEventReturnsNoTimes()
    {
        $firstDate = new \DateTime('midnight');

        $time = new Time();
        $time->setTimeBegin('10:30');

        $times = new ObjectStorage();
        $times->attach($time);

        $event = new Event();
        $event->setEventType('single');
        $event->setDifferentTimes($times);

        $expectedTimes = new \SplObjectStorage();

        self::assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate)
        );
    }

    /**
     * @test
     */
    public function getTimesForDateWithDifferentTimesOnDifferentWeekdayAndRecurringEventReturnsNoTimes()
    {
        $firstDate = new \DateTime('midnight');
        $secondDate = new \DateTime('midnight');
        $secondDate->modify('tomorrow');

        $time = new Time();
        $time->setTimeBegin('10:30');
        $time->setWeekday($secondDate->format('l'));

        $times = new ObjectStorage();
        $times->attach($time);

        $event = new Event();
        $event->setEventType('recurring');
        $event->setDifferentTimes($times);

        $expectedTimes = new \SplObjectStorage();

        self::assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate)
        );
    }

    /**
     * @test
     */
    public function getTimesForDateWithDifferentTimesAndRecurringEventReturnsTimes()
    {
        $firstDate = new \DateTime('midnight');

        $time = new Time();
        $time->setTimeBegin('10:30');
        $time->setWeekday($firstDate->format('l'));

        $times = new ObjectStorage();
        $times->attach($time);

        $event = new Event();
        $event->setEventType('recurring');
        $event->setDifferentTimes($times);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);

        self::assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate)
        );
    }

    /**
     * @test
     */
    public function getTimesForDateWithDifferentTimesAndSingleEventReturnsOneTime()
    {
        $firstDate = new \DateTime('midnight');

        $time = new Time();
        $time->setTimeBegin('10:30');

        $times = new ObjectStorage();
        $times->attach($time);

        $event = new Event();
        $event->setEventType('single');
        $event->setDifferentTimes($times);
        $event->setEventTime($time);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);

        self::assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate)
        );
    }

    /**
     * @test
     */
    public function getTimesForDateWithEventTimeReturnsOneTime()
    {
        $firstDate = new \DateTime('midnight');

        $time = new Time();
        $time->setTimeBegin('10:30');

        $event = new Event();
        $event->setEventType('single');
        $event->setEventTime($time);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);

        self::assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate)
        );
    }

    /**
     * @test
     */
    public function getTimesForDateWithExceptionsWithoutTimesButWithEventTimeReturnsOneTime()
    {
        $firstDate = new \DateTime('midnight');
        $secondDate = new \DateTime('midnight');
        $secondDate->modify('tomorrow');

        $time = new Time();
        $time->setTimeBegin('10:30');

        $firstAddException = new Exception();
        $firstAddException->setExceptionType('Add');
        $firstAddException->setExceptionDate($firstDate);
        $secondAddException = new Exception();
        $secondAddException->setExceptionType('Add');
        $secondAddException->setExceptionDate($secondDate);

        $exceptions = new ObjectStorage();
        $exceptions->attach($firstAddException);
        $exceptions->attach($secondAddException);

        $event = new Event();
        $event->setExceptions($exceptions);
        $event->setEventTime($time);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);

        self::assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate)
        );
    }

    /**
     * @test
     */
    public function getSortedTimesForDateWithoutTimesReturnsEmptyStorage()
    {
        $date = new \DateTime('midnight');

        $event = new Event();

        $expectedTimes = new \SplObjectStorage();

        self::assertEquals(
            $expectedTimes,
            $this->subject->getSortedTimesForDate($event, $date)
        );
    }

    /**
     * @test
     */
    public function getSortedTimesForDateWithEventTimeReturnsOneSortedTime()
    {
        $date = new \DateTime('midnight');

        $time = new Time();
        $time->setTimeBegin('10:30');

        $event = new Event();
        $event->setEventTime($time);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);

        self::assertEquals(
            $expectedTimes,
            $this->subject->getSortedTimesForDate($event, $date)
        );
    }

    /**
     * @test
     */
    public function getSortedTimesForDateWithDifferentTimesReturnsSortedTimes()
    {
        $date = new \DateTime('midnight');
        $tomorrow = new \DateTime('midnight');
        $tomorrow->modify('tomorrow');

        $time1 = new Time();
        $time1->setWeekday($date->format('l'));
        $time1->setTimeBegin('23:56');
        $time2 = new Time();
        $time2->setWeekday($date->format('l'));
        $time2->setTimeBegin('12:34');
        $time3 = new Time();
        $time3->setWeekday($date->format('l'));
        $time3->setTimeBegin('00:34');
        $time4 = new Time();
        $time4->setWeekday($tomorrow->format('l'));
        $time4->setTimeBegin('10:35');

        $times = new ObjectStorage();
        $times->attach($time1);
        $times->attach($time2);
        $times->attach($time3);
        $times->attach($time4);

        $event = new Event();
        $event->setEventType('recurring');
        $event->setDifferentTimes($times);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time3);
        $expectedTimes->attach($time2);
        $expectedTimes->attach($time1);

        self::assertEquals(
            $expectedTimes,
            $this->subject->getSortedTimesForDate($event, $date)
        );
    }

    /**
     * @test
     */
    public function getSortedTimesForDateWithTimeExceptionsReturnsSortedTimes()
    {
        $date = new \DateTime('midnight');
        $tomorrow = new \DateTime('midnight');
        $tomorrow->modify('tomorrow');

        $time1 = new Time();
        $time1->setTimeBegin('23:56');
        $time2 = new Time();
        $time2->setTimeBegin('12:34');
        $time3 = new Time();
        $time3->setTimeBegin('00:34');
        $time4 = new Time();
        $time4->setTimeBegin('10:35');

        $exception1 = new Exception();
        $exception1->setExceptionType('Add');
        $exception1->setExceptionDate($date);
        $exception1->setExceptionTime($time1);
        $exception2 = new Exception();
        $exception2->setExceptionType('Time');
        $exception2->setExceptionDate($date);
        $exception2->setExceptionTime($time2);
        $exception3 = new Exception();
        $exception3->setExceptionType('Add');
        $exception3->setExceptionDate($date);
        $exception3->setExceptionTime($time3);
        $exception4 = new Exception();
        $exception4->setExceptionType('Add');
        $exception4->setExceptionDate($tomorrow);
        $exception4->setExceptionTime($time4);

        $exceptions = new ObjectStorage();
        $exceptions->attach($exception1);
        $exceptions->attach($exception2);
        $exceptions->attach($exception3);
        $exceptions->attach($exception4);

        $event = new Event();
        $event->setExceptions($exceptions);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time3);
        $expectedTimes->attach($time2);
        $expectedTimes->attach($time1);

        self::assertEquals(
            $expectedTimes,
            $this->subject->getSortedTimesForDate($event, $date)
        );
    }
}
