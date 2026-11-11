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
 * The JsonImporter uses the DataHandler, which generates the path segment
 * of new events while inserting them.
 */
class JsonImporterTest extends FunctionalTestCase
{
    private const TABLE = 'tx_events2_domain_model_event';

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
        $this->getConnectionPool()->getConnectionForTable('be_users')->insert(
            'be_users',
            ['uid' => 1, 'username' => 'reaction', 'admin' => 1],
        );
        $this->getConnectionPool()->getConnectionForTable(self::TABLE)->insert(
            self::TABLE,
            ['pid' => Events2Constants::PAGE_STORAGE, 'title' => 'Weekly market', 'path_segment' => 'weekly-market'],
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
    public function importOfEventsWithExistingTitleAppendsIncrementToPathSegment(): void
    {
        $importConfiguration = new ImportConfiguration(
            [$this->getImportEvent(1, 'Weekly market'), $this->getImportEvent(2, 'Weekly market')],
            new ReactionInstruction(['storage_pid' => Events2Constants::PAGE_STORAGE]),
            0,
        );

        self::assertTrue($this->get(JsonImporter::class)->import($importConfiguration));
        self::assertSame(
            ['weekly-market', 'weekly-market-1', 'weekly-market-2'],
            $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE)
                ->select('path_segment')
                ->from(self::TABLE)
                ->orderBy('uid', 'ASC')
                ->executeQuery()
                ->fetchFirstColumn(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getImportEvent(int $importId, string $title): array
    {
        return [
            'uid' => $importId,
            'starttime' => null,
            'endtime' => null,
            'hidden' => false,
            'event_type' => 'single',
            'top_of_list' => false,
            'title' => $title,
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
            'images' => [],
            'video_link' => [],
            'download_links' => [],
        ];
    }
}
