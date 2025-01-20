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
use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for XmlImporter
 */
class XmlImporterTest extends FunctionalTestCase
{
    protected XmlImporter $subject;

    protected EventRepository $eventRepository;

    protected ObjectManager $objectManager;

    protected ExtConf $extConf;

    /**
     * @var EventDispatcher|MockObject
     */
    protected $eventDispatcherMock;

    protected PathSegmentHelper $pathSegmentHelper;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
        'scheduler',
    ];

    protected array $testExtensionsToLoad = [
        'jweiland/events2',
        'sjbr/static-info-tables',
    ];

    /**
     * I have set the date of the import events to 2025. That should be enough for the next years ;-)
     */
    protected function setUp(): void
    {
        self::markTestIncomplete('XmlImporterTest not updated until right now');

        parent::setUp();

        $this->importDataSet(__DIR__ . '/../Fixtures/sys_category.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_events2_domain_model_location.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_events2_domain_model_organizer.xml');

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->eventRepository = $this->objectManager->get(EventRepository::class);

        $this->extConf = new ExtConf(new ExtensionConfiguration());
        $this->extConf->setOrganizerIsRequired(true);
        $this->extConf->setLocationIsRequired(true);
        $this->extConf->setPathSegmentType('uid');

        $this->eventDispatcherMock = $this->createMock(EventDispatcher::class);

        $this->pathSegmentHelper = new PathSegmentHelper(
            $this->extConf,
            $this->eventDispatcherMock,
        );

        $this->subject = new XmlImporter(
            $this->objectManager->get(EventRepository::class),
            $this->objectManager->get(OrganizerRepository::class),
            $this->objectManager->get(LocationRepository::class),
            $this->objectManager->get(CategoryRepository::class),
            $this->objectManager->get(PersistenceManagerInterface::class),
            $this->pathSegmentHelper,
            new DateTimeUtility(),
            $this->extConf,
        );

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['BE_USER'],
            $this->eventRepository,
            $this->objectManager,
            $this->extConf,
            $this->eventDispatcherMock,
            $this->subject,
        );

        unlink(GeneralUtility::getFileAbsFileName(
            $this->instancePath . 'typo3conf/ext/events2/Tests/Functional/Fixtures/XmlImport/Messages.txt',
        ));

        parent::tearDown();
    }

    /**
     * @test
     */
    public function importWillCreate3events(): void
    {
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject($this->instancePath . 'typo3conf/ext/events2/Tests/Functional/Fixtures/XmlImport/Success.xml');
        $this->subject->setFile($fileObject);
        $this->subject->setStoragePid(12);

        self::assertTrue($this->subject->import());
        self::assertMatchesRegularExpression(
            '/We have processed 3 events/',
            file_get_contents(GeneralUtility::getFileAbsFileName(
                $this->instancePath . 'typo3conf/ext/events2/Tests/Functional/Fixtures/XmlImport/Messages.txt',
            )),
        );
    }

    /**
     * @test
     */
    public function importEventWithMissingCategoryEntryWillResultInErrorInMessagesTxt(): void
    {
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject($this->instancePath . 'typo3conf/ext/events2/Tests/Functional/Fixtures/XmlImport/MissingCategoryEntryEvent.xml');
        $this->subject->setFile($fileObject);
        $this->subject->setStoragePid(12);

        self::assertFalse($this->subject->import());
        self::assertMatchesRegularExpression(
            '/Missing child element.*?Expected is.*?categories/',
            file_get_contents(GeneralUtility::getFileAbsFileName(
                $this->instancePath . 'typo3conf/ext/events2/Tests/Functional/Fixtures/XmlImport/Messages.txt',
            )),
        );
    }

    /**
     * @test
     */
    public function importEventWithNotExistingCategoryInDatabaseWillResultInErrorInMessagesTxt(): void
    {
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject($this->instancePath . 'typo3conf/ext/events2/Tests/Functional/Fixtures/XmlImport/NotExistingCategoriesEvent.xml');
        $this->extConf->setLocationIsRequired(false);
        $this->extConf->setOrganizerIsRequired(false);
        $this->subject->setFile($fileObject);
        $this->subject->setStoragePid(12);

        self::assertFalse($this->subject->import());
        self::assertMatchesRegularExpression(
            '/Given category "I\'m not in database" does not exist/',
            file_get_contents(GeneralUtility::getFileAbsFileName(
                $this->instancePath . 'typo3conf/ext/events2/Tests/Functional/Fixtures/XmlImport/Messages.txt',
            )),
        );
    }

    /**
     * @test
     */
    public function importEventWithNotExistingOrganizerInDatabaseWillResultInErrorInMessagesTxt(): void
    {
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject($this->instancePath . 'typo3conf/ext/events2/Tests/Functional/Fixtures/XmlImport/NotExistingOrganizerEvent.xml');
        $this->subject->setFile($fileObject);
        $this->subject->setStoragePid(12);

        self::assertFalse($this->subject->import());
        self::assertMatchesRegularExpression(
            '/Given organizer "AG" does not exist/',
            file_get_contents(GeneralUtility::getFileAbsFileName(
                $this->instancePath . 'typo3conf/ext/events2/Tests/Functional/Fixtures/XmlImport/Messages.txt',
            )),
        );
    }

    /**
     * @test
     */
    public function importEventWithNotExistingLocationInDatabaseWillResultInErrorInMessagesTxt(): void
    {
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject($this->instancePath . 'typo3conf/ext/events2/Tests/Functional/Fixtures/XmlImport/NotExistingLocationEvent.xml');
        $this->extConf->setOrganizerIsRequired(false);
        $this->subject->setFile($fileObject);
        $this->subject->setStoragePid(12);

        self::assertFalse($this->subject->import());
        self::assertMatchesRegularExpression(
            '/Given location "Not existing" does not exist/',
            file_get_contents(GeneralUtility::getFileAbsFileName(
                $this->instancePath . 'typo3conf/ext/events2/Tests/Functional/Fixtures/XmlImport/Messages.txt',
            )),
        );
    }

    /**
     * @test
     */
    public function modifySimpleEvent(): void
    {
        // Add simple event
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject($this->instancePath . 'typo3conf/ext/events2/Tests/Functional/Fixtures/XmlImport/SimpleEvent.xml');
        $this->extConf->setLocationIsRequired(false);
        $this->extConf->setOrganizerIsRequired(false);

        $this->subject->setFile($fileObject);
        $this->subject->setStoragePid(12);
        self::assertTrue($this->subject->import());

        // Override simple event
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject($this->instancePath . 'typo3conf/ext/events2/Tests/Functional/Fixtures/XmlImport/ModifySimpleEvent.xml');
        $this->subject->setFile($fileObject);
        $this->subject->setStoragePid(12);
        self::assertTrue($this->subject->import());

        // Test, if we still have exactly one event
        $events = $this->createEventQuery()->execute(true);
        self::assertCount(
            1,
            $events,
        );
        $event = current($events);

        // Test values of event
        self::assertSame(
            'Bearbeiteter Termin',
            $event['title'],
        );
        self::assertSame(
            1762902000, // Dienstag, 12. November 2019 00:00:00 GMT+01:00
            $event['event_begin'],
        );
    }

    /**
     * @test
     */
    public function deleteSimpleEvent(): void
    {
        // Add 2 simple events
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject($this->instancePath . 'typo3conf/ext/events2/Tests/Functional/Fixtures/XmlImport/SimpleEvent.xml');
        $this->extConf->setLocationIsRequired(false);
        $this->extConf->setOrganizerIsRequired(false);

        $this->subject->setFile($fileObject);
        $this->subject->setStoragePid(12);
        $this->subject->import(); // Import event to be deleted
        $this->subject->import(); // delete one of the imported event

        // Delete one simple event
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject($this->instancePath . 'typo3conf/ext/events2/Tests/Functional/Fixtures/XmlImport/DeleteSimpleEvent.xml');
        $this->subject->setFile($fileObject);
        $this->subject->setStoragePid(12);
        self::assertTrue($this->subject->import());

        // Test, if we still have exactly one event
        $events = $this->createEventQuery()->execute(true);
        self::assertCount(
            1,
            $events,
        );
    }

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
