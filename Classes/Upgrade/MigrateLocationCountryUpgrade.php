<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Upgrade;

use Doctrine\DBAL\ArrayParameterType;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Former versions of events2 stored the UID of a static_countries record (EXT:static_info_tables)
 * in the country column of locations. This wizard migrates these UIDs into ISO 3166-1 alpha-2 codes.
 * EXT:static_info_tables does not need to be installed, but its table "static_countries" has to exist.
 * If the database analyzer has already renamed the table to "zzz_deleted_static_countries"
 * (after removing EXT:static_info_tables), the renamed table will be used as fallback.
 */
#[UpgradeWizard('events2_migrateLocationCountry')]
final class MigrateLocationCountryUpgrade implements UpgradeWizardInterface, ChattyInterface
{
    private const LOCATION_TABLE = 'tx_events2_domain_model_location';

    /**
     * The table of EXT:static_info_tables and its name after renaming by the database analyzer
     */
    private const STATIC_COUNTRIES_TABLES = [
        'static_countries',
        'zzz_deleted_static_countries',
    ];

    private OutputInterface $output;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {
        $this->output = new NullOutput();
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    public function getTitle(): string
    {
        return '[events2] Migrate country of locations to ISO code';
    }

    public function getDescription(): string
    {
        return 'Migrate the UIDs of static_countries in column "country" of events2 locations into '
            . 'ISO 3166-1 alpha-2 codes. EXT:static_info_tables does not need to be installed, but the table '
            . '"static_countries" or "zzz_deleted_static_countries" has to exist in the database.';
    }

    public function updateNecessary(): bool
    {
        return $this->getStoredCountryUids() !== [];
    }

    /**
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function executeUpdate(): bool
    {
        $countryUids = $this->getStoredCountryUids();
        $uidsToResolve = array_values(array_filter($countryUids, static fn(int $uid): bool => $uid > 0));
        $staticCountriesTable = $this->getStaticCountriesTable();
        if ($uidsToResolve !== [] && $staticCountriesTable === '') {
            $this->output->writeln(
                '<error>Neither table "static_countries" nor "zzz_deleted_static_countries" exists. Please import '
                . 'the table "static_countries" of EXT:static_info_tables to migrate the country of events2 '
                . 'locations.</error>',
            );
            return false;
        }

        $isoCodes = $uidsToResolve === []
            ? []
            : $this->getIsoCodesOfStaticCountries($uidsToResolve, $staticCountriesTable);
        foreach ($countryUids as $countryUid) {
            $this->updateCountryOfLocations($countryUid, $isoCodes[$countryUid] ?? '');
        }

        return true;
    }

    /**
     * Returns all numeric values of the country column. These are the UIDs of static_countries.
     *
     * @return int[]
     */
    private function getStoredCountryUids(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::LOCATION_TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        $countryValues = $queryBuilder
            ->select('country')
            ->distinct()
            ->from(self::LOCATION_TABLE)
            ->executeQuery()
            ->fetchFirstColumn();

        $numericCountryValues = array_filter(
            $countryValues,
            static fn(mixed $countryValue): bool => ctype_digit(trim((string)$countryValue)),
        );

        return array_values(array_unique(array_map('intval', $numericCountryValues)));
    }

    /**
     * Returns the name of the first existing static countries table or an empty string
     */
    private function getStaticCountriesTable(): string
    {
        foreach (self::STATIC_COUNTRIES_TABLES as $tableName) {
            $tableExists = $this->connectionPool
                ->getConnectionForTable($tableName)
                ->createSchemaManager()
                ->tablesExist([$tableName]);
            if ($tableExists) {
                return $tableName;
            }
        }

        return '';
    }

    /**
     * @param int[] $countryUids
     * @return array<int, string>
     */
    private function getIsoCodesOfStaticCountries(array $countryUids, string $staticCountriesTable): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($staticCountriesTable);
        $queryBuilder->getRestrictions()->removeAll();

        $staticCountries = $queryBuilder
            ->select('uid', 'cn_iso_2')
            ->from($staticCountriesTable)
            ->where($queryBuilder->expr()->in(
                'uid',
                $queryBuilder->createNamedParameter($countryUids, ArrayParameterType::INTEGER),
            ))
            ->executeQuery()
            ->fetchAllAssociative();

        $isoCodes = [];
        foreach ($staticCountries as $staticCountry) {
            $isoCodes[(int)$staticCountry['uid']] = strtoupper(trim((string)$staticCountry['cn_iso_2']));
        }

        return $isoCodes;
    }

    private function updateCountryOfLocations(int $countryUid, string $isoCode): void
    {
        if ($countryUid > 0 && $isoCode === '') {
            $this->output->writeln(sprintf(
                '<comment>Country with UID %d not found in static_countries. Country of related locations '
                . 'has been reset.</comment>',
                $countryUid,
            ));
        }

        $this->connectionPool
            ->getConnectionForTable(self::LOCATION_TABLE)
            ->update(
                self::LOCATION_TABLE,
                ['country' => $isoCode],
                ['country' => (string)$countryUid],
            );
    }
}
