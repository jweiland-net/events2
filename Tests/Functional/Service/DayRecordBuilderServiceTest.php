<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Service;

use JWeiland\Events2\Service\DayGeneratorService;
use JWeiland\Events2\Service\DayRecordBuilderService;
use JWeiland\Events2\Service\Result\DayGeneratorResult;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 */
class DayRecordBuilderServiceTest extends FunctionalTestCase
{
    protected DayRecordBuilderService $dayRecordBuilderService;

    protected DayGeneratorService $dayGeneratorService;

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

        $this->dayGeneratorService = GeneralUtility::makeInstance(DayGeneratorService::class);

        $this->dayRecordBuilderService = new DayRecordBuilderService();
    }

    protected function tearDown(): void
    {
        unset(
            $this->dayRecordBuilderService,
        );

        parent::tearDown();
    }

    #[Test]
    public function buildDayRecordsWithSingleEventWillCreateDayRecordForSingleEvents(): void
    {
        $eventBegin = new \DateTimeImmutable();
        $eventBegin = $eventBegin->modify('midnight');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'single',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => 0,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => [],
        ];

        $dayGeneratorResult = $this->dayGeneratorService->getDateTimeStorageForEventRecord($eventRecord);

        $this->dayRecordBuilderService->buildDayRecordsFor($dayGeneratorResult);

        self::assertArrayIsIdenticalToArrayIgnoringListOfKeys(
            [
                'pid' => 0,
                'hidden' => 0,
                'fe_group' => 0,
                't3ver_wsid' => 0,
                'day' => (int)$eventBegin->format('U'),
                'day_time' => (int)$eventBegin->format('U'),
                'sort_day_time' => (int)$eventBegin->format('U'),
                'same_day_time' => (int)$eventBegin->format('U'),
                'is_removed_date' => 0,
                'event' => 123,
            ],
            current($dayGeneratorResult->getDayRecords()),
            [
                'tstamp',
                'crdate',
            ]
        );
    }

    #[Test]
    public function buildDayRecordsWithSingleEventWillCreateDayRecordForDurationEvents(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');
        $tomorrow = $eventBegin->modify('tomorrow');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'duration',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => (int)$tomorrow->format('U'),
            'recurring_end' => 0,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => [
                1 => [
                    'hidden' => 0,
                    'exception_type' => 'Remove',
                    'exception_date' => (int)$eventBegin->format('U'),
                    'show_anyway' => 0,
                ],
            ],
        ];

        $dayGeneratorResult = $this->dayGeneratorService->getDateTimeStorageForEventRecord($eventRecord);

        $this->dayRecordBuilderService->buildDayRecordsFor($dayGeneratorResult);

        self::assertArrayIsIdenticalToArrayIgnoringListOfKeys(
            [
                'pid' => 0,
                'hidden' => 0,
                'fe_group' => 0,
                't3ver_wsid' => 0,
                'day' => (int)$tomorrow->format('U'),
                'day_time' => (int)$tomorrow->format('U'),
                'sort_day_time' => (int)$tomorrow->format('U'),
                'same_day_time' => (int)$tomorrow->format('U'),
                'is_removed_date' => 0,
                'event' => 123,
            ],
            current($dayGeneratorResult->getDayRecords()),
            [
                'tstamp',
                'crdate',
            ]
        );
    }

    #[Test]
    public function buildDayRecordsWithSingleEventWillCreateDayRecordForRecurringEvents(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');
        $nextWeek = $eventBegin->modify('+7 days');
        $recurringEnd = $eventBegin->modify('+14 days');

        $eventRecord = [
            'uid' => 123,
            'event_type' => 'recurring',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => (int)$recurringEnd->format('U'),
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 1,
            'each_months' => 0,
            'exceptions' => [],
        ];

        $dayGeneratorResult = $this->dayGeneratorService->getDateTimeStorageForEventRecord($eventRecord);

        $this->dayRecordBuilderService->buildDayRecordsFor($dayGeneratorResult);

        $dayRecords = $dayGeneratorResult->getDayRecords();
        var_dump($dayGeneratorResult->getDateTimeResultStorageSorted()->getArrayCopy());

        var_dump(reset($dayRecords));
        var_dump(next($dayRecords));
        var_dump(next($dayRecords));

        self::assertArrayIsIdenticalToArrayIgnoringListOfKeys(
            [
                'pid' => 0,
                'hidden' => 0,
                'fe_group' => 0,
                't3ver_wsid' => 0,
                'day' => (int)$eventBegin->format('U'),
                'day_time' => (int)$eventBegin->format('U'),
                'sort_day_time' => (int)$eventBegin->format('U'),
                'same_day_time' => (int)$eventBegin->format('U'),
                'is_removed_date' => 0,
                'event' => 123,
            ],
            reset($dayRecords),
            [
                'tstamp',
                'crdate',
            ]
        );

        self::assertArrayIsIdenticalToArrayIgnoringListOfKeys(
            [
                'pid' => 0,
                'hidden' => 0,
                'fe_group' => 0,
                't3ver_wsid' => 0,
                'day' => (int)$nextWeek->format('U'),
                'day_time' => (int)$nextWeek->format('U'),
                'sort_day_time' => (int)$nextWeek->format('U'),
                'same_day_time' => (int)$nextWeek->format('U'),
                'is_removed_date' => 0,
                'event' => 123,
            ],
            next($dayRecords),
            [
                'tstamp',
                'crdate',
            ]
        );

        self::assertArrayIsIdenticalToArrayIgnoringListOfKeys(
            [
                'pid' => 0,
                'hidden' => 0,
                'fe_group' => 0,
                't3ver_wsid' => 0,
                'day' => (int)$recurringEnd->format('U'),
                'day_time' => (int)$recurringEnd->format('U'),
                'sort_day_time' => (int)$recurringEnd->format('U'),
                'same_day_time' => (int)$recurringEnd->format('U'),
                'is_removed_date' => 0,
                'event' => 123,
            ],
            next($dayRecords),
            [
                'tstamp',
                'crdate',
            ]
        );
    }
}
