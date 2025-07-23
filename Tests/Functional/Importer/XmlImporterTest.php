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
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for XmlImporter
 */
class XmlImporterTest extends FunctionalTestCase
{
    protected XmlImporter $subject;

    protected EventRepository $eventRepository;

    protected ExtConf $extConf;

    protected EventDispatcher|MockObject $eventDispatcherMock;

    protected PathSegmentHelper $pathSegmentHelper;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    /**
     * I have set the date of the import events to 2025. That should be enough for the next years ;-)
     */
    protected function setUp(): void
    {
        parent::setUp();

        date_default_timezone_set('Europe/Berlin');

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Events2PageTree.csv');

        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_location');
        $connection->insert(
            'tx_events2_domain_model_location',
            [
                'uid' => 1,
                'pid' => Events2Constants::PAGE_STORAGE,
                'location' => 'Cinema',
                'street' => 'Cinema Street',
                'house_number' => '42',
                'zip' => '12345',
                'city' => 'Everywhere',
            ]
        );

        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_organizer');
        $connection->insert(
            'tx_events2_domain_model_organizer',
            [
                'uid' => 1,
                'pid' => Events2Constants::PAGE_STORAGE,
                'organizer' => 'GmbH',
            ]
        );
        $connection->insert(
            'tx_events2_domain_model_organizer',
            [
                'uid' => 2,
                'pid' => Events2Constants::PAGE_STORAGE,
                'organizer' => 'Co. KG',
            ]
        );

        $this->eventRepository = $this->get(EventRepository::class);

        $this->extConf = new ExtConf(
            organizerIsRequired: true,
            locationIsRequired: true,
            pathSegmentType: 'uid',
        );

        $this->subject = new XmlImporter(
            $this->get(EventRepository::class),
            $this->get(OrganizerRepository::class),
            $this->get(LocationRepository::class),
            $this->get(CategoryRepository::class),
            $this->get(PersistenceManagerInterface::class),
            $this->get(PathSegmentHelper::class),
            new DateTimeUtility(),
            $this->extConf,
        );
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['BE_USER'],
            $this->eventRepository,
            $this->extConf,
            $this->subject,
        );

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
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/Success.xml');

        $this->subject->setFile($fileObject);
        $this->subject->setStoragePid(Events2Constants::PAGE_STORAGE);

