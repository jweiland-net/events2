<?php

namespace JWeiland\Events2\Tests\Unit\ViewHelpers;

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
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Utility\DateTimeUtility;
use JWeiland\Events2\Service\EventService;
use JWeiland\Events2\ViewHelpers\GetEventDatesViewHelper;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class ShowEventDatesViewHelperTest extends UnitTestCase
{
    /**
     * @var GetEventDatesViewHelper
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = new GetEventDatesViewHelper();
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
    public function renderWillInitiallyReturnEmptyArray()
    {
        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->assertSame(
            [],
            $this->subject->render(new Event())
        );
    }

    /**
     * @test
     */
    public function renderWithOneFutureDayReturnsOneDay()
    {
        $today = new \DateTime();
        $today->modify('midnight');
        $day = new Day();
        $day->setDay($today);
        $days = new ObjectStorage();
        $days->attach($day);
        $event = new Event();
        $event->setDays($days);
        $time = new Time();
        $time->setTimeBegin('09:25');
        $event->setEventTime($time);

        $expectedDays = [];
        $expectedDays[] = [
            'day' => $day,
            'time' => $time,
            'eventDate' => $today->format('U'),
            'eventTime' => $time->getTimeBegin(),
            'isRemoved' => false,
            'infos' => new \SplObjectStorage(),
        ];

        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->subject->injectEventService(new EventService());
        $this->assertEquals(
            $expectedDays,
            $this->subject->render($event)
        );
    }

    /**
     * @test
     */
    public function renderWithOldDayReturnsEmptyArray()
    {
        $yesterday = new \DateTime();
        $yesterday->modify('yesterday midnight');
        $day = new Day();
        $day->setDay($yesterday);
        $days = new ObjectStorage();
        $days->attach($day);
        $event = new Event();
        $event->setDays($days);
        $time = new Time();
        $time->setTimeBegin('09:25');
        $event->setEventTime($time);

        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->subject->injectEventService(new EventService());
        $this->assertEquals(
            [],
            $this->subject->render($event)
        );
    }

    /**
     * this test also tests the else-part in protected addFutureDaysFromEventRecord.
     *
     * @test
     */
    public function renderWithoutAnyTimesAddedToEventReturnFilledDaysArray()
    {
        $today = new \DateTime();
        $today->modify('midnight');
        $day = new Day();
        $day->setDay($today);
        $days = new ObjectStorage();
        $days->attach($day);
        $event = new Event();
        $event->setDays($days);

        $expectedDays = [];
        $expectedDays[] = [
            'day' => $day,
            'time' => new Time(),
            'eventDate' => $today->format('U'),
            'eventTime' => '',
            'isRemoved' => false,
            'infos' => new \SplObjectStorage(),
        ];

        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->subject->injectEventService(new EventService());
        $this->assertEquals(
            $expectedDays,
            $this->subject->render($event)
        );
    }

    /**
     * this test also tests the else-part in protected addFutureDaysFromRemovedEventExceptions.
     *
     * @test
     */
    public function renderWithRemovedExceptionsAndNoTimesAddedToEventsReturnFilledDaysArray()
    {
        $today = new \DateTime();
        $today->modify('midnight');
        $day = new Day();
        $day->setDay($today);

        $exceptions = new ObjectStorage();
        $exception = new Exception();
        $exception->setExceptionType('Remove');
        $exception->setExceptionDate($today);
        $exceptions->attach($exception);

        $infoExceptions = new \SplObjectStorage();
        $infoExceptions->attach($exception);

        $event = new Event();
        $event->setExceptions($exceptions);

        $expectedDays = [];
        $expectedDays[] = [
            'day' => $day,
            'time' => new Time(),
            'eventDate' => $today->format('U'),
            'eventTime' => '',
            'isRemoved' => true,
            'infos' => $infoExceptions,
        ];

        $eventService = new EventService();
        $eventService->injectDateTimeUtility(new DateTimeUtility());

        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->subject->injectEventService($eventService);
        $this->assertEquals(
            $expectedDays,
            $this->subject->render($event)
        );
    }

    /**
     * @test
     */
    public function renderWillAddDayAndTimeToDayArray()
    {
        $tomorrow = new \DateTime();
        $tomorrow->modify('tomorrow midnight');
        $day = new Day();
        $day->setDay($tomorrow);
        $time = new Time();
        $time->setTimeBegin('09:25');
        $event = new Event();
        $event->addDay($day);
        $event->setEventBegin($tomorrow);
        $event->setEventTime($time);

        $expectedDayArray = [
            0 => [
                'day' => $day,
                'time' => $time,
                'eventDate' => $tomorrow->format('U'),
                'eventTime' => $time->getTimeBegin(),
                'isRemoved' => false,
                'infos' => new \SplObjectStorage()
            ]
        ];

        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->subject->injectEventService(new EventService());
        $this->assertEquals(
            $expectedDayArray,
            $this->subject->render($event)
        );
    }

    /**
     * @test
     */
    public function buildDayArrayWithRemovedExceptionsWillSetIsRemovedToTrue()
    {
        $tomorrow = new \DateTime();
        $tomorrow->modify('tomorrow midnight');
        $day = new Day();
        $day->setDay($tomorrow);
        $time = new Time();
        $time->setTimeBegin('09:25');

        $exception = new Exception();
        $exception->setExceptionType('Remove');
        $exception->setExceptionDate($tomorrow);
        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

        $infoExceptions = new \SplObjectStorage();
        $infoExceptions->attach($exception);

        $expectedDayArray = [
            0 => [
                'day' => $day,
                'time' => $time,
                'eventDate' => $tomorrow->format('U'),
                'eventTime' => $time->getTimeBegin(),
                'isRemoved' => true,
                'infos' => $infoExceptions
            ]
        ];

        $event = new Event();
        $event->setEventBegin($tomorrow);
        $event->setEventTime($time);
        $event->setExceptions($exceptions);

        $eventService = new EventService();
        $eventService->injectDateTimeUtility(new DateTimeUtility());

        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->subject->injectEventService($eventService);
        $this->assertEquals(
            $expectedDayArray,
            $this->subject->render($event)
        );
    }

    /**
     * @test
     */
    public function renderWithInfoExceptionsWillSetInfoInDayArray()
    {
        $tomorrow = new \DateTime();
        $tomorrow->modify('tomorrow midnight');
        $day = new Day();
        $day->setDay($tomorrow);

        $time = new Time();
        $time->setTimeBegin('09:25');

        $infoException = new Exception();
        $infoException->setExceptionType('Info');
        $infoException->setExceptionDate($tomorrow);

        $removeException = new Exception();
        $removeException->setExceptionType('Remove');
        $removeException->setExceptionDate($tomorrow);

        $infoExceptions = new \SplObjectStorage();
        $infoExceptions->attach($infoException);
        $infoExceptions->attach($removeException);

        $exceptions = new ObjectStorage();
        $exceptions->attach($removeException);
        $exceptions->attach($infoException);

        $expectedDayArray = [
            0 => [
                'day' => $day,
                'time' => $time,
                'eventDate' => $tomorrow->format('U'),
                'eventTime' => $time->getTimeBegin(),
                'isRemoved' => true,
                'infos' => $infoExceptions
            ]
        ];

        $event = new Event();
        $event->setEventBegin($tomorrow);
        $event->setEventTime($time);
        $event->setExceptions($exceptions);

        $eventService = new EventService();
        $eventService->injectDateTimeUtility(new DateTimeUtility());

        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->subject->injectEventService($eventService);
        $this->assertEquals(
            $expectedDayArray,
            $this->subject->render($event)
        );
    }

    /**
     * @test
     */
    public function sortDaysByDateAndTimeWithStringDaysResultsInSortedArray()
    {
        $tomorrow = new \DateTime();
        $tomorrow->modify('tomorrow midnight');
        $dayTomorrow = new Day();
        $dayTomorrow->setDay($tomorrow);

        $inOneWeek = new \DateTime();
        $inOneWeek->modify('+1 week midnight');
        $dayInOneWeek = new Day();
        $dayInOneWeek->setDay($inOneWeek);

        $days = new ObjectStorage();
        $days->attach($dayTomorrow);
        $days->attach($dayInOneWeek);

        $timeBegin = new Time();
        $timeBegin->setTimeBegin('10:34');
        $multipleTime = new Time();
        $multipleTime->setTimeBegin('08:12');

        $multipleTimes = new ObjectStorage();
        $multipleTimes->attach($multipleTime);

        $exceptionTime = new Time();
        $exceptionTime->setTimeBegin('12:30');

        $exception = new Exception();
        $exception->setExceptionType('Time');
        $exception->setExceptionDate($inOneWeek);
        $exception->setExceptionTime($exceptionTime);

        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

        $splExceptions = new \SplObjectStorage();
        $splExceptions->attach($exception);

        $event = new Event();
        $event->setDays($days);
        $event->setEventType('recurring');
        $event->setEventBegin($tomorrow);
        $event->setEventTime($timeBegin);
        $event->setSameDay(true);
        $event->setMultipleTimes($multipleTimes);
        $event->setExceptions($exceptions);

        $expectedDayArray = [
            0 => [
                'day' => $dayTomorrow,
                'time' => $multipleTime,
                'eventDate' => $tomorrow->format('U'),
                'eventTime' => $multipleTime->getTimeBegin(),
                'isRemoved' => false,
                'infos' => new \SplObjectStorage()
            ],
            1 => [
                'day' => $dayTomorrow,
                'time' => $timeBegin,
                'eventDate' => $tomorrow->format('U'),
                'eventTime' => $timeBegin->getTimeBegin(),
                'isRemoved' => false,
                'infos' => new \SplObjectStorage()
            ],
            2 => [
                'day' => $dayInOneWeek,
                'time' => $exceptionTime,
                'eventDate' => $inOneWeek->format('U'),
                'eventTime' => $exceptionTime->getTimeBegin(),
                'isRemoved' => false,
                'infos' => $splExceptions
            ]
        ];

        $eventService = new EventService();
        $eventService->injectDateTimeUtility(new DateTimeUtility());

        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->subject->injectEventService($eventService);

        // we have a different SplObjectStorage in infos. So we can't make use of assertSame
        $this->assertEquals(
            $expectedDayArray,
            $this->subject->render($event)
        );
    }
}
