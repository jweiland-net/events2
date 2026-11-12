<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Importer;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Repository\CategoryRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Domain\Repository\LocationRepository;
use JWeiland\Events2\Domain\Repository\OrganizerRepository;
use JWeiland\Events2\Helper\PathSegmentHelper;
use JWeiland\Events2\Importer\EventRecordBuilder;
use JWeiland\Events2\Importer\XmlImporter;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for XmlImporter
 */
class XmlImporterWithoutOrgLocTest extends FunctionalTestCase
{
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

    /**
     * I have set the date of the import events to 2025. That should be enough for the next years ;-)
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $messagesFile = GeneralUtility::getFileAbsFileName(
            'EXT:events2/Tests/Functional/Fixtures/XmlImport/Messages.txt',
        );
        if (is_file($messagesFile)) {
            unlink($messagesFile);
        }

        parent::tearDown();
    }

    #[Test]
    public function importWillCreate3eventsWithPathSegmentOfTitle(): void
    {
        self::assertTrue($this->importFile('SuccessMissingOrganizerLocation.xml'));
        self::assertMatchesRegularExpression(
            '/We have processed 3 events/',
            file_get_contents(GeneralUtility::getFileAbsFileName(
                'EXT:events2/Tests/Functional/Fixtures/XmlImport/Messages.txt',
            )),
        );

        self::assertSame(
            ['einfacher-termin', 'wiederkehrender-termin', 'durchgaengiger-termin'],
            $this->getPathSegmentsOrderedByUid(),
        );
    }

    #[Test]
    public function importOfEventsWithSameTitleWillAppendIncrementToPathSegment(): void
    {
        self::assertTrue($this->importFile('DuplicateTitleEvents.xml'));

        self::assertSame(
            ['weekly-market', 'weekly-market-1'],
            $this->getPathSegmentsOrderedByUid(),
        );
    }

    #[Test]
    public function importUsesFieldsOfTcaGeneratorOptionsForPathSegment(): void
    {
        $this->overrideGeneratorOptions(['fields' => ['title', 'event_type']]);

        self::assertTrue($this->importFile('SuccessMissingOrganizerLocation.xml'));

        self::assertSame(
            ['einfacher-termin-single', 'wiederkehrender-termin-recurring', 'durchgaengiger-termin-duration'],
            $this->getPathSegmentsOrderedByUid(),
        );
        $this->assertPathSegmentsEqualSlugsOfStoredRecords();
    }

    #[Test]
    public function importPassesCompleteEventRecordToPostModifiersOfTca(): void
    {
        $this->overrideGeneratorOptions([
            'postModifiers' => [
                static fn(array $parameters): string => $parameters['slug']
                    . '-' . $parameters['record']['event_type']
                    . '-' . date('Y-m-d', (int)$parameters['record']['event_begin']),
            ],
        ]);

        self::assertTrue($this->importFile('SuccessMissingOrganizerLocation.xml'));

        self::assertSame(
            [
                'einfacher-termin-single-2030-11-11',
                'wiederkehrender-termin-recurring-2030-11-11',
                'durchgaengiger-termin-duration-2030-11-18',
            ],
            $this->getPathSegmentsOrderedByUid(),
        );
        $this->assertPathSegmentsEqualSlugsOfStoredRecords();
    }

    /**
     * @param array<string, mixed> $generatorOptions
     */
    private function overrideGeneratorOptions(array $generatorOptions): void
    {
        $slugConfiguration = &$GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['path_segment']['config'];
        $slugConfiguration['generatorOptions'] = array_replace($slugConfiguration['generatorOptions'], $generatorOptions);
        unset($slugConfiguration);
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
    }

    /**
     * The DataHandler generates the slug of a stored record from the
     * database record. The XML import must result in the same slug.
     */
    private function assertPathSegmentsEqualSlugsOfStoredRecords(): void
    {
        $slugHelper = GeneralUtility::makeInstance(
            SlugHelper::class,
            'tx_events2_domain_model_event',
            'path_segment',
            $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['path_segment']['config'],
        );

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_event');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $storedRecords = $queryBuilder
            ->select('*')
            ->from('tx_events2_domain_model_event')
            ->executeQuery()
            ->fetchAllAssociative();

        self::assertNotEmpty($storedRecords);
        foreach ($storedRecords as $storedRecord) {
            self::assertSame(
                $slugHelper->generate($storedRecord, (int)$storedRecord['pid']),
                $storedRecord['path_segment'],
            );
        }
    }

    private function importFile(string $fileName): bool
    {
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/' . $fileName);

        $xmlImporter = new XmlImporter(
            $this->get(EventRepository::class),
            $this->get(OrganizerRepository::class),
            $this->get(LocationRepository::class),
            $this->get(CategoryRepository::class),
            $this->get(PersistenceManagerInterface::class),
            $this->get(PathSegmentHelper::class),
            new DateTimeUtility(),
            new ExtConf(
                xmlImportValidatorPath: 'EXT:events2/Resources/Public/XmlImportWithoutRelationsValidator.xsd',
                organizerIsRequired: false,
                locationIsRequired: false,
            ),
            $this->get(EventRecordBuilder::class),
        );

        $xmlImporter->setFile($fileObject);
        $xmlImporter->setStoragePid(Events2Constants::PAGE_STORAGE);

        return $xmlImporter->import();
    }

    /**
     * @return string[]
     */
    private function getPathSegmentsOrderedByUid(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_event');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);

        return $queryBuilder
            ->select('path_segment')
            ->from('tx_events2_domain_model_event')
            ->orderBy('uid', 'ASC')
            ->executeQuery()
            ->fetchFirstColumn();
    }
}
