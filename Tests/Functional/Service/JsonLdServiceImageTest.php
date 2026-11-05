<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Service;

use JWeiland\Events2\Domain\Factory\TimeFactory;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Service\JsonLdService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference as CoreFileReference;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for the image of the JSON-LD data
 */
class JsonLdServiceImageTest extends FunctionalTestCase
{
    /**
     * A PNG image with 1x1 pixel
     */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

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

    protected array $additionalFoldersToCreate = [
        '/fileadmin/Extensions/events2',
    ];

    /**
     * @var array<string, mixed>
     */
    private array $serverBackup = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->serverBackup = $_SERVER;
        $_SERVER['HTTP_HOST'] = 'www.example.com';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        GeneralUtility::flushInternalRuntimeCaches();
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        GeneralUtility::flushInternalRuntimeCaches();

        parent::tearDown();
    }

    #[Test]
    public function addImageToDataWithFileOfFileadminAddsAbsoluteUrlWithoutDoubleSlash(): void
    {
        $file = $this->createFile('Extensions/events2/test1.png');

        self::assertSame(
            'https://www.example.com/fileadmin/Extensions/events2/test1.png',
            $this->getImageData(new CoreFileReference([
                'uid' => 1,
                'uid_local' => $file->getUid(),
                'title' => 'Test image',
                'description' => '',
            ]))['contentUrl'],
        );
    }

    public static function publicUrlDataProvider(): array
    {
        return [
            'path relative to host' => [
                '/fileadmin/Extensions/events2/test1.png',
                'https://www.example.com/fileadmin/Extensions/events2/test1.png',
            ],
            'path relative to host of subdirectory installation' => [
                '/subdirectory/fileadmin/test1.png',
                'https://www.example.com/subdirectory/fileadmin/test1.png',
            ],
            'path relative to current directory' => [
                'fileadmin/Extensions/events2/test1.png',
                'https://www.example.com/fileadmin/Extensions/events2/test1.png',
            ],
            'absolute URL of CDN' => [
                'https://cdn.example.com/events2/test1.png',
                'https://cdn.example.com/events2/test1.png',
            ],
            'protocol relative URL of CDN' => [
                '//cdn.example.com/events2/test1.png',
                'https://cdn.example.com/events2/test1.png',
            ],
        ];
    }

    #[Test]
    #[DataProvider('publicUrlDataProvider')]
    public function addImageToDataAddsAbsoluteUrl(string $publicUrl, string $expectedUrl): void
    {
        self::assertSame(
            $expectedUrl,
            $this->getImageData($this->createFileReferenceStub($publicUrl))['contentUrl'],
        );
    }

    #[Test]
    public function addImageToDataWithoutPublicUrlAddsNoImage(): void
    {
        self::assertSame(
            [],
            $this->getImageData($this->createFileReferenceStub(null)),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getImageData(CoreFileReference $coreFileReference): array
    {
        $fileReference = new FileReference();
        $fileReference->setOriginalResource($coreFileReference);

        $event = new Event();
        $event->addImage($fileReference);

        $jsonLdService = new JsonLdService(new TimeFactory(new DateTimeUtility()));
        (new \ReflectionMethod($jsonLdService, 'addImageToData'))->invoke($jsonLdService, $event);

        return $jsonLdService->getCollectedJsonLdData()['image'] ?? [];
    }

    private function createFileReferenceStub(?string $publicUrl): CoreFileReference
    {
        $file = self::createStub(File::class);
        $file->method('getUid')->willReturn(1);

        $coreFileReference = self::createStub(CoreFileReference::class);
        $coreFileReference->method('getOriginalFile')->willReturn($file);
        $coreFileReference->method('getPublicUrl')->willReturn($publicUrl);

        return $coreFileReference;
    }

    private function createFile(string $identifier): File
    {
        $filePath = $this->instancePath . '/fileadmin/' . $identifier;
        file_put_contents($filePath, base64_decode(self::PNG));

        $file = $this->get(ResourceFactory::class)->retrieveFileOrFolderObject('fileadmin/' . $identifier);
        self::assertInstanceOf(File::class, $file);

        return $file;
    }
}