        self::assertTrue($this->subject->import());
        self::assertMatchesRegularExpression(
            '/We have processed 3 events/',
            file_get_contents(GeneralUtility::getFileAbsFileName(
                'EXT:events2/Tests/Functional/Fixtures/XmlImport/Messages.txt',
            )),
        );
    }

    #[Test]
    public function importEventWithMissingCategoryEntryWillResultInErrorInMessagesTxt(): void
    {
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/MissingCategoryEntryEvent.xml');

        $this->subject->setFile($fileObject);
        $this->subject->setStoragePid(Events2Constants::PAGE_STORAGE);

        self::assertFalse($this->subject->import());
        self::assertMatchesRegularExpression(
            '/Missing child element.*?Expected is.*?categories/',
            file_get_contents(GeneralUtility::getFileAbsFileName(
                'EXT:events2/Tests/Functional/Fixtures/XmlImport/Messages.txt',
            )),
        );
    }

    #[Test]
    public function importEventWithNotExistingCategoryInDatabaseWillResultInErrorInMessagesTxt(): void
    {
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/NotExistingCategoriesEvent.xml');

        $this->extConf = new ExtConf(
            organizerIsRequired: false,
            locationIsRequired: false,
            pathSegmentType: 'uid',
        );

        $this->subject = new XmlImporter(
            $this->get(EventRepository::class),
            $this->get(OrganizerRepository::class),
            $this->get(LocationRepository::class),
            $this->get(CategoryRepository::class),
            $this->get(PersistenceManagerInterface::class),
            $this->get(PathSegmentHelper::class),
            new DateTimeUtility(),
            $this->extConf,
        );

        $this->subject->setFile($fileObject);
        $this->subject->setStoragePid(Events2Constants::PAGE_STORAGE);

        self::assertFalse($this->subject->import());
        self::assertMatchesRegularExpression(
            '/Given category "I\'m not in database" does not exist/',
            file_get_contents(GeneralUtility::getFileAbsFileName(
                'EXT:events2/Tests/Functional/Fixtures/XmlImport/Messages.txt',
            )),
        );
    }

    #[Test]
    public function importEventWithNotExistingOrganizerInDatabaseWillResultInErrorInMessagesTxt(): void
    {
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/NotExistingOrganizerEvent.xml');

        $this->subject->setFile($fileObject);
        $this->subject->setStoragePid(Events2Constants::PAGE_STORAGE);

        self::assertFalse($this->subject->import());
        self::assertMatchesRegularExpression(
            '/Given organizer "AG" does not exist/',
            file_get_contents(GeneralUtility::getFileAbsFileName(
                'EXT:events2/Tests/Functional/Fixtures/XmlImport/Messages.txt',
            )),
        );
    }

    #[Test]
    public function importEventWithNotExistingLocationInDatabaseWillResultInErrorInMessagesTxt(): void
    {
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/NotExistingLocationEvent.xml');

        $this->extConf = new ExtConf(
            organizerIsRequired: false,
            locationIsRequired: true,
            pathSegmentType: 'uid',
        );

        $this->subject = new XmlImporter(
            $this->get(EventRepository::class),
            $this->get(OrganizerRepository::class),
            $this->get(LocationRepository::class),
            $this->get(CategoryRepository::class),
            $this->get(PersistenceManagerInterface::class),
            $this->get(PathSegmentHelper::class),
            new DateTimeUtility(),
            $this->extConf,
        );

        $this->subject->setFile($fileObject);
        $this->subject->setStoragePid(Events2Constants::PAGE_STORAGE);

        self::assertFalse($this->subject->import());
        self::assertMatchesRegularExpression(
            '/Given location "Not existing" does not exist/',
            file_get_contents(GeneralUtility::getFileAbsFileName(
                'EXT:events2/Tests/Functional/Fixtures/XmlImport/Messages.txt',
            )),
        );
    }

    #[Test]
    public function importWillModifyPreviouslyImportedEventByImportId(): void
    {
        // Add a simple event
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/SimpleEvent.xml');

        $this->extConf = new ExtConf(
            organizerIsRequired: false,
            locationIsRequired: false,
            pathSegmentType: 'uid',
        );

        $this->subject = new XmlImporter(
            $this->get(EventRepository::class),
            $this->get(OrganizerRepository::class),
            $this->get(LocationRepository::class),
            $this->get(CategoryRepository::class),
            $this->get(PersistenceManagerInterface::class),
            $this->get(PathSegmentHelper::class),
            new DateTimeUtility(),
            $this->extConf,
        );

        $this->subject->setFile($fileObject);
        $this->subject->setStoragePid(Events2Constants::PAGE_STORAGE);
        self::assertTrue($this->subject->import());

        // Override a simple event
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/ModifySimpleEvent.xml');

        $this->subject->setFile($fileObject);
        $this->subject->setStoragePid(Events2Constants::PAGE_STORAGE);

        self::assertTrue(
            $this->subject->import()
        );

        // Test, if we still have exactly one event
        $queryBuilder= $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_event');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $events = $queryBuilder
            ->select('*')
            ->from('tx_events2_domain_model_event')
            ->executeQuery()
            ->fetchAllAssociative();

        self::assertCount(
            1,
            $events,
        );

        // Test values of the event
        self::assertSame(
            'Bearbeiteter Termin',
            $events[0]['title'],
        );
        self::assertSame(
            1762902000, // Dienstag, 12. November 2019 00:00:00 GMT+01:00
            $events[0]['event_begin'],
        );
    }

    #[Test]
    public function deleteSimpleEvent(): void
    {
        // Add 2 simple events
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/SimpleEvent.xml');

        $this->extConf = new ExtConf(
            organizerIsRequired: false,
            locationIsRequired: false,
            pathSegmentType: 'uid',
        );

        $this->subject = new XmlImporter(
            $this->get(EventRepository::class),
            $this->get(OrganizerRepository::class),
            $this->get(LocationRepository::class),
            $this->get(CategoryRepository::class),
            $this->get(PersistenceManagerInterface::class),
            $this->get(PathSegmentHelper::class),
            new DateTimeUtility(),
            $this->extConf,
        );

        $this->subject->setFile($fileObject);
        $this->subject->setStoragePid(Events2Constants::PAGE_STORAGE);
        $this->subject->import(); // Import event to be deleted
        $this->subject->import(); // delete one of the imported event

        // Delete one simple event
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/DeleteSimpleEvent.xml');
        $this->subject->setFile($fileObject);
        $this->subject->setStoragePid(Events2Constants::PAGE_STORAGE);
        self::assertTrue($this->subject->import());

        // Test, if we still have exactly one event
        $queryBuilder= $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_event');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $numberOfEvents = $queryBuilder
            ->count('*')
            ->from('tx_events2_domain_model_event')
            ->executeQuery()
            ->fetchOne();

        self::assertSame(
            1,
            $numberOfEvents,
        );
    }
}
