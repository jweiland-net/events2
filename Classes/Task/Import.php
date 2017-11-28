<?php

namespace JWeiland\Events2\Task;

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
use JWeiland\Events2\Importer\ImporterInterface;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Import extends AbstractTask
{
    /**
     * @var string
     */
    public $path = '';

    /**
     * @var int
     */
    public $storagePid = 0;

    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * constructor of this class.
     */
    public function __construct()
    {
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];

        parent::__construct();
    }

    /**
     * This is the main method that is called when a task is executed
     * Note that there is no error handling, errors and failures are expected
     * to be handled and logged by the client implementations.
     * Should return TRUE on successful execution, FALSE on error.
     *
     * @return bool Returns TRUE on successful execution, FALSE on error
     */
    public function execute()
    {
        try {
            /** @var File $file */
            $file = ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->path);
            if ($file instanceof File) {
                if ($file->isMissing()) {
                    $this->addMessage('The defined file seems to be missing. Please check, if file is still at its place', FlashMessage::ERROR);
                    return false;
                } else {
                    // File can be updated by (S)FTP. So we have to update its properties first.
                    /** @var Indexer $indexer */
                    $indexer = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\Indexer', $file->getStorage());
                    $indexer->updateIndexEntry($file);
                }
            } else {
                $this->addMessage('The defined file is not a valid file. Maybe you have defined a folder. Please re-check file path', FlashMessage::ERROR);
                return false;
            }
        } catch (\Exception $e) {
            $this->addMessage('Please check path. Import file could not be found.', FlashMessage::ERROR);
            return false;
        }

        return $this->importFile($file);
    }

    /**
     * Import file
     *
     * @param File $file
     *
     * @return bool
     */
    protected function importFile(File $file)
    {
        $className = sprintf(
            'JWeiland\\Events2\\Importer\\%sImporter',
            ucfirst(strtolower($file->getExtension()))
        );
        if (!class_exists($className)) {
            $this->addMessage('There is no class to handler files of type: ' . $file->getExtension());
            return false;
        }
        $importer = GeneralUtility::makeInstance($className);
        if (!$importer instanceof ImporterInterface) {
            $this->addMessage('Importer has to implement ImporterInterface');
            return false;
        }
        $importer->initialize();
        if (!$importer->isValid($file)) {
            return false;
        }
        return $importer->import($file, $this);
    }

    /**
     * This method is used to add a message to the internal queue
     *
     * @param string $message The message itself
     * @param int $severity Message level (according to \TYPO3\CMS\Core\Messaging\FlashMessage class constants)
     *
     * @return void
     */
    public function addMessage($message, $severity = FlashMessage::OK) {
        /** @var FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $message, '', $severity);
        /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
        $flashMessageService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
        /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * This object will be saved serialized in database
     * SingletonInterface objects may be different while recreating the objects,
     * that's why I recreate them on my own
     *
     * @return void
     */
    public function __wakeup()
    {
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];
    }
}
