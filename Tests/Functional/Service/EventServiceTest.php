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
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for class \JWeiland\Events2\Service\EventService
 */
class EventServiceTest extends FunctionalTestCase
{
    protected EventService $subject;

    protected EventRepository|MockObject $eventRepositoryMock;

    protected DataMapper|MockObject $dataMapperMock;

    protected DatabaseService|MockObject $databaseServiceMock;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'phpTimeZone' => Events2Constants::PHP_TIMEZONE,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventRepositoryMock = $this->createMock(EventRepository::class);
        $this->dataMapperMock = $this->createMock(DataMapper::class);
        $this->databaseServiceMock = $this->createMock(DatabaseService::class);

        $this->subject = new EventService(
            $this->eventRepositoryMock,
            new TimeFactory(new DateTimeUtility()),
            $this->dataMapperMock,
            $this->databaseServiceMock,
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->databaseServiceMock,
            $this->dataMapperMock,
            $this->eventRepositoryMock,
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function getNextDayForEventWithoutEventReturnsFalse(): void
    {
        $this->eventRepositoryMock
            ->expects(self::once())
            ->method('findByIdentifier')
            ->with(self::identicalTo(1))
            ->willReturn(null);

        self::assertNull(
            $this->subject->getNextDayForEvent(1),
        );
    }

    #[Test]
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

        $event = new Event();
        $event->setDays($days);

        $this->eventRepositoryMock
            ->expects(self::once())
            ->method('findByIdentifier')
            ->with(self::identicalTo(1))
            ->willReturn($event);

        self::assertNull(
            $this->subject->getNextDayForEvent(1),
        );
    }

    #[Test]
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

        $event = new Event();
        $event->setDays($days);

        $this->eventRepositoryMock
            ->expects(self::once())
            ->method('findByIdentifier')
            ->with(self::identicalTo(1))
            ->willReturn($event);

        self::assertEquals(
            $day->getDay(),
            $this->subject->getNextDayForEvent(1),
        );
    }

    /**
     * This test also tests re-sorting of days
     */
    #[Test]
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

        $event = new Event();
        $event->setEventType('recurring');
        $event->setDays($days);

        $this->eventRepositoryMock
            ->expects(self::once())
            ->method('findByIdentifier')
            ->with(self::identicalTo(1))
            ->willReturn($event);

        self::assertEquals(
            $day3->getDay(),
            $this->subject->getNextDayForEvent(1),
        );
    }

    #[Test]
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

        $event = new Event();
        $event->setDays($days);

        $this->eventRepositoryMock
            ->expects(self::once())
            ->method('findByIdentifier')
            ->with(self::identicalTo(1))
            ->willReturn($event);

        self::assertEquals(
            $day->getDay(),
            $this->subject->getLastDayForEvent(1),
        );
    }

    /**
     * This test also tests re-sorting of days
     */
    #[Test]
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

        $event = new Event();
        $event->setEventType('recurring');
        $event->setDays($days);

        $this->eventRepositoryMock
            ->expects(self::once())
            ->method('findByIdentifier')
            ->with(self::identicalTo(1))
            ->willReturn($event);

        self::assertEquals(
            $day1->getDay(),
            $this->subject->getLastDayForEvent(1),
        );
    }
}
