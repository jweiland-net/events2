<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Property\TypeConverter;

use JWeiland\Checkfaluploads\Service\FalUploadService;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/*
 * A for PropertyMapper to convert multiple file uploads into an array
 */
class UploadMultipleFilesConverter extends AbstractTypeConverter
{
    /**
     * @var array<string>
     */
    protected $sourceTypes = ['array'];

    /**
     * @var string
     */
    protected $targetType = ObjectStorage::class;

    /**
     * @var int
     */
    protected $priority = 2;

    /**
     * @var Folder
     */
    protected $uploadFolder;

    /**
     * @var PropertyMappingConfigurationInterface
     */
    protected $converterConfiguration = [];

    /**
     * @var Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * Do not inject this property, as EXT:checkfaluploads may not be loaded
     *
     * @var FalUploadService
     */
    protected $falUploadService;

    public function injectSignalSlotDispatcher(Dispatcher $signalSlotDispatcher): void
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * This implementation always returns TRUE for this method.
     *
     * @param mixed  $source     the source data
     * @param string $targetType the type to convert to.
     * @return bool true if this TypeConverter can convert from $source to $targetType, FALSE otherwise.
     */
    public function canConvertFrom($source, string $targetType): bool
    {
        // check if $source consists of uploaded files
        foreach ($source as $uploadedFile) {
            if (!isset(
                $uploadedFile['error'],
                $uploadedFile['name'],
                $uploadedFile['size'],
                $uploadedFile['tmp_name'],
                $uploadedFile['type']
            )) {
                return false;
            }
        }

        return true;
    }

    public function convertFrom(
        $source,
        string $targetType,
        array $convertedChildProperties = [],
        PropertyMappingConfigurationInterface $configuration = null
    ) {
        $this->initialize($configuration);
        $originalSource = $source;
        foreach ($originalSource as $key => $uploadedFile) {
            $alreadyPersistedImage = $this->getAlreadyPersistedFileReferenceByPosition(
                $this->getAlreadyPersistedImages(),
                $key
            );

            // If no file was uploaded use the already persisted one
            if (!$this->isValidUploadFile($uploadedFile)) {
                if (isset($uploadedFile['delete']) && $uploadedFile['delete'] === '1') {
                    $this->deleteFile($alreadyPersistedImage);
                    unset($source[$key]);
                } elseif ($alreadyPersistedImage instanceof FileReference) {
                    $source[$key] = $alreadyPersistedImage;
                } else {
                    unset($source[$key]);
                }
                continue;
            }
            // Check if uploaded file returns an error
            if (!$uploadedFile['error'] === 0) {
                return new Error(
                    LocalizationUtility::translate('error.upload', 'events2') . $uploadedFile['error'],
                    1396957314
                );
            }

            // Check if file extension is allowed
            $fileParts = GeneralUtility::split_fileref($uploadedFile['name']);
            if (!GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileParts['fileext'])) {
                return new Error(
                    LocalizationUtility::translate(
                        'error.fileExtension',
                        'events2',
                        [
                            $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
                        ]
                    ),
                    1402981282
                );
            }

            if (
                ExtensionManagementUtility::isLoaded('checkfaluploads')
                && $error = $this->getFalUploadService()->checkFile($uploadedFile)
            ) {
                return $error;
            }

            $this->emitPostCheckFileReference($source, $key, $alreadyPersistedImage, $uploadedFile);
        }

        // Upload file and add it to ObjectStorage
        $references = GeneralUtility::makeInstance(ObjectStorage::class);
        foreach ($source as $uploadedFile) {
            if ($uploadedFile instanceof FileReference) {
                $references->attach($uploadedFile);
            } else {
                $references->attach($this->getExtbaseFileReference($uploadedFile));
            }
        }

