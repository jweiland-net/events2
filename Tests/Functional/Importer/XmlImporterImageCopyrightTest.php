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
use JWeiland\Events2\Service\FileCopyrightService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * EXT:filemetadata adds the column "copyright" to sys_file_metadata
 */
class XmlImporterImageCopyrightTest extends FunctionalTestCase
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
    public function importStoresCopyrightOfImageInMetaDataOfFile(): void
    {
        self::assertTrue($this->importFile('SimpleEventWithImageCopyright.xml'));

        self::assertSame(
            'Max Mustermann',
            $this->getCopyrightOfFile('events2-image-with-copyright.png'),
        );
    }

    #[Test]
    public function importKeepsCopyrightOfImageWithoutCopyrightEmpty(): void
    {
        self::assertTrue($this->importFile('SimpleEventWithImageCopyright.xml'));

        self::assertSame(
            '',
            $this->getCopyrightOfFile('events2-image-without-copyright.png'),
        );
    }

    #[Test]
    public function importOfXmlWithoutCopyrightStillWorks(): void
    {
        self::assertTrue($this->importFile('SuccessMissingOrganizerLocation.xml'));
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
            $this->get(FileCopyrightService::class),
        );

        $xmlImporter->setFile($fileObject);
        $xmlImporter->setStoragePid(Events2Constants::PAGE_STORAGE);

        return $xmlImporter->import();
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
}
