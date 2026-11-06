<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Command;

use JWeiland\Events2\Command\ImportCommand;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for class \JWeiland\Events2\Command\ImportCommand
 */
class ImportCommandTest extends FunctionalTestCase
{
    private const IMPORT_FOLDER = 'fileadmin/events2_import/';

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

        $this->getConnectionPool()
            ->getConnectionForTable('tx_events2_domain_model_location')
            ->insert('tx_events2_domain_model_location', [
                'pid' => Events2Constants::PAGE_STORAGE,
                'location' => 'Cinema',
            ]);

        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_organizer');
        $connection->insert('tx_events2_domain_model_organizer', [
            'pid' => Events2Constants::PAGE_STORAGE,
            'organizer' => 'GmbH',
        ]);
        $connection->insert('tx_events2_domain_model_organizer', [
            'pid' => Events2Constants::PAGE_STORAGE,
            'organizer' => 'Co. KG',
        ]);

        GeneralUtility::mkdir_deep($this->instancePath . '/' . self::IMPORT_FOLDER);
    }

    #[Test]
    public function executeWithInvalidStoragePidReturnsInvalid(): void
    {
        $commandTester = new CommandTester($this->get(ImportCommand::class));

        self::assertSame(
            Command::INVALID,
            $commandTester->execute([
                'path' => self::IMPORT_FOLDER . 'Import.xml',
                'storagePid' => '0',
            ]),
        );
    }

    #[Test]
    public function executeWithNotExistingStorageReturnsSuccess(): void
    {
        $commandTester = new CommandTester($this->get(ImportCommand::class));

        self::assertSame(
            Command::SUCCESS,
            $commandTester->execute([
                'path' => '4711:/events2_import/Import.xml',
                'storagePid' => (string)Events2Constants::PAGE_STORAGE,
            ]),
        );
        self::assertStringContainsString('Currently no file for import found', $commandTester->getDisplay());
    }

    #[Test]
    public function executeWithFolderReturnsFailure(): void
    {
        $commandTester = new CommandTester($this->get(ImportCommand::class));

        self::assertSame(
            Command::FAILURE,
            $commandTester->execute([
                'path' => self::IMPORT_FOLDER,
                'storagePid' => (string)Events2Constants::PAGE_STORAGE,
            ]),
        );
    }

    #[Test]
    public function executeImportsEventsAndDeletesFile(): void
    {
        $importFile = $this->instancePath . '/' . self::IMPORT_FOLDER . 'Import.xml';
        copy(__DIR__ . '/../Fixtures/XmlImport/Success.xml', $importFile);

        $commandTester = new CommandTester($this->get(ImportCommand::class));

        self::assertSame(
            Command::SUCCESS,
            $commandTester->execute([
                'path' => self::IMPORT_FOLDER . 'Import.xml',
                'storagePid' => (string)Events2Constants::PAGE_STORAGE,
            ]),
        );
        self::assertStringContainsString('We have processed 3 events', $commandTester->getDisplay());
        self::assertFileDoesNotExist($importFile);
        self::assertSame(3, $this->countImportedEvents());
    }

    private function countImportedEvents(): int
    {
        // Imported events are hidden
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_event');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);

        return (int)$queryBuilder
            ->count('*')
            ->from('tx_events2_domain_model_event')
            ->where($queryBuilder->expr()->eq(
                'pid',
                $queryBuilder->createNamedParameter(Events2Constants::PAGE_STORAGE, Connection::PARAM_INT),
            ))
            ->executeQuery()
            ->fetchOne();
    }
}
