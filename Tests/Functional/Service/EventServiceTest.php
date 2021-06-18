<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Service;

use JWeiland\Events2\Domain\Factory\TimeFactory;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Service\EventService;
use JWeiland\Events2\Utility\DateTimeUtility;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Test case for class \JWeiland\Events2\Service\EventService
 */
class EventServiceTest extends FunctionalTestCase
{
    /**
     * @var EventService
     */
    protected $subject;

    /**
     * @var EventRepository|ObjectProphecy
     */
    protected $eventRepositoryProphecy;

    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2'
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->eventRepositoryProphecy = $this->prophesize(EventRepository::class);

        $this->subject = GeneralUtility::makeInstance(
            EventService::class,
            $this->eventRepositoryProphecy->reveal(),
            new TimeFactory(new DateTimeUtility())
        );
    }

    public function tearDown(): void
    {
        unset(
            $this->subject,
            $this->eventRepositoryProphecy
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function getNextDayForEventWithoutEventReturnsFalse(): void
    {
        $this->eventRepositoryProphecy
            ->findByIdentifier(1)
            ->shouldBeCalled()
            ->willReturn(null);

        self::assertNull(
            $this->subject->getNextDayForEvent(1)
        );
    }

    /**
     * @test
     */
    public function getNextDayForEventWithEventButWithoutFutureDaysReturnsFalse(): void
    {
        $yesterday = new \DateTime('yesterday midnight');
        $yesterdayWithTime = new \DateTime('yesterday');

        $day = new Day();
        $day->setDay($yesterday);
        $day->setDayTime($yesterdayWithTime);
        $day->setSortDayTime($yesterdayWithTime);

        $days = new ObjectStorage();
        $days->attach($day);

        $event = new Event();
        $event->setDays($days);

        $this->eventRepositoryProphecy
            ->findByIdentifier(1)
            ->shouldBeCalled()
            ->willReturn(null);

        self::assertNull(
            $this->subject->getNextDayForEvent(1)
        );
    }

    /**
     * @test
     */
    public function getNextDayForEventWithEventWithFutureDayReturnsDay(): void
    {
        $tomorrow = new \DateTime('tomorrow midnight');
        $tomorrowWithTime = new \DateTime('tomorrow');

        $day = new Day();
        $day->setDay($tomorrow);
        $day->setDayTime($tomorrowWithTime);
        $day->setSortDayTime($tomorrowWithTime);

        $days = new ObjectStorage();
        $days->attach($day);

        $event = new Event();
        $event->setDays($days);

        $this->eventRepositoryProphecy
            ->findByIdentifier(1)
            ->shouldBeCalled()
            ->willReturn($event);

        self::assertEquals(
            $day->getDay(),
            $this->subject->getNextDayForEvent(1)
        );
    }

    /**
     * This test also tests re-sorting of days
     *
     * @test
     */
    public function getNextDayForEventWithEventWithFutureDaysReturnsNextDay(): void
    {
        $tomorrow = new \DateTime('tomorrow midnight');
        $tomorrowWithTime = new \DateTime('tomorrow');
        $nextWeek = new \DateTime('next week midnight');
        $nextWeekWithTime = new \DateTime('next week');
        $nextMonth = new \DateTime('next month midnight');
        $nextMonthWithTime = new \DateTime('next month');

        $day1 = new Day();
        $day1->setDay($nextMonth);
        $day1->setDayTime($nextMonthWithTime);
        $day1->setSortDayTime($nextMonthWithTime);
        $day2 = new Day();
        $day2->setDay($nextWeek);
        $day2->setDayTime($nextWeekWithTime);
        $day2->setSortDayTime($nextWeekWithTime);
        $day3 = new Day();
        $day3->setDay($tomorrow);
        $day3->setDayTime($tomorrowWithTime);
        $day3->setSortDayTime($tomorrowWithTime);

        $days = new ObjectStorage();
        $days->attach($day1);
        $days->attach($day2);
        $days->attach($day3);

        $event = new Event();
        $event->setEventType('recurring');
        $event->setDays($days);

        $this->eventRepositoryProphecy
            ->findByIdentifier(1)
            ->shouldBeCalled()
            ->willReturn($event);

        self::assertEquals(
            $day3->getDay(),
            $this->subject->getNextDayForEvent(1)
        );
    }

    /**
     * @test
     */
    public function getLastDayForEventWithEventWithFutureDayReturnsDay(): void
    {
        $tomorrow = new \DateTime('tomorrow midnight');
        $tomorrowWithTime = new \DateTime('tomorrow');

        $day = new Day();
        $day->setDay($tomorrow);
        $day->setDayTime($tomorrowWithTime);
        $day->setSortDayTime($tomorrowWithTime);

        $days = new ObjectStorage();
        $days->attach($day);

        $event = new Event();
        $event->setDays($days);

        $this->eventRepositoryProphecy
            ->findByIdentifier(1)
            ->shouldBeCalled()
            ->willReturn($event);

        self::assertEquals(
            $day->getDay(),
            $this->subject->getLastDayForEvent(1)
        );
    }

    /**
     * This test also tests re-sorting of days
     *
     * @test
     */
    public function getLastDayForEventWithEventWithFutureDaysReturnsLastDay(): void
    {
        $tomorrow = new \DateTime('tomorrow midnight');
        $tomorrowWithTime = new \DateTime('tomorrow');
        $nextWeek = new \DateTime('next week midnight');
        $nextWeekWithTime = new \DateTime('next week');
        $nextMonth = new \DateTime('next month midnight');
        $nextMonthWithTime = new \DateTime('next month');

        $day1 = new Day();
        $day1->setDay($nextMonth);
        $day1->setDayTime($nextMonthWithTime);
        $day1->setSortDayTime($nextMonthWithTime);
        $day2 = new Day();
        $day2->setDay($nextWeek);
        $day2->setDayTime($nextWeekWithTime);
        $day2->setSortDayTime($nextWeekWithTime);
        $day3 = new Day();
        $day3->setDay($tomorrow);
        $day3->setDayTime($tomorrowWithTime);
        $day3->setSortDayTime($tomorrowWithTime);

        $days = new ObjectStorage();
        $days->attach($day1);
        $days->attach($day2);
        $days->attach($day3);

        $event = new Event();
        $event->setEventType('recurring');
        $event->setDays($days);

        $this->eventRepositoryProphecy
            ->findByIdentifier(1)
            ->shouldBeCalled()
            ->willReturn($event);

        self::assertEquals(
            $day1->getDay(),
            $this->subject->getLastDayForEvent(1)
        );
    }
}
