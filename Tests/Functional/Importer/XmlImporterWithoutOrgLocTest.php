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
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Importer\XmlImporter;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for XmlImporter
 */
class XmlImporterWithoutOrgLocTest extends FunctionalTestCase
{
    protected EventRepository $eventRepository;

    protected ObjectManager $objectManager;

    protected ExtConf $extConf;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
        'scheduler',
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
        self::markTestIncomplete('XmlImporterWithoutLocTest not updated until right now');

        parent::setUp();

        $this->extConf = GeneralUtility::makeInstance(ExtConf::class);
        $this->extConf->setXmlImportValidatorPath($this->instancePath . 'typo3conf/ext/events2/Resources/Public/XmlImportWithoutRelationsValidator.xsd');
        $this->extConf->setOrganizerIsRequired(false);
        $this->extConf->setLocationIsRequired(false);
        $this->extConf->setPathSegmentType('uid');

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->eventRepository = $this->objectManager->get(EventRepository::class);

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['BE_USER'],
        );

        unlink(GeneralUtility::getFileAbsFileName(
            $this->instancePath . 'typo3conf/ext/events2/Tests/Functional/Fixtures/XmlImport/Messages.txt',
        ));

        parent::tearDown();
    }

    #[Test]
    public function importWillCreate3events(): void
    {
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)
            ->retrieveFileOrFolderObject($this->instancePath . 'typo3conf/ext/events2/Tests/Functional/Fixtures/XmlImport/SuccessMissingOrganizerLocation.xml');

        $xmlImporter = $this->objectManager->get(XmlImporter::class);
        $xmlImporter->setFile($fileObject);
        $xmlImporter->setStoragePid(12);

        self::assertTrue($xmlImporter->import());
        self::assertMatchesRegularExpression(
            '/We have processed 3 events/',
            file_get_contents(GeneralUtility::getFileAbsFileName(
                $this->instancePath . 'typo3conf/ext/events2/Tests/Functional/Fixtures/XmlImport/Messages.txt',
            )),
        );

        self::assertSame(
            3,
            $this->getDatabaseConnection()
                ->selectCount(
                    '*',
                    'tx_events2_domain_model_event',
                ),
        );

        // Check, if path_segment is set for all three records
        $statement = $this->getDatabaseConnection()
            ->select(
                '*',
                'tx_events2_domain_model_event',
                '1=1',
            );

        while ($eventRecord = $statement->fetch()) {
            self::assertNotSame('/', $eventRecord['path_segment']);
            self::assertNotSame('', $eventRecord['path_segment']);
        }
    }
}
