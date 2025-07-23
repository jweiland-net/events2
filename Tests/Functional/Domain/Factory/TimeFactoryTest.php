<?php

declare(strict_types=1);

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
use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for TimeFactory
 */
class TimeFactoryTest extends FunctionalTestCase
{
    protected TimeFactory $subject;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        date_default_timezone_set('Europe/Berlin');

        $this->subject = new TimeFactory(new DateTimeUtility());
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function getTimesForDateWithoutAnyTimesReturnsNoTimes(): void
    {
        $firstDate = new \DateTimeImmutable('midnight');
        $secondDate = new \DateTimeImmutable('tomorrow midnight');

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
            $this->subject->getTimesForDate($event, $firstDate),
        );
    }

    #[Test]
    public function getTimesForDateWithExceptionsWithTimeReturnsTimes(): void
    {
        $firstDate = new \DateTimeImmutable('midnight');
        $secondDate = new \DateTimeImmutable('tomorrow midnight');

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
        $event->setEventType('duration');
        $event->setExceptions($exceptions);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);

        self::assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate),
        );
    }

    #[Test]
    public function getTimesForDateWithDifferentExceptionTypesWithTimeReturnsTimes(): void
    {
        $firstDate = new \DateTimeImmutable('midnight');

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
        $event->setEventType('recurring');
        $event->setExceptions($exceptions);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);
        $expectedTimes->attach($time);

        self::assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate),
        );
    }

    #[Test]
    public function getTimesForDateWithDifferentTimesAndSingleEventReturnsNoTimes(): void
    {
        $firstDate = new \DateTimeImmutable('midnight');

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
            $this->subject->getTimesForDate($event, $firstDate),
        );
    }

    #[Test]
    public function getTimesForDateWithDifferentTimesOnDifferentWeekdayAndRecurringEventReturnsNoTimes(): void
    {
        $firstDate = new \DateTimeImmutable('midnight');
        $secondDate = new \DateTimeImmutable('tomorrow midnight');

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
            $this->subject->getTimesForDate($event, $firstDate),
        );
    }

    #[Test]
    public function getTimesForDateWithDifferentTimesAndRecurringEventReturnsTimes(): void
    {
        $firstDate = new \DateTimeImmutable('midnight');

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
            $this->subject->getTimesForDate($event, $firstDate),
        );
    }

    #[Test]
    public function getTimesForDateWithDifferentTimesAndSingleEventReturnsOneTime(): void
    {
        $firstDate = new \DateTimeImmutable('midnight');

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
            $this->subject->getTimesForDate($event, $firstDate),
        );
    }

    #[Test]
    public function getTimesForDateWithEventTimeReturnsOneTime(): void
    {
        $firstDate = new \DateTimeImmutable('midnight');

        $time = new Time();
        $time->setTimeBegin('10:30');

        $event = new Event();
        $event->setEventType('single');
        $event->setEventTime($time);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);

        self::assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDate($event, $firstDate),
        );
    }

    #[Test]
    public function getTimesForDateWithExceptionsWithoutTimesButWithEventTimeReturnsOneTime(): void
    {
        $firstDate = new \DateTimeImmutable('midnight');
        $secondDate = new \DateTimeImmutable('tomorrow midnight');

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
            $this->subject->getTimesForDate($event, $firstDate),
        );
    }

    #[Test]
    public function getSortedTimesForDateWithoutTimesReturnsEmptyStorage(): void
    {
        $date = new \DateTimeImmutable('midnight');

        $event = new Event();

        $expectedTimes = new \SplObjectStorage();

        self::assertEquals(
            $expectedTimes,
            $this->subject->getSortedTimesForDate($event, $date),
        );
    }

    #[Test]
    public function getSortedTimesForDateWithEventTimeReturnsOneSortedTime(): void
    {
        $date = new \DateTimeImmutable('midnight');

        $time = new Time();
        $time->setTimeBegin('10:30');

        $event = new Event();
        $event->setEventTime($time);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);

        self::assertEquals(
            $expectedTimes,
            $this->subject->getSortedTimesForDate($event, $date),
        );
    }

    #[Test]
    public function getSortedTimesForDateWithDifferentTimesReturnsSortedTimes(): void
    {
        $date = new \DateTimeImmutable('midnight');
        $tomorrow = new \DateTimeImmutable('tomorrow midnight');

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
            $this->subject->getSortedTimesForDate($event, $date),
        );
    }

    #[Test]
    public function getSortedTimesForDateWithTimeExceptionsReturnsSortedTimes(): void
    {
        $date = new \DateTimeImmutable('midnight');
        $tomorrow = new \DateTimeImmutable('tomorrow midnight');

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
        $event->setEventType('duration');
        $event->setExceptions($exceptions);

        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time3);
        $expectedTimes->attach($time2);
        $expectedTimes->attach($time1);

        self::assertEquals(
            $expectedTimes,
            $this->subject->getSortedTimesForDate($event, $date),
        );
    }

    #[Test]
    public function getTimesForDateWithEventBeginWillRemoveCurrentDay(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');

        $request = new ServerRequest('https://www.example.com/', 'GET');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = $request->withQueryParams([
            'tx_events2_show' => [
                'timestamp' => $eventBegin->format('U'),
            ],
        ]);

        $GLOBALS['TYPO3_REQUEST'] = $request;

        $time = new Time();
        $time->setTimeBegin('08:00');
        $time->setDuration('02:00');

        $event = new Event();
        $event->setEventBegin($eventBegin);
        $event->setEventTime($time);

        self::assertEquals(
            new \SplObjectStorage(),
            $this->subject->getTimesForDate($event, $eventBegin, true),
        );
    }
}
