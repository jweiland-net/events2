<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Command;

use JWeiland\Events2\Command\RebuildCommand;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for class \JWeiland\Events2\Command\RebuildCommand
 */
class RebuildCommandTest extends FunctionalTestCase
{
    use InsertEventTrait;

    private const ORPHANED_EVENT_UID = 9999;

    private int $eventUid = 0;

    protected array $coreExtensionsToLoad = [
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
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

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Events2PageTree.csv');

        $this->eventUid = $this->insertEvent('Future event', new \DateTimeImmutable('tomorrow midnight'));

        // A day record which does not belong to any event. Only TRUNCATE removes it.
        $this->getConnectionPool()
            ->getConnectionForTable('tx_events2_domain_model_day')
            ->insert('tx_events2_domain_model_day', [
                'pid' => Events2Constants::PAGE_STORAGE,
                'event' => self::ORPHANED_EVENT_UID,
                'day' => (int)(new \DateTimeImmutable('tomorrow midnight'))->format('U'),
            ]);
    }

    #[Test]
    public function executeWithTruncateTruncatesDayTableAndCreatesDayRecords(): void
    {
        $commandTester = new CommandTester($this->get(RebuildCommand::class));

        self::assertSame(Command::SUCCESS, $commandTester->execute(['--truncate' => true]));
        self::assertSame(0, $this->countDayRecordsOfEvent(self::ORPHANED_EVENT_UID));
        self::assertSame(1, $this->countDayRecordsOfEvent($this->eventUid));
    }

    #[Test]
    public function executeKeepsDayTableAndCreatesDayRecords(): void
    {
        $commandTester = new CommandTester($this->get(RebuildCommand::class));

        self::assertSame(Command::SUCCESS, $commandTester->execute([]));
        self::assertSame(1, $this->countDayRecordsOfEvent(self::ORPHANED_EVENT_UID));
        self::assertSame(1, $this->countDayRecordsOfEvent($this->eventUid));
    }

    private function countDayRecordsOfEvent(int $eventUid): int
    {
        return $this->getConnectionPool()
            ->getConnectionForTable('tx_events2_domain_model_day')
            ->count('*', 'tx_events2_domain_model_day', ['event' => $eventUid]);
    }

    /**
     * @return array<string, array{0: array<string, bool>}>
     */
    public static function inputDataProvider(): array
    {
        return [
            'without truncate' => [[]],
            'with truncate' => [['--truncate' => true]],
        ];
    }

    #[Test]
    #[DataProvider('inputDataProvider')]
    public function executeWithFailingEventContinuesWithOtherEventsAndReturnsFailure(array $input): void
    {
        $secondEventUid = $this->insertEvent('Second future event', new \DateTimeImmutable('tomorrow midnight'));

        $processedEventUids = [];
        $dayRelationServiceMock = $this->createMock(DayRelationService::class);
        $dayRelationServiceMock
            ->expects(self::exactly(2))
            ->method('createDayRelations')
            ->willReturnCallback(function (int $eventUid) use (&$processedEventUids): void {
                $processedEventUids[] = $eventUid;
                if ($eventUid === $this->eventUid) {
                    throw new \RuntimeException('Broken event record', 1762430000);
                }
            });

        $commandTester = new CommandTester(new RebuildCommand(
            $this->get(DatabaseService::class),
            $dayRelationServiceMock,
            $this->get(CacheManager::class)->getCache('runtime'),
        ));

        self::assertSame(Command::FAILURE, $commandTester->execute($input));

        // No ORDER BY in query. Do not rely on the order of processed events.
        sort($processedEventUids);
        self::assertSame([$this->eventUid, $secondEventUid], $processedEventUids);
        self::assertStringContainsString(
            'Event UID: ' . $this->eventUid . ', PID: ' . Events2Constants::PAGE_STORAGE . ', Error: Broken event record',
            $commandTester->getDisplay(),
        );
    }
}
