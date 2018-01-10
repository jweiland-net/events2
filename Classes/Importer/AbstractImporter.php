<?php

namespace JWeiland\Events2\Importer;

/*
 * This file is part of the events2 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use JWeiland\Events2\Domain\Repository\CategoryRepository;
use JWeiland\Events2\Domain\Repository\LocationRepository;
use JWeiland\Events2\Domain\Repository\OrganizerRepository;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class AbstractImporter implements ImporterInterface
{
    /**
     * @var FileInterface
     */
    protected $file;

    /**
     * @var array
     */
    protected $allowedMimeType = [];

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var OrganizerRepository
     */
    protected $organizerRepository;

    /**
     * @var LocationRepository
     */
    protected $locationRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var DateTimeUtility
     */
    protected $dateTimeUtility;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var \DateTime
     */
    protected $today;

    /**
     * XmlImporter constructor.
     *
     * @param FileInterface $file
     */
    public function __construct(FileInterface $file)
    {
        $this->file = $file;
    }

    /**
     * Initialize this object
     *
     * @return void
     */
    public function initialize()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->organizerRepository = $this->objectManager->get(OrganizerRepository::class);
        $this->locationRepository = $this->objectManager->get(LocationRepository::class);
        $this->categoryRepository = $this->objectManager->get(CategoryRepository::class);
        $this->dateTimeUtility = $this->objectManager->get(DateTimeUtility::class);
        $this->registry = $this->objectManager->get(Registry::class);
        $this->today = new \DateTime('now');
    }

    /**
     * Check, if File is valid for this importer
     *
     * @param FileInterface $file
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function isValid(FileInterface $file)
    {
        $isValid = true;

        $modificationTime = $this->registry->get('events2', 'import-task-file-' . $file->getProperty('uid'));
        if ($modificationTime && $file->getModificationTime() <= $modificationTime) {
            $isValid = false;
            $this->addMessage('Modification time of file has not changed. Stop importing', FlashMessage::ERROR);
        }

        if (!in_array($file->getMimeType(), $this->allowedMimeType)) {
            $isValid = false;
            $this->addMessage('MimeType of file is not allowed', FlashMessage::ERROR);
        }

        $this->registry->set('events2', 'import-task-file-' . $file->getProperty('uid'), $file->getModificationTime());

        return $isValid;
    }

    /**
     * This method is used to add a message to the internal queue
     *
     * @param string $message The message itself
     * @param int $severity Message level (according to \TYPO3\CMS\Core\Messaging\FlashMessage class constants)
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function addMessage($message, $severity = FlashMessage::OK)
    {
        static $firstMessage = true;

        // log messages into file
        $filename = 'Messages.txt';

        try {
            /** @var Folder $folder */
            $folder = $this->file->getParentFolder();
            if (!$folder->hasFile($filename)) {
                $file = $folder->createFile($filename);
            } else {
                $file = ResourceFactory::getInstance()->retrieveFileOrFolderObject($folder->getCombinedIdentifier() . 'Messages.txt');
            }

            if ($firstMessage) {
                $content = $message;
                $firstMessage = false;
            } else {
                $content = $file->getContents() . LF . $message;
            }
            $file->setContents($content);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $severity = FlashMessage::ERROR;
        }

        // show messages in TYPO3 BE when started manually
        /** @var FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, '', $severity);
        /** @var FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        /** @var FlashMessageQueue $defaultFlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * Get persistence manager
     *
     * @return PersistenceManagerInterface
     */
    protected function getPersistenceManager()
    {
        if ($this->persistenceManager === null) {
            /** @var ObjectManager $objectManager */
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            /** @var PersistenceManagerInterface $persistenceManager */
            $this->persistenceManager = $objectManager->get(PersistenceManager::class);
        }
        return $this->persistenceManager;
    }

    /**
     * Get TYPO3s Database Connection
     *
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
