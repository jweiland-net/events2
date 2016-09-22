<?php

namespace JWeiland\Events2\Tests\Unit\ViewHelpers\Widget\Controller;

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
use JWeiland\Events2\Utility\EventUtility;
use JWeiland\Events2\ViewHelpers\Widget\Controller\ShowEventDatesController;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class ShowEventDatesControllerTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\ViewHelpers\Widget\Controller\ShowEventDatesController
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = new ShowEventDatesController();
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
    public function getEventInitiallyReturnsNull()
    {
        $this->assertNull($this->subject->getEvent());
    }

    /**
     * @test
     */
    public function setEventSetsEvent()
    {
        $instance = new Event();
        $this->subject->setEvent($instance);

        $this->assertSame(
            $instance,
            $this->subject->getEvent()
        );
    }

    /**
     * @test
     */
    public function getExceptionsInitiallyReturnsObjectStorage()
    {
        $this->assertEquals(
            new ObjectStorage(),
            $this->subject->getExceptions()
        );
    }

    /**
     * @test
     */
    public function setExceptionsSetsExceptions()
    {
        $object = new Exception();
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);
        $this->subject->setExceptions($objectStorage);

        $this->assertSame(
            $objectStorage,
            $this->subject->getExceptions()
        );
    }

    /**
     * @test
     */
    public function initializeActionInitializesShowEventDatesController()
    {
        $exceptions = new ObjectStorage();
        $exception = new Exception();
        $exception->setExceptionType('Add');
        $event = new Event();
        $event->setSameDay(true);
        $event->setTitle('Test123');
        $event->setExceptions($exceptions);
        $widgetConfiguration = array(
            'event' => $event,
        );

        /** @var \JWeiland\Events2\ViewHelpers\Widget\Controller\ShowEventDatesController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $showEventDatesController */
        $showEventDatesController = $this->getAccessibleMock('JWeiland\\Events2\\ViewHelpers\\Widget\\Controller\\ShowEventDatesController', array('dummy'));
        $showEventDatesController->_set('widgetConfiguration', $widgetConfiguration);
        $showEventDatesController->initializeAction();

        $this->assertSame(
            $exceptions,
            $showEventDatesController->getExceptions()
        );
        $this->assertSame(
            $event,
            $showEventDatesController->getEvent()
        );
    }

    /**
     * @test
     */
    public function getCollectedDaysWillInitiallyReturnEmptyArray()
    {
        $this->subject->setEvent(new Event());
        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->assertSame(
            array(),
            $this->subject->getCollectedDays()
        );
    }

    /**
     * @test
     */
    public function getCollectedDaysWithOneFutureDayReturnsOneDay()
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

        $expectedDays = array();
        $expectedDays[] = array(
            'day' => $day,
            'time' => $time,
            'eventDate' => $today->format('U'),
            'eventTime' => $time->getTimeBegin(),
            'isRemoved' => false,
            'infos' => new \SplObjectStorage(),
        );

        $this->subject->setEvent($event);
        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->subject->injectEventUtility(new EventUtility());
        $this->assertEquals(
            $expectedDays,
            $this->subject->getCollectedDays()
        );
    }

    /**
     * @test
     */
    public function getCollectedDaysWithOldDayReturnsEmptyArray()
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

        $this->subject->setEvent($event);
        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->subject->injectEventUtility(new EventUtility());
        $this->assertEquals(
            array(),
            $this->subject->getCollectedDays()
        );
    }

    /**
     * this test also tests the else-part in protected addFutureDaysFromEventRecord.
     *
     * @test
     */
    public function getCollectedDaysWithoutAnyTimesAddedToEventReturnFilledDaysArray()
    {
        $today = new \DateTime();
        $today->modify('midnight');
        $day = new Day();
        $day->setDay($today);
        $days = new ObjectStorage();
        $days->attach($day);
        $event = new Event();
        $event->setDays($days);

        $expectedDays = array();
        $expectedDays[] = array(
            'day' => $day,
            'time' => new Time(),
            'eventDate' => $today->format('U'),
            'eventTime' => '',
            'isRemoved' => false,
            'infos' => new \SplObjectStorage(),
        );

        $this->subject->setEvent($event);
        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->subject->injectEventUtility(new EventUtility());
        $this->assertEquals(
            $expectedDays,
            $this->subject->getCollectedDays()
        );
    }

    /**
     * this test also tests the else-part in protected addFutureDaysFromRemovedEventExceptions.
     *
     * @test
     */
    public function getCollectedDaysWithRemovedExceptionsAndNoTimesAddedToEventsReturnFilledDaysArray()
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

        $expectedDays = array();
        $expectedDays[] = array(
            'day' => $day,
            'time' => new Time(),
            'eventDate' => $today->format('U'),
            'eventTime' => '',
            'isRemoved' => true,
            'infos' => $infoExceptions,
        );

        $eventUtility = new EventUtility();
        $eventUtility->injectDateTimeUtility(new DateTimeUtility());

        $this->subject->setEvent($event);
        $this->subject->setExceptions($exceptions);
        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->subject->injectEventUtility($eventUtility);
        $this->assertEquals(
            $expectedDays,
            $this->subject->getCollectedDays()
        );
    }

    /**
     * @test
     */
    public function buildDayArrayWillAddDayAndTimeToDayArray()
    {
        $today = new \DateTime();
        $today->modify('midnight');
        $day = new Day();
        $day->setDay($today);
        $time = new Time();
        $time->setTimeBegin('09:25');

        $expectedDayArray = array(
            'day' => $day,
            'time' => $time,
            'eventDate' => $today->format('U'),
            'eventTime' => $time->getTimeBegin(),
            'isRemoved' => false,
            'infos' => new \SplObjectStorage(),
        );

        $this->subject->setEvent(new Event());
        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->subject->injectEventUtility(new EventUtility());
        $this->assertEquals(
            $expectedDayArray,
            $this->subject->buildDayArray($day, $time)
        );
    }

    /**
     * @test
     */
    public function buildDayArrayWithRemovedExceptionsWillSetIsRemovedToTrue()
    {
        $today = new \DateTime();
        $today->modify('midnight');
        $day = new Day();
        $day->setDay($today);
        $time = new Time();
        $time->setTimeBegin('09:25');

        $exception = new Exception();
        $exception->setExceptionType('Remove');
        $exception->setExceptionDate($today);
        $exceptions = new ObjectStorage();
        $exceptions->attach($exception);

        $infoExceptions = new \SplObjectStorage();
        $infoExceptions->attach($exception);

        $expectedDayArray = array(
            'day' => $day,
            'time' => $time,
            'eventDate' => $today->format('U'),
            'eventTime' => $time->getTimeBegin(),
            'isRemoved' => true,
            'infos' => $infoExceptions,
        );

        $event = new Event();
        $event->setExceptions($exceptions);

        $eventUtility = new EventUtility();
        $eventUtility->injectDateTimeUtility(new DateTimeUtility());

        $this->subject->setEvent($event);
        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->subject->injectEventUtility($eventUtility);
        $this->assertEquals(
            $expectedDayArray,
            $this->subject->buildDayArray($day, $time)
        );
    }

    /**
     * @test
     */
    public function buildDayArrayWithInfoExceptionsWillSetInfoInDayArray()
    {
        $today = new \DateTime();
        $today->modify('midnight');
        $day = new Day();
        $day->setDay($today);

        $time = new Time();
        $time->setTimeBegin('09:25');

        $infoException = new Exception();
        $infoException->setExceptionType('Info');
        $infoException->setExceptionDate($today);

        $removeException = new Exception();
        $removeException->setExceptionType('Remove');
        $removeException->setExceptionDate($today);

        $infoExceptions = new \SplObjectStorage();
        $infoExceptions->attach($infoException);
        $infoExceptions->attach($removeException);

        $exceptions = new ObjectStorage();
        $exceptions->attach($removeException);
        $exceptions->attach($infoException);

        $expectedDayArray = array(
            'day' => $day,
            'time' => $time,
            'eventDate' => $today->format('U'),
            'eventTime' => $time->getTimeBegin(),
            'isRemoved' => true,
            'infos' => $infoExceptions,
        );

        $event = new Event();
        $event->setExceptions($exceptions);

        $eventUtility = new EventUtility();
        $eventUtility->injectDateTimeUtility(new DateTimeUtility());

        $this->subject->setEvent($event);
        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->subject->injectEventUtility($eventUtility);
        $this->assertEquals(
            $expectedDayArray,
            $this->subject->buildDayArray($day, $time)
        );
    }

    /**
     * data provider for sortDaysByDateAndTimeWithInvalidValuesThrowsException.
     *
     * @return array
     */
    public function dataProviderForInvalidValuesInSortMethod()
    {
        $invalidValue = array();
        $invalidValue['null'] = array(null);
        $invalidValue['string'] = array('Hello');
        $invalidValue['integer'] = array(123);
        $invalidValue['object'] = array(new \stdClass());

        return $invalidValue;
    }

    /**
     * @test
     *
     * @param mixed $invalidValue
     * @dataProvider dataProviderForInvalidValuesInSortMethod
     * @expectedException \PHPUnit_Framework_Error
     */
    public function sortDaysByDateAndTimeWithInvalidValuesThrowsException($invalidValue)
    {
        $this->subject->sortDaysByDateAndTime($invalidValue);
    }

    /**
     * @test
     */
    public function sortDaysByDateAndTimeWithEmptyDaysReturnsUnchangedArray()
    {
        $days = array();
        $this->assertSame(
            array(),
            $this->subject->sortDaysByDateAndTime($days)
        );
    }

    /**
     * @test
     */
    public function sortDaysByDateAndTimeWithOneDayResultsInSortedArray()
    {
        $days = array();
        $day = array(
            'eventDate' => 12345,
            'eventTime' => '04:34',
        );
        $days[] = $day;

        $this->assertSame(
            $days,
            $this->subject->sortDaysByDateAndTime($days)
        );
    }

    /**
     * @test
     */
    public function sortDaysByDateAndTimeWithStringDaysResultsInSortedArray()
    {
        $days = array();
        $days[] = array('eventDate' => '10', 'eventTime' => '10:34');
        $days[] = array('eventDate' => '2', 'eventTime' => '02:34');
        $days[] = array('eventDate' => '1', 'eventTime' => '01:34');
        $days[] = array('eventDate' => '21', 'eventTime' => '21:34');

        $expectedDays = array();
        $expectedDays[] = array('eventDate' => '1', 'eventTime' => '01:34');
        $expectedDays[] = array('eventDate' => '2', 'eventTime' => '02:34');
        $expectedDays[] = array('eventDate' => '10', 'eventTime' => '10:34');
        $expectedDays[] = array('eventDate' => '21', 'eventTime' => '21:34');

        $this->assertSame(
            $expectedDays,
            $this->subject->sortDaysByDateAndTime($days)
        );
    }

    /**
     * @test
     */
    public function sortDaysByDateAndTimeWithIntDateAndStringTimeResultsInSortedArray()
    {
        $days = array();
        $days[] = array('eventDate' => 10, 'eventTime' => '10:34');
        $days[] = array('eventDate' => 8, 'eventTime' => '11:00');
        $days[] = array('eventDate' => 2, 'eventTime' => '02:34');
        $days[] = array('eventDate' => 5, 'eventTime' => '09:00');
        $days[] = array('eventDate' => 1, 'eventTime' => '01:34');
        $days[] = array('eventDate' => 21, 'eventTime' => '21:34');

        $expectedDays = array();
        $expectedDays[] = array('eventDate' => 1, 'eventTime' => '01:34');
        $expectedDays[] = array('eventDate' => 2, 'eventTime' => '02:34');
        $expectedDays[] = array('eventDate' => 5, 'eventTime' => '09:00');
        $expectedDays[] = array('eventDate' => 8, 'eventTime' => '11:00');
        $expectedDays[] = array('eventDate' => 10, 'eventTime' => '10:34');
        $expectedDays[] = array('eventDate' => 21, 'eventTime' => '21:34');

        $this->assertSame(
            $expectedDays,
            $this->subject->sortDaysByDateAndTime($days)
        );
    }
}
