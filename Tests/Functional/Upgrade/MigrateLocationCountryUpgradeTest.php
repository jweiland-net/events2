<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Upgrade;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Upgrade\MigrateLocationCountryUpgrade;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for class \JWeiland\Events2\Upgrade\MigrateLocationCountryUpgrade
 */
class MigrateLocationCountryUpgradeTest extends FunctionalTestCase
{
    private const LOCATION_TABLE = 'tx_events2_domain_model_location';

    private const STATIC_COUNTRIES_TABLE = 'static_countries';

    private const RENAMED_STATIC_COUNTRIES_TABLE = 'zzz_deleted_static_countries';

    private MigrateLocationCountryUpgrade $subject;

    private BufferedOutput $output;

    protected array $coreExtensionsToLoad = [
        'install',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'jweiland/events2',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropStaticCountriesTables();

        $this->output = new BufferedOutput();
        $this->subject = new MigrateLocationCountryUpgrade($this->getConnectionPool());
        $this->subject->setOutput($this->output);
    }

    protected function tearDown(): void
    {
        $this->dropStaticCountriesTables();

        unset($this->subject, $this->output);

        parent::tearDown();
    }

    #[Test]
    public function updateNecessaryWithIsoCodesReturnsFalse(): void
    {
        $this->insertLocation('DE');
        $this->insertLocation('');

        self::assertFalse($this->subject->updateNecessary());
    }

    #[Test]
    public function updateNecessaryWithStaticCountryUidReturnsTrue(): void
    {
        $this->insertLocation('DE');
        $this->insertLocation('54');

        self::assertTrue($this->subject->updateNecessary());
    }

    #[Test]
    public function executeUpdateMigratesStaticCountryUidsToIsoCodes(): void
    {
        $this->createStaticCountriesTable([54 => 'DE', 125 => 'CH']);
        $germanyUid = $this->insertLocation('54');
        $deletedGermanyUid = $this->insertLocation('54', ['deleted' => 1]);
        $switzerlandUid = $this->insertLocation('125');
        $zeroUid = $this->insertLocation('0');
        $austriaUid = $this->insertLocation('AT');
        $unknownUid = $this->insertLocation('999');

        self::assertTrue($this->subject->executeUpdate());

        self::assertSame('DE', $this->getCountryOfLocation($germanyUid));
        self::assertSame('DE', $this->getCountryOfLocation($deletedGermanyUid));
        self::assertSame('CH', $this->getCountryOfLocation($switzerlandUid));
        self::assertSame('', $this->getCountryOfLocation($zeroUid));
        self::assertSame('AT', $this->getCountryOfLocation($austriaUid));
        self::assertSame('', $this->getCountryOfLocation($unknownUid));
        self::assertStringContainsString('Country with UID 999 not found', $this->output->fetch());
        self::assertFalse($this->subject->updateNecessary());
    }

    #[Test]
    public function executeUpdateWithoutStaticCountriesTableReturnsFalse(): void
    {
        $locationUid = $this->insertLocation('54');

        self::assertFalse($this->subject->executeUpdate());

        self::assertSame('54', $this->getCountryOfLocation($locationUid));
        self::assertStringContainsString(
            'Neither table "static_countries" nor "zzz_deleted_static_countries" exists',
            $this->output->fetch(),
        );
    }

    #[Test]
    public function executeUpdateWithRenamedStaticCountriesTableMigratesStaticCountryUids(): void
    {
        $this->createStaticCountriesTable([54 => 'DE'], self::RENAMED_STATIC_COUNTRIES_TABLE);
        $locationUid = $this->insertLocation('54');

        self::assertTrue($this->subject->executeUpdate());

        self::assertSame('DE', $this->getCountryOfLocation($locationUid));
    }

    #[Test]
    public function executeUpdatePrefersStaticCountriesTableOverRenamedTable(): void
    {
        $this->createStaticCountriesTable([54 => 'DE']);
        $this->createStaticCountriesTable([54 => 'AT'], self::RENAMED_STATIC_COUNTRIES_TABLE);
        $locationUid = $this->insertLocation('54');

        self::assertTrue($this->subject->executeUpdate());

        self::assertSame('DE', $this->getCountryOfLocation($locationUid));
    }

    #[Test]
    public function executeUpdateWithZeroValuesOnlyDoesNotNeedStaticCountriesTable(): void
    {
        $locationUid = $this->insertLocation('0');

        self::assertTrue($this->subject->executeUpdate());

        self::assertSame('', $this->getCountryOfLocation($locationUid));
    }

    /**
     * @param array<string, mixed> $additionalFields
     */
    private function insertLocation(string $country, array $additionalFields = []): int
    {
        $connection = $this->getConnectionPool()->getConnectionForTable(self::LOCATION_TABLE);
        $connection->insert(
            self::LOCATION_TABLE,
            array_merge(
                [
                    'pid' => Events2Constants::PAGE_STORAGE,
                    'location' => 'Location with country ' . $country,
                    'country' => $country,
                ],
                $additionalFields,
            ),
        );

        return (int)$connection->lastInsertId();
    }

    private function getCountryOfLocation(int $locationUid): string
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::LOCATION_TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        return (string)$queryBuilder
            ->select('country')
            ->from(self::LOCATION_TABLE)
            ->where($queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($locationUid, Connection::PARAM_INT),
            ))
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @param array<int, string> $isoCodes
     */
    private function createStaticCountriesTable(
        array $isoCodes,
        string $tableName = self::STATIC_COUNTRIES_TABLE,
    ): void {
        $connection = $this->getConnectionPool()->getConnectionForTable($tableName);

        $table = new Table($tableName);
        $table->addColumn('uid', Types::INTEGER, ['notnull' => true, 'default' => 0]);
        $table->addColumn('cn_iso_2', Types::STRING, ['length' => 2, 'notnull' => true, 'default' => '']);
        $connection->createSchemaManager()->createTable($table);

        foreach ($isoCodes as $uid => $isoCode) {
            $connection->insert($tableName, ['uid' => $uid, 'cn_iso_2' => $isoCode]);
        }
    }

    private function dropStaticCountriesTables(): void
    {
        foreach ([self::STATIC_COUNTRIES_TABLE, self::RENAMED_STATIC_COUNTRIES_TABLE] as $tableName) {
            $schemaManager = $this->getConnectionPool()
                ->getConnectionForTable($tableName)
                ->createSchemaManager();

            if ($schemaManager->tablesExist([$tableName])) {
                $schemaManager->dropTable($tableName);
            }
        }
    }
}
