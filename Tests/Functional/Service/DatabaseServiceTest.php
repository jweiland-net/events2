<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Service;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for DatabaseService
 */
class DatabaseServiceTest extends FunctionalTestCase
{
    use InsertEventTrait;

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

        $eventBegin = new \DateTimeImmutable('first day of this month midnight');
        $eventBegin = $eventBegin
            ->modify('+4 days')
            ->modify('-2 months');

        $this->insertEvent(
            title: 'Week market',
            eventBegin: $eventBegin,
            additionalFields: [
                'event_type' => 'recurring',
                'xth' => 31,
                'weekday' => 16,
            ],
            organizer: 'Stefan',
            location: 'Market',
        );

        $this->createDayRelations();
    }

    #[Test]
    public function getDaysInRangeWillFindDaysForCurrentMonth(): void
    {
        $eventBegin = new \DateTimeImmutable('first day of this month midnight');
        $eventEnd = new \DateTimeImmutable('last day of this month midnight');

        $databaseService = new DatabaseService(
            new ExtConf(
                recurringPast: 3,
                recurringFuture: 6,
            ),
            new DateTimeUtility(),
        );

        $days = $databaseService->getDaysInRange($eventBegin, $eventEnd, [Events2Constants::PAGE_STORAGE]);

        self::assertGreaterThanOrEqual(
            3,
            count($days),
        );
    }

    #[Test]
    public function getDaysInRangeWillFindFirstDayOfDurationEventOnly(): void
    {
        $firstDayOfMonth = new \DateTimeImmutable('first day of this month midnight');
        $eventBegin = $firstDayOfMonth->modify('+1 day');

        $this->insertEvent(
            title: 'Exhibition',
            eventBegin: $eventBegin,
            additionalFields: [
                'event_type' => 'duration',
                'event_end' => (int)$eventBegin->modify('+4 days')->format('U'),
            ],
        );
        $this->createDayRelations();

        $databaseService = new DatabaseService(
            new ExtConf(
                recurringPast: 3,
                recurringFuture: 6,
            ),
            new DateTimeUtility(),
        );

        $daysOfExhibition = $this->getDaysOfEvent(
            $databaseService->getDaysInRange(
                $firstDayOfMonth,
                new \DateTimeImmutable('last day of this month midnight'),
                [Events2Constants::PAGE_STORAGE],
            ),
            'Exhibition',
        );

        self::assertCount(
            1,
            $daysOfExhibition,
        );
        self::assertSame(
            (int)$eventBegin->format('U'),
            (int)$daysOfExhibition[0]['day'],
        );
    }

    #[Test]
    public function getDaysInRangeWillNotFindDaysOfDurationEventStartedInPreviousMonth(): void
    {
        $firstDayOfMonth = new \DateTimeImmutable('first day of this month midnight');
        $eventBegin = $firstDayOfMonth->modify('-3 days');

        $this->insertEvent(
            title: 'Exhibition',
            eventBegin: $eventBegin,
            additionalFields: [
                'event_type' => 'duration',
                'event_end' => (int)$firstDayOfMonth->modify('+3 days')->format('U'),
            ],
        );
        $this->createDayRelations();

        $databaseService = new DatabaseService(
            new ExtConf(
                recurringPast: 3,
                recurringFuture: 6,
            ),
            new DateTimeUtility(),
        );

        $daysOfCurrentMonth = $this->getDaysOfEvent(
            $databaseService->getDaysInRange(
                $firstDayOfMonth,
                new \DateTimeImmutable('last day of this month midnight'),
                [Events2Constants::PAGE_STORAGE],
            ),
            'Exhibition',
        );
        $daysOfPreviousMonth = $this->getDaysOfEvent(
            $databaseService->getDaysInRange(
                $firstDayOfMonth->modify('-1 month'),
                $firstDayOfMonth->modify('-1 day'),
                [Events2Constants::PAGE_STORAGE],
            ),
            'Exhibition',
        );

        self::assertCount(
            0,
            $daysOfCurrentMonth,
        );
        self::assertCount(
            1,
            $daysOfPreviousMonth,
        );
        self::assertSame(
            (int)$eventBegin->format('U'),
            (int)$daysOfPreviousMonth[0]['day'],
        );
    }

    #[Test]
    public function getDaysInRangeWillFindFirstDayOfDurationEventWithTime(): void
    {
        $firstDayOfMonth = new \DateTimeImmutable('first day of this month midnight');
        $eventBegin = $firstDayOfMonth->modify('+1 day');

        $this->insertEvent(
            title: 'Exhibition',
            eventBegin: $eventBegin,
            timeBegin: '10:00',
            additionalFields: [
                'event_type' => 'duration',
                'event_end' => (int)$eventBegin->modify('+4 days')->format('U'),
            ],
        );
        $this->createDayRelations();

        $databaseService = new DatabaseService(
            new ExtConf(
                recurringPast: 3,
                recurringFuture: 6,
            ),
            new DateTimeUtility(),
        );

        $daysOfExhibition = $this->getDaysOfEvent(
            $databaseService->getDaysInRange(
                $firstDayOfMonth,
                new \DateTimeImmutable('last day of this month midnight'),
                [Events2Constants::PAGE_STORAGE],
            ),
            'Exhibition',
        );

        self::assertCount(
            1,
            $daysOfExhibition,
        );
        self::assertSame(
            (int)$eventBegin->format('U'),
            (int)$daysOfExhibition[0]['day'],
        );
    }

    protected function getDaysOfEvent(array $days, string $title): array
    {
        return array_values(array_filter(
            $days,
            static fn(array $day): bool => $day['title'] === $title,
        ));
    }
}
