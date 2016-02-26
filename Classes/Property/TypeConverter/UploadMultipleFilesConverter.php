<?php

namespace JWeiland\Events2\Property\TypeConverter;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014 Stefan Froemken <froemken@gmail.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Property\Exception;

/**
 * Converter for uploads.
 */
class UploadMultipleFilesConverter extends AbstractTypeConverter
{
    /**
     * @var array<string>
     */
    protected $sourceTypes = array('array');

    /**
     * @var string
     */
    protected $targetType = 'TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage';

    /**
     * @var int
     */
    protected $priority = 2;

    /**
     * This implementation always returns TRUE for this method.
     *
     * @param mixed  $source     the source data
     * @param string $targetType the type to convert to.
     *
     * @return bool TRUE if this TypeConverter can convert from $source to $targetType, FALSE otherwise.
     *
     * @api
     */
    public function canConvertFrom($source, $targetType)
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
     * @param array                                                             $source
     * @param string                                                            $targetType
     * @param array                                                             $convertedChildProperties
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     *
     * @throws \TYPO3\CMS\Extbase\Property\Exception
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\AbstractFileFolder
     *
     * @api
     */
    public function convertFrom(
        $source,
        $targetType,
        array $convertedChildProperties = array(),
        \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null
    ) {
        $alreadyPersistedImages = $configuration->getConfigurationValue(
            'JWeiland\\Events2\\Property\\TypeConverter\\UploadMultipleFilesConverter',
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
                return new \TYPO3\CMS\Extbase\Error\Error(
                    LocalizationUtility::translate('error.upload', 'events2') . $uploadedFile['error'],
                    1396957314
                );
            }
            // now we have a valid uploaded file. Check if user has rights to upload this file
            if (!isset($uploadedFile['rights']) || empty($uploadedFile['rights'])) {
                return new \TYPO3\CMS\Extbase\Error\Error(
                    LocalizationUtility::translate('error.uploadRights', 'events2'),
                    1397464390
                );
            }
            // check if file extension is allowed
            $fileParts = GeneralUtility::split_fileref($uploadedFile['name']);
            if (!GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileParts['fileext'])) {
                return new \TYPO3\CMS\Extbase\Error\Error(
                    LocalizationUtility::translate(
                        'error.fileExtension',
                        'events2',
                        array(
                            $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
                        )
                    ),
                    1402981282
                );
            }
            // OK...we have a valid file and the user has the rights. It's time to check, if an old file can be deleted
            if ($alreadyPersistedImages[$key] instanceof \TYPO3\CMS\Extbase\Domain\Model\FileReference) {
                /** @var \TYPO3\CMS\Extbase\Domain\Model\FileReference $oldFile */
                $oldFile = $alreadyPersistedImages[$key];
                $oldFile->getOriginalResource()->getOriginalFile()->delete();
            }
        }

        // I will do two foreach here. First: everything must be OK, before files will be uploaded

        // upload file and add it to ObjectStorage
        /** @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage $references */
        $references = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');
        foreach ($source as $uploadedFile) {
            if ($uploadedFile instanceof \TYPO3\CMS\Extbase\Domain\Model\FileReference) {
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
     * @param array  $source
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference
     */
    protected function getExtbaseFileReference($source)
    {
        /** @var $reference \TYPO3\CMS\Extbase\Domain\Model\FileReference */
        $extbaseFileReference = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Domain\\Model\\FileReference');
        $extbaseFileReference->setOriginalResource($this->getCoreFileReference($source));

        return $extbaseFileReference;
    }

    /**
     * upload file and get a file reference object.
     *
     * @param array $source
     *
     * @return \TYPO3\CMS\Core\Resource\FileReference
     */
    protected function getCoreFileReference(array $source)
    {
        // upload file
        $uploadFolder = ResourceFactory::getInstance()->retrieveFileOrFolderObject('uploads/tx_events2/');
        $uploadedFile = $uploadFolder->addUploadedFile($source, 'changeName');
        // create Core FileReference
        return ResourceFactory::getInstance()->createFileReferenceObject(
            array(
                'uid_local' => $uploadedFile->getUid(),
                'uid_foreign' => uniqid('NEW_'),
                'uid' => uniqid('NEW_'),
            )
        );
    }
}
