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
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Service\EventService;
use JWeiland\Events2\Utility\DateTimeUtility;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Test case for class \JWeiland\Events2\Service\EventService
 */
class EventServiceTest extends FunctionalTestCase
{
    use ProphecyTrait;

    protected EventService $subject;

    /**
     * @var EventRepository|ObjectProphecy
     */
    protected $eventRepositoryProphecy;

    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2'
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventRepositoryProphecy = $this->prophesize(EventRepository::class);

        $this->subject = GeneralUtility::makeInstance(
            EventService::class,
            $this->eventRepositoryProphecy->reveal(),
            new TimeFactory(new DateTimeUtility()),
            $this->prophesize(DataMapper::class)->reveal(),
            $this->prophesize(DatabaseService::class)->reveal()
        );
    }

    protected function tearDown(): void
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
        $yesterday = new \DateTimeImmutable('yesterday midnight');
        $yesterdayWithTime = new \DateTimeImmutable('yesterday');

        $day = new Day();
        $day->setDay($yesterday);
        $day->setDayTime($yesterdayWithTime);
        $day->setSortDayTime($yesterdayWithTime);

        $days = new ObjectStorage();
        $days->attach($day);

        $event = GeneralUtility::makeInstance(Event::class);
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
        $tomorrow = new \DateTimeImmutable('tomorrow midnight');
        $tomorrowWithTime = new \DateTimeImmutable('tomorrow');

        $day = new Day();
        $day->setDay($tomorrow);
        $day->setDayTime($tomorrowWithTime);
        $day->setSortDayTime($tomorrowWithTime);

        $days = new ObjectStorage();
        $days->attach($day);

        $event = GeneralUtility::makeInstance(Event::class);
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
        $tomorrow = new \DateTimeImmutable('tomorrow midnight');
        $tomorrowWithTime = new \DateTimeImmutable('tomorrow');
        $nextWeek = new \DateTimeImmutable('next week midnight');
        $nextWeekWithTime = new \DateTimeImmutable('next week');
        $nextMonth = new \DateTimeImmutable('next month midnight');
        $nextMonthWithTime = new \DateTimeImmutable('next month');

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

        $event = GeneralUtility::makeInstance(Event::class);
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
        $tomorrow = new \DateTimeImmutable('tomorrow midnight');
        $tomorrowWithTime = new \DateTimeImmutable('tomorrow');

        $day = new Day();
        $day->setDay($tomorrow);
        $day->setDayTime($tomorrowWithTime);
        $day->setSortDayTime($tomorrowWithTime);

        $days = new ObjectStorage();
        $days->attach($day);

        $event = GeneralUtility::makeInstance(Event::class);
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
        $tomorrow = new \DateTimeImmutable('tomorrow midnight');
        $tomorrowWithTime = new \DateTimeImmutable('tomorrow');
        $nextWeek = new \DateTimeImmutable('next week midnight');
        $nextWeekWithTime = new \DateTimeImmutable('next week');
        $nextMonth = new \DateTimeImmutable('next month midnight');
        $nextMonthWithTime = new \DateTimeImmutable('next month');

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

        $event = GeneralUtility::makeInstance(Event::class);
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
