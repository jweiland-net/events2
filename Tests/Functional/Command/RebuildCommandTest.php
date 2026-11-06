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
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
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
    public function executeTruncatesDayTableAndCreatesDayRecords(): void
    {
        $commandTester = new CommandTester($this->get(RebuildCommand::class));

        self::assertSame(Command::SUCCESS, $commandTester->execute([]));
        self::assertSame(0, $this->countDayRecordsOfEvent(self::ORPHANED_EVENT_UID));
        self::assertSame(1, $this->countDayRecordsOfEvent($this->eventUid));
    }

    #[Test]
    public function executeWithNoTruncateKeepsDayTableAndCreatesDayRecords(): void
    {
        $commandTester = new CommandTester($this->get(RebuildCommand::class));

        self::assertSame(Command::SUCCESS, $commandTester->execute(['--no-truncate' => true]));
        self::assertSame(1, $this->countDayRecordsOfEvent(self::ORPHANED_EVENT_UID));
        self::assertSame(1, $this->countDayRecordsOfEvent($this->eventUid));
    }

    private function countDayRecordsOfEvent(int $eventUid): int
    {
        return $this->getConnectionPool()
            ->getConnectionForTable('tx_events2_domain_model_day')
            ->count('*', 'tx_events2_domain_model_day', ['event' => $eventUid]);
    }
}
