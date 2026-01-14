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
use JWeiland\Events2\Service\DayGeneratorService;
use JWeiland\Events2\Service\DayRecordBuilderService;
use JWeiland\Events2\Service\TimeService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 */
class DayRecordBuilderServiceTest extends FunctionalTestCase
{
    use InsertEventTrait;

    protected DayRecordBuilderService $subject;

    protected DayGeneratorService $dayGeneratorService;

    protected TimeService|MockObject $timeServiceMock;

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

        $this->timeServiceMock = $this->createStub(TimeService::class);

        $this->dayGeneratorService = new DayGeneratorService(
            $this->timeServiceMock,
            new ExtConf(
                recurringPast: 3,
                recurringFuture: 6,
            ),
            new DateTimeUtility(),
            $this->createStub(EventDispatcher::class),
            $this->createStub(Logger::class),
        );

        $this->subject = new DayRecordBuilderService();
    }

    protected function tearDown(): void
    {
        unset(
            $this->timeServiceMock,
            $this->dayGeneratorService,
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function buildDayRecordsWithSingleEventWillCreateDayRecordForSingleEvents(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');

        $eventRecord = [
            'uid' => 123,
            'pid' => Events2Constants::PAGE_STORAGE,
            'event_type' => 'single',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => 0,
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => [],
        ];

        $dayGeneratorResult = $this->dayGeneratorService->getDayGeneratorResultForEventRecord($eventRecord);

        $this->subject->buildDayRecordsFor($dayGeneratorResult);

        self::assertArrayIsIdenticalToArrayIgnoringListOfKeys(
            [
                'pid' => Events2Constants::PAGE_STORAGE,
                'hidden' => 0,
                'fe_group' => 0,
                'day' => (int)$eventBegin->format('U'),
                'day_time' => (int)$eventBegin->format('U'),
                'sort_day_time' => (int)$eventBegin->format('U'),
                'same_day_time' => (int)$eventBegin->format('U'),
                'is_removed_date' => 0,
            ],
            current($dayGeneratorResult->getDayRecords()),
            [
                'tstamp',
                'crdate',
            ],
        );
    }

    #[Test]
    public function buildDayRecordsWithSingleEventWillCreateDayRecordForSingleEventsInWorkspace(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');

        $eventUid = $this->insertEvent(
            title: 'Week market',
            eventBegin: $eventBegin,
        );
        $this->createDayRelations(1);

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->workspace = 0;

        $eventRecord = [
            'uid' => 123,
            'pid' => Events2Constants::PAGE_STORAGE,
            'title' => 'TYPO3',
            'event_type' => 'single',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => 0,
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 0,
            'each_months' => 0,
            'exceptions' => [],
            't3ver_wsid' => 1,
            't3ver_oid' => $eventUid,
            't3ver_state' => VersionState::DELETE_PLACEHOLDER->value,
        ];

        $dayGeneratorResult = $this->dayGeneratorService->getDayGeneratorResultForEventRecord($eventRecord);

        $this->subject->buildDayRecordsFor($dayGeneratorResult);

        self::assertArrayIsIdenticalToArrayIgnoringListOfKeys(
            [
                'pid' => Events2Constants::PAGE_STORAGE,
                'hidden' => 0,
                'fe_group' => 0,
                'day' => (int)$eventBegin->format('U'),
                'day_time' => (int)$eventBegin->format('U'),
                'sort_day_time' => (int)$eventBegin->format('U'),
                'same_day_time' => (int)$eventBegin->format('U'),
                'is_removed_date' => 0,
            ],
            current($dayGeneratorResult->getDayRecords()),
            [
                'tstamp',
                'crdate',
            ],
        );
    }

    #[Test]
    public function buildDayRecordsWithDurationEventWillCreateDayRecordForDurationEvents(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');
        $tomorrow = $eventBegin->modify('tomorrow');
        $inTwoDays = $eventBegin->modify('+2 days midnight');

        $eventRecord = [
            'uid' => 123,
            'pid' => Events2Constants::PAGE_STORAGE,
            'event_type' => 'duration',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => (int)$inTwoDays->format('U'),
            'recurring_end' => 0,
            'xth' => 0,
            'weekday' => 0,
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

        $dayGeneratorResult = $this->dayGeneratorService->getDayGeneratorResultForEventRecord($eventRecord);

        $this->subject->buildDayRecordsFor($dayGeneratorResult);

        self::assertArrayIsIdenticalToArrayIgnoringListOfKeys(
            [
                [
                    'pid' => Events2Constants::PAGE_STORAGE,
                    'hidden' => 0,
                    'fe_group' => 0,
                    'day' => (int)$tomorrow->format('U'),
                    'day_time' => (int)$tomorrow->format('U'),
                    'sort_day_time' => (int)$tomorrow->format('U'),
                    'same_day_time' => (int)$tomorrow->format('U'),
                    'is_removed_date' => 0,
                ],
                [
                    'pid' => Events2Constants::PAGE_STORAGE,
                    'hidden' => 0,
                    'fe_group' => 0,
                    'day' => (int)$inTwoDays->format('U'),
                    'day_time' => (int)$inTwoDays->format('U'),
                    'sort_day_time' => (int)$tomorrow->format('U'),
                    'same_day_time' => (int)$tomorrow->format('U'),
                    'is_removed_date' => 0,
                ],
            ],
            $dayGeneratorResult->getDayRecords(),
            [
                'tstamp',
                'crdate',
            ],
        );
    }

    #[Test]
    public function buildDayRecordsWithRecurringEventWillCreateDayRecordForRecurringEvents(): void
    {
        $eventBegin = new \DateTimeImmutable('midnight');
        $nextWeek = $eventBegin->modify('+7 days');
        $recurringEnd = $eventBegin->modify('+14 days');

        $eventRecord = [
            'uid' => 123,
            'pid' => Events2Constants::PAGE_STORAGE,
            'event_type' => 'recurring',
            'event_begin' => (int)$eventBegin->format('U'),
            'event_end' => 0,
            'recurring_end' => (int)$recurringEnd->format('U'),
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 1,
            'each_months' => 0,
            'exceptions' => [],
        ];

        $dayGeneratorResult = $this->dayGeneratorService->getDayGeneratorResultForEventRecord($eventRecord);

        $this->subject->buildDayRecordsFor($dayGeneratorResult);

        $dayRecords = $dayGeneratorResult->getDayRecords();

        self::assertArrayIsIdenticalToArrayIgnoringListOfKeys(
            [
                'pid' => Events2Constants::PAGE_STORAGE,
                'hidden' => 0,
                'fe_group' => 0,
                'day' => (int)$eventBegin->format('U'),
                'day_time' => (int)$eventBegin->format('U'),
                'sort_day_time' => (int)$eventBegin->format('U'),
                'same_day_time' => (int)$eventBegin->format('U'),
                'is_removed_date' => 0,
            ],
            reset($dayRecords),
            [
                'tstamp',
                'crdate',
            ],
        );

        self::assertArrayIsIdenticalToArrayIgnoringListOfKeys(
            [
                'pid' => Events2Constants::PAGE_STORAGE,
                'hidden' => 0,
                'fe_group' => 0,
                'day' => (int)$nextWeek->format('U'),
                'day_time' => (int)$nextWeek->format('U'),
                'sort_day_time' => (int)$nextWeek->format('U'),
                'same_day_time' => (int)$nextWeek->format('U'),
                'is_removed_date' => 0,
            ],
            next($dayRecords),
            [
                'tstamp',
                'crdate',
            ],
        );

        self::assertArrayIsIdenticalToArrayIgnoringListOfKeys(
            [
                'pid' => Events2Constants::PAGE_STORAGE,
                'hidden' => 0,
                'fe_group' => 0,
                'day' => (int)$recurringEnd->format('U'),
                'day_time' => (int)$recurringEnd->format('U'),
                'sort_day_time' => (int)$recurringEnd->format('U'),
                'same_day_time' => (int)$recurringEnd->format('U'),
                'is_removed_date' => 0,
            ],
            next($dayRecords),
            [
                'tstamp',
                'crdate',
            ],
        );
    }
}
