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
use JWeiland\Events2\Importer\XmlImporter;
use JWeiland\Events2\Utility\DateTimeUtility;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Functional test for XmlImporter
 */
class XmlImporterTest extends FunctionalTestCase
{
    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = [
        'extensionmanager',
        'scheduler'
    ];

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2',
        'typo3conf/ext/static_info_tables',
    ];

    /**
     * I have set the date of the import events to 2025. That should be enough for the next years ;-)
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/../Fixtures/sys_category.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_events2_domain_model_location.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_events2_domain_model_organizer.xml');

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->eventRepository = $this->objectManager->get(EventRepository::class);

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
    }

    public function tearDown(): void
    {
        unset(
            $GLOBALS['BE_USER']
        );
        unlink(GeneralUtility::getFileAbsFileName(
            'EXT:events2/Tests/Functional/Fixtures/XmlImport/Messages.txt'
        ));
        parent::tearDown();
    }

    /**
     * @test
     */
    public function importWillCreate3events(): void
    {
        $fileObject = ResourceFactory::getInstance()
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/Success.xml');
        $xmlImporter = $this->objectManager->get(XmlImporter::class);
        $xmlImporter->setFile($fileObject);
        $xmlImporter->setStoragePid(12);

        self::assertTrue($xmlImporter->import());
        self::assertRegExp(
            '/We have processed 3 events/',
            file_get_contents(GeneralUtility::getFileAbsFileName(
                'EXT:events2/Tests/Functional/Fixtures/XmlImport/Messages.txt'
            ))
        );
    }

    /**
     * @test
     */
    public function importEventWithMissingCategoryEntryWillResultInErrorInMessagesTxt(): void
    {
        $fileObject = ResourceFactory::getInstance()
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/MissingCategoryEntryEvent.xml');
        $xmlImporter = $this->objectManager->get(XmlImporter::class);
        $xmlImporter->setFile($fileObject);
        $xmlImporter->setStoragePid(12);

        self::assertFalse($xmlImporter->import());
        self::assertRegExp(
            '/Missing child element.*?Expected is.*?categories/',
            file_get_contents(GeneralUtility::getFileAbsFileName(
                'EXT:events2/Tests/Functional/Fixtures/XmlImport/Messages.txt'
            ))
        );
    }

    /**
     * @test
     */
    public function importEventWithNotExistingCategoryInDatabaseWillResultInErrorInMessagesTxt(): void
    {
        $fileObject = ResourceFactory::getInstance()
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/NotExistingCategoriesEvent.xml');
        $xmlImporter = $this->objectManager->get(XmlImporter::class);
        $xmlImporter->setFile($fileObject);
        $xmlImporter->setStoragePid(12);

        self::assertFalse($xmlImporter->import());
        self::assertRegExp(
            '/Given category "I\'m not in database" does not exist/',
            file_get_contents(GeneralUtility::getFileAbsFileName(
                'EXT:events2/Tests/Functional/Fixtures/XmlImport/Messages.txt'
            ))
        );
    }

    /**
     * @test
     */
    public function importEventWithNotExistingOrganizerInDatabaseWillResultInErrorInMessagesTxt(): void
    {
        $fileObject = ResourceFactory::getInstance()
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/NotExistingOrganizerEvent.xml');
        $extConf = new ExtConf();
        $extConf->setOrganizerIsRequired(true);
        $xmlImporter = new XmlImporter(
            $this->objectManager->get(EventRepository::class),
            $this->objectManager->get(OrganizerRepository::class),
            $this->objectManager->get(LocationRepository::class),
            $this->objectManager->get(CategoryRepository::class),
            $this->objectManager->get(PersistenceManagerInterface::class),
            new DateTimeUtility(),
            $extConf
        );
        $xmlImporter->setFile($fileObject);
        $xmlImporter->setStoragePid(12);

        self::assertFalse($xmlImporter->import());
        self::assertRegExp(
            '/Given organizer "AG" does not exist/',
            file_get_contents(GeneralUtility::getFileAbsFileName(
                'EXT:events2/Tests/Functional/Fixtures/XmlImport/Messages.txt'
            ))
        );
    }

    /**
     * @test
     */
    public function importEventWithNotExistingLocationInDatabaseWillResultInErrorInMessagesTxt(): void
    {
        $fileObject = ResourceFactory::getInstance()
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/NotExistingLocationEvent.xml');
        $extConf = new ExtConf();
        $extConf->setLocationIsRequired(true);
        $xmlImporter = new XmlImporter(
            $this->objectManager->get(EventRepository::class),
            $this->objectManager->get(OrganizerRepository::class),
            $this->objectManager->get(LocationRepository::class),
            $this->objectManager->get(CategoryRepository::class),
            $this->objectManager->get(PersistenceManagerInterface::class),
            new DateTimeUtility(),
            $extConf
        );
        $xmlImporter->setFile($fileObject);
        $xmlImporter->setStoragePid(12);

        self::assertFalse($xmlImporter->import());
        self::assertRegExp(
            '/Given location "Not existing" does not exist/',
            file_get_contents(GeneralUtility::getFileAbsFileName(
                'EXT:events2/Tests/Functional/Fixtures/XmlImport/Messages.txt'
            ))
        );
    }

    /**
     * @test
     */
    public function modifySimpleEvent(): void
    {
        // Add simple event
        $fileObject = ResourceFactory::getInstance()
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/SimpleEvent.xml');
        $xmlImporter = $this->objectManager->get(XmlImporter::class);
        $xmlImporter->setFile($fileObject);
        $xmlImporter->setStoragePid(12);
        self::assertTrue($xmlImporter->import());

        // Override simple event
        $fileObject = ResourceFactory::getInstance()
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/ModifySimpleEvent.xml');
        $xmlImporter = $this->objectManager->get(XmlImporter::class);
        $xmlImporter->setFile($fileObject);
        $xmlImporter->setStoragePid(12);
        self::assertTrue($xmlImporter->import());

        // Test, if we still have exactly one event
        $events = $this->createEventQuery()->execute(true);
        self::assertCount(
            1,
            $events
        );
        $event = current($events);

        // Test values of event
        self::assertSame(
            'Bearbeiteter Termin',
            $event['title']
        );
        self::assertSame(
            1762902000, // Dienstag, 12. November 2019 00:00:00 GMT+01:00
            $event['event_begin']
        );
    }

    /**
     * @test
     */
    public function deleteSimpleEvent(): void
    {
        // Add 2 simple events
        $fileObject = ResourceFactory::getInstance()
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/SimpleEvent.xml');
        $xmlImporter = $this->objectManager->get(XmlImporter::class);
        $xmlImporter->setFile($fileObject);
        $xmlImporter->setStoragePid(12);
        $xmlImporter->import();
        $xmlImporter->import();

        // Delete one simple event
        $fileObject = ResourceFactory::getInstance()
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/DeleteSimpleEvent.xml');
        $xmlImporter = $this->objectManager->get(XmlImporter::class);
        $xmlImporter->setFile($fileObject);
        $xmlImporter->setStoragePid(12);
        self::assertTrue($xmlImporter->import());

        // Test, if we still have exactly one event
        $events = $this->createEventQuery()->execute(true);
        self::assertCount(
            1,
            $events
        );
    }

    /**
     * @return QueryInterface
     */
    protected function createEventQuery(): QueryInterface
    {
        $query = $this->eventRepository->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);
        $query->getQuerySettings()->setLanguageOverlayMode(true);
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->getQuerySettings()->setEnableFieldsToBeIgnored(['hidden']);

        return $query;
    }
}
