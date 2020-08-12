<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Importer;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Importer\XmlImporter;
use JWeiland\Events2\Task\Import;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

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
     * @var AbstractTask
     */
    protected $task;

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
    public function setUp()
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/../Fixtures/sys_category.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_events2_domain_model_location.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_events2_domain_model_organizer.xml');

        /** @var ObjectProphecy $taskProphecy */
        $taskProphecy = $this->prophesize(Import::class);
        /** @var AbstractTask $task */
        $this->task = $taskProphecy->reveal();
        $this->task->storagePid = 12;

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->eventRepository = $objectManager->get(EventRepository::class);

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
    }

    public function tearDown()
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
    public function importWillCreate3events()
    {
        $fileObject = ResourceFactory::getInstance()
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/Success.xml');
        $xmlImporter = new XmlImporter($fileObject, $this->task);

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
    public function importInvalidEventWillResultInErrorInMessagesTxt()
    {
        $fileObject = ResourceFactory::getInstance()
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/InvalidEvent.xml');
        $xmlImporter = new XmlImporter($fileObject, $this->task);

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
    public function modifySimpleEvent()
    {
        // Add simple event
        $fileObject = ResourceFactory::getInstance()
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/SimpleEvent.xml');
        $xmlImporter = new XmlImporter($fileObject, $this->task);
        self::assertTrue($xmlImporter->import());

        // Override simple event
        $fileObject = ResourceFactory::getInstance()
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/ModifySimpleEvent.xml');
        $xmlImporter = new XmlImporter($fileObject, $this->task);
        self::assertTrue($xmlImporter->import());

        // Test, if we still have exactly one event
        $events = $this->createEventQuery()->execute(true);
        self::assertSame(
            1,
            count($events)
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
    public function deleteSimpleEvent()
    {
        // Add 2 simple events
        $fileObject = ResourceFactory::getInstance()
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/SimpleEvent.xml');
        $xmlImporter = new XmlImporter($fileObject, $this->task);
        $xmlImporter->import();
        $xmlImporter->import();

        // Delete one simple event
        $fileObject = ResourceFactory::getInstance()
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/DeleteSimpleEvent.xml');
        $xmlImporter = new XmlImporter($fileObject, $this->task);
        self::assertTrue($xmlImporter->import());

        // Test, if we still have exactly one event
        $events = $this->createEventQuery()->execute(true);
        self::assertSame(
            1,
            count($events)
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
