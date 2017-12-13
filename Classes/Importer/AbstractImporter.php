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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class AbstractImporter implements ImporterInterface
{
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
     * Initialize this object
     *
     * @return void
     */
    public function initialize()
    {
        $this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $this->organizerRepository = $this->objectManager->get('JWeiland\\Events2\\Domain\\Repository\\OrganizerRepository');
        $this->locationRepository = $this->objectManager->get('JWeiland\\Events2\\Domain\\Repository\\LocationRepository');
        $this->categoryRepository = $this->objectManager->get('JWeiland\\Events2\\Domain\\Repository\\CategoryRepository');
        $this->dateTimeUtility = $this->objectManager->get('JWeiland\\Events2\\Utility\\DateTimeUtility');
        $this->registry = $this->objectManager->get('TYPO3\\CMS\\Core\\Registry');
        $this->today = new \DateTime('now');
    }

    /**
     * Check, if File is valid for this importer
     *
     * @param File $file
     *
     * @return bool
     */
    public function isValid(File $file) {
        $isValid = true;

        $modificationTime = $this->registry->get('events2', 'import-task-file-' . $file->getProperty('uid'));
        if ($modificationTime && $file->getModificationTime() <= $modificationTime) {
            $isValid = false;
            $this->addMessage('Modification time of file has not changed. Stop importing');
        }

        if (!in_array($file->getMimeType(), $this->allowedMimeType)) {
            $isValid = false;
            $this->addMessage('MimeType of file is not allowed');
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
     */
    protected function addMessage($message, $severity = FlashMessage::OK) {
        /** @var FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $message, '', $severity);
        /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
        $flashMessageService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
        /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
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
            $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
            /** @var PersistenceManagerInterface $persistenceManager */
            $this->persistenceManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager');
        }
        return $this->persistenceManager;
    }

    /**
     * Get TYPO3s Database Connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
