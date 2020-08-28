<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Property\TypeConverter;

use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;
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
     * @var PropertyMappingConfigurationInterface
     */
    protected $converterConfiguration = [];

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
            if (
                !isset($uploadedFile['error']) ||
                !isset($uploadedFile['name']) ||
                !isset($uploadedFile['size']) ||
                !isset($uploadedFile['tmp_name']) ||
                !isset($uploadedFile['type'])
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Actually convert from $source to $targetType, taking into account the fully
     * built $convertedChildProperties and $configuration.
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface|null $configuration
     * @return mixed|Error the target type, or an error object if a user-error occurred
     */
    public function convertFrom(
        $source,
        string $targetType,
        array $convertedChildProperties = [],
        PropertyMappingConfigurationInterface $configuration = null
    ) {
        $this->converterConfiguration = $configuration;
        $alreadyPersistedImages = $this->converterConfiguration->getConfigurationValue(
            self::class,
            'IMAGES'
        );
        $originalSource = $source;
        foreach ($originalSource as $key => $uploadedFile) {
            // check if $source contains an uploaded file. 4 = no file uploaded
            if (
                !isset($uploadedFile['error']) ||
                !isset($uploadedFile['name']) ||
                !isset($uploadedFile['size']) ||
                !isset($uploadedFile['tmp_name']) ||
                !isset($uploadedFile['type']) ||
                $uploadedFile['error'] === 4
            ) {
                if ($alreadyPersistedImages[$key] !== null) {
                    $source[$key] = $alreadyPersistedImages[$key];
                } else {
                    unset($source[$key]);
                }
                continue;
            }
            // check if uploaded file returns an error
            if (!$uploadedFile['error'] === 0) {
                return new Error(
                    LocalizationUtility::translate('error.upload', 'events2') . $uploadedFile['error'],
                    1396957314
                );
            }
            // check if file extension is allowed
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
            // OK...we have a valid file and the user has the rights. It's time to check, if an old file can be deleted
            if ($alreadyPersistedImages[$key] instanceof FileReference) {
                $oldFile = $alreadyPersistedImages[$key];
                $oldFile->getOriginalResource()->getOriginalFile()->delete();
            }
        }

        // I will do two foreach here. First: everything must be OK, before files will be uploaded

        // upload file and add it to ObjectStorage
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
        $settings = $this->converterConfiguration->getConfigurationValue(
            self::class,
            'settings'
        ) ?? [];

        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $uploadFolderIdentifier = $settings['new']['uploadFolder'] ?? '';

        try {
            $uploadFolder = $resourceFactory->getFolderObjectFromCombinedIdentifier($uploadFolderIdentifier);
        } catch (FolderDoesNotExistException $e) {
            [$storageUid, $identifier] = GeneralUtility::trimExplode(':', $uploadFolderIdentifier);
            try {
                $storage = $resourceFactory->getStorageObject($storageUid);
            } catch (\InvalidArgumentException $e) {
                $storage = $resourceFactory->getDefaultStorage();
                $identifier = $uploadFolderIdentifier;
            }
            $uploadFolder = $storage->createFolder($identifier);
        }

        $uploadedFile = $uploadFolder->addUploadedFile($source, DuplicationBehavior::RENAME);

        // create Core FileReference
        return $resourceFactory->createFileReferenceObject(
            [
                'uid_local' => $uploadedFile->getUid(),
                'uid_foreign' => uniqid('NEW_'),
                'uid' => uniqid('NEW_'),
            ]
        );
    }
}