        return $references;
    }

    protected function initialize(?PropertyMappingConfigurationInterface $configuration)
    {
        if ($configuration === null) {
            throw new \Exception(
                'Missing PropertyMapper configuration in UploadMultipleFilesConverter',
                1604051720
            );
        }

        $this->converterConfiguration = $configuration;

        $this->setUploadFolder();
    }

    protected function getAlreadyPersistedImages(): ObjectStorage
    {
        $alreadyPersistedImages = $this->converterConfiguration->getConfigurationValue(
            self::class,
            'IMAGES'
        );

        return $alreadyPersistedImages instanceof ObjectStorage ? $alreadyPersistedImages : new ObjectStorage();
    }

    protected function getAlreadyPersistedFileReferenceByPosition(
        ObjectStorage $alreadyPersistedFileReferences,
        int $position
    ): ?FileReference {
        return $alreadyPersistedFileReferences->toArray()[$position] ?? null;
    }

    protected function getTypoScriptPluginSettings(): array
    {
        $settings = $this->converterConfiguration->getConfigurationValue(
            self::class,
            'settings'
        );

        return $settings ?? [];
    }

    protected function setUploadFolder()
    {
        $combinedUploadFolderIdentifier = $this->getTypoScriptPluginSettings()['new']['uploadFolder'] ?? '';
        if ($combinedUploadFolderIdentifier === '') {
            throw new \Exception(
                'You have forgotten to set an Upload Folder in TypoScript for clubdirectory',
                1603808777
            );
        }

        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        try {
            $uploadFolder = $resourceFactory->getObjectFromCombinedIdentifier($combinedUploadFolderIdentifier);
        } catch (ResourceDoesNotExistException $exception) {
            [$storageUid] = GeneralUtility::trimExplode(':', $combinedUploadFolderIdentifier);
            $resourceStorage = $resourceFactory->getStorageObject((int)$storageUid);
            $uploadFolder = $resourceStorage->createFolder($combinedUploadFolderIdentifier);
        }

        $this->uploadFolder = $uploadFolder;
    }

    /**
     * Check, if we have a valid uploaded file
     * Error = 4: No file uploaded
     *
     * @param array $uploadedFile
     * @return bool
     */
    protected function isValidUploadFile(array $uploadedFile): bool
    {
        if ($uploadedFile['error'] === 4) {
            return false;
        }

        return isset(
            $uploadedFile['error'],
            $uploadedFile['name'],
            $uploadedFile['size'],
            $uploadedFile['tmp_name'],
            $uploadedFile['type']
        );
    }

    /**
     * If file is in our own upload folder we can delete it from filesystem and sys_file table.
     *
     * @param FileReference|null $fileReference
     */
    protected function deleteFile(?FileReference $fileReference)
    {
        if ($fileReference !== null) {
            $fileReference = $fileReference->getOriginalResource();

            if ($fileReference->getStorage()->isWithinFolder($this->uploadFolder, $fileReference)) {
                try {
                    $fileReference->getOriginalFile()->delete();
                } catch (\Exception $exception) {
                    // Do nothing. File already deleted or not found
                }
            }
        }
    }

    /**
     * upload file and get a file reference object.
     *
     * @param array $source
     * @return FileReference
     */
    protected function getExtbaseFileReference(array $source): FileReference
    {
        $extbaseFileReference = GeneralUtility::makeInstance(FileReference::class);
        $extbaseFileReference->setOriginalResource($this->getCoreFileReference($source));

        return $extbaseFileReference;
    }

    /**
     * Upload file and get a file reference object.
     *
     * @param array $source
     * @return \TYPO3\CMS\Core\Resource\FileReference
     */
    protected function getCoreFileReference(array $source): \TYPO3\CMS\Core\Resource\FileReference
    {
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $uploadedFile = $this->uploadFolder->addUploadedFile($source, DuplicationBehavior::RENAME);

        // create Core FileReference
        return $resourceFactory->createFileReferenceObject(
            [
                'uid_local' => $uploadedFile->getUid(),
                'uid_foreign' => uniqid('NEW_'),
                'uid' => uniqid('NEW_'),
            ]
        );
    }

    protected function emitPostCheckFileReference(
        array $source,
        int $key,
        ?FileReference $alreadyPersistedImage,
        array $uploadedFile
    ): void {
        $this->signalSlotDispatcher->dispatch(
            self::class,
            'postCheckFileReference',
            [$source, $key, $alreadyPersistedImage, $uploadedFile]
        );
    }

    protected function getFalUploadService(): FalUploadService
    {
        if ($this->falUploadService === null) {
            $this->falUploadService = GeneralUtility::makeInstance(FalUploadService::class);
        }
        return $this->falUploadService;
    }
}
