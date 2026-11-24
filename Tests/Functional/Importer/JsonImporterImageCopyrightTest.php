<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Importer;

use JWeiland\Events2\Configuration\ImportConfiguration;
use JWeiland\Events2\Importer\JsonImporter;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Reactions\Authentication\ReactionUserAuthentication;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * EXT:filemetadata adds the column "copyright" to sys_file_metadata
 */
class JsonImporterImageCopyrightTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'filemetadata',
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
        $this->getConnectionPool()->getConnectionForTable('be_users')->insert(
            'be_users',
            ['uid' => 1, 'username' => 'reaction', 'admin' => 1],
        );
        $this->getConnectionPool()->getConnectionForTable('sys_file_storage')->insert(
            'sys_file_storage',
            [
                'uid' => 1,
                'name' => 'fileadmin',
                'driver' => 'Local',
                'configuration' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>'
                    . '<T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
                    . '<field index="basePath"><value index="vDEF">fileadmin/</value></field>'
                    . '<field index="pathType"><value index="vDEF">relative</value></field>'
                    . '<field index="caseSensitive"><value index="vDEF">1</value></field>'
                    . '</language></sheet></data></T3FlexForms>',
                'is_browsable' => 1,
                'is_public' => 1,
                'is_writable' => 1,
                'is_online' => 1,
            ],
        );

        $backendUser = new ReactionUserAuthentication();
        $backendUser->setBeUserByUid(1);
        $backendUser->workspace = 0;
        $GLOBALS['BE_USER'] = $backendUser;
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);

        parent::tearDown();
    }

    #[Test]
    public function importStoresCopyrightOfImageInMetaDataOfFile(): void
    {
        self::assertTrue($this->get(JsonImporter::class)->import($this->getImportConfiguration()));

        self::assertSame(
            'Max Mustermann',
            $this->getCopyrightOfFile('events2-json-image-with-copyright.png'),
        );
    }

    #[Test]
    public function importKeepsCopyrightOfImageWithoutCopyrightEmpty(): void
    {
        self::assertTrue($this->get(JsonImporter::class)->import($this->getImportConfiguration()));

        self::assertSame(
            '',
            $this->getCopyrightOfFile('events2-json-image-without-copyright.png'),
        );
    }

    #[Test]
    public function importCreatesVisibleFileReferences(): void
    {
        self::assertTrue($this->get(JsonImporter::class)->import($this->getImportConfiguration()));

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()->removeAll();
        $hiddenFlags = $queryBuilder
            ->select('hidden')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter('tx_events2_domain_model_event'),
                ),
            )
            ->executeQuery()
            ->fetchFirstColumn();

        self::assertSame([0, 0], array_map('intval', $hiddenFlags));
    }

    private function getImportConfiguration(): ImportConfiguration
    {
        return new ImportConfiguration(
            [$this->getImportEvent()],
            new ReactionInstruction([
                'storage_pid' => Events2Constants::PAGE_STORAGE,
                'storage_folder' => '1:/',
            ]),
            0,
        );
    }

    private function getCopyrightOfFile(string $fileName): string
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_file');
        $copyright = $queryBuilder
            ->select('m.copyright')
            ->from('sys_file', 'f')
            ->join(
                'f',
                'sys_file_metadata',
                'm',
                $queryBuilder->expr()->eq('m.file', $queryBuilder->quoteIdentifier('f.uid')),
            )
            ->where(
                $queryBuilder->expr()->eq('f.name', $queryBuilder->createNamedParameter($fileName)),
            )
            ->executeQuery()
            ->fetchOne();

        self::assertNotFalse($copyright, 'Meta data of imported file ' . $fileName . ' not found');

        return (string)$copyright;
    }

    /**
     * @return array<string, mixed>
     */
    private function getImportEvent(): array
    {
        return [
            'uid' => 1,
            'starttime' => null,
            'endtime' => null,
            'hidden' => false,
            'event_type' => 'single',
            'top_of_list' => false,
            'title' => 'Event with copyright',
            'event_begin' => '2030-11-11',
            'event_time' => [],
            'event_end' => null,
            'same_day' => false,
            'multiple_times' => [],
            'xth' => 0,
            'weekday' => 0,
            'different_times' => [],
            'each_weeks' => 0,
            'each_months' => 0,
            'recurring_end' => null,
            'exceptions' => [],
            'teaser' => '',
            'detail_information' => '',
            'free_entry' => false,
            'ticket_link' => [],
            'categories' => [],
            'location' => [],
            'organizers' => [],
            'images' => [
                [
                    'url' => 'https://www.example.com/events2-json-image-with-copyright.png',
                    'copyright' => 'Max Mustermann',
                ],
                [
                    'url' => 'https://www.example.com/events2-json-image-without-copyright.png',
                ],
            ],
            'video_link' => [],
            'download_links' => [],
        ];
    }
}
