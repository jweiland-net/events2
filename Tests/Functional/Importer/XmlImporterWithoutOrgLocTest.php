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
use JWeiland\Events2\Importer\XmlImporter;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for XmlImporter
 */
class XmlImporterWithoutOrgLocTest extends FunctionalTestCase
{
    protected EventRepository $eventRepository;

    protected ExtConf $extConf;

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
    public function importWillCreate3events(): void
    {
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/SuccessMissingOrganizerLocation.xml');

        $this->extConf = new ExtConf(
            xmlImportValidatorPath: 'EXT:events2/Resources/Public/XmlImportWithoutRelationsValidator.xsd',
            organizerIsRequired: false,
            locationIsRequired: false,
            pathSegmentType: 'uid',
        );

        $xmlImporter = new XmlImporter(
            $this->get(EventRepository::class),
            $this->get(OrganizerRepository::class),
            $this->get(LocationRepository::class),
            $this->get(CategoryRepository::class),
            $this->get(PersistenceManagerInterface::class),
            $this->get(PathSegmentHelper::class),
            new DateTimeUtility(),
            $this->extConf,
        );

        $xmlImporter->setFile($fileObject);
        $xmlImporter->setStoragePid(Events2Constants::PAGE_STORAGE);

        self::assertTrue($xmlImporter->import());
        self::assertMatchesRegularExpression(
            '/We have processed 3 events/',
            file_get_contents(GeneralUtility::getFileAbsFileName(
                'EXT:events2/Tests/Functional/Fixtures/XmlImport/Messages.txt',
            )),
        );

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_event');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $numberOfEvents = $queryBuilder
            ->count('*')
            ->from('tx_events2_domain_model_event')
            ->executeQuery()
            ->fetchOne();

        self::assertSame(
            3,
            $numberOfEvents,
        );

        // Check if path_segment is set for all three records
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_event');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $queryResult = $queryBuilder
            ->select('*')
            ->from('tx_events2_domain_model_event')
            ->executeQuery();

        while ($eventRecord = $queryResult->fetchAssociative()) {
            self::assertNotSame('/', $eventRecord['path_segment']);
            self::assertNotSame('', $eventRecord['path_segment']);
        }
    }
}
