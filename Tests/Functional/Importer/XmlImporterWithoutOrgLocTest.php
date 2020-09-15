<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Importer;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Importer\XmlImporter;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Functional test for XmlImporter
 */
class XmlImporterWithoutOrgLocTest extends FunctionalTestCase
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
     * @var ExtConf
     */
    protected $extConf;

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

        $this->extConf = GeneralUtility::makeInstance(ExtConf::class);
        $this->extConf->setXmlImportValidatorPath('EXT:events2/Resources/Public/XmlImportWithoutRelationsValidator.xsd');
        $this->extConf->setOrganizerIsRequired(false);
        $this->extConf->setLocationIsRequired(false);

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->eventRepository = $this->objectManager->get(EventRepository::class);

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
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject('EXT:events2/Tests/Functional/Fixtures/XmlImport/SuccessMissingOrganizerLocation.xml');

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
}
