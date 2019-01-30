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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
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
     * Full path from document root. F.e. /fileadmin/events_import/Import.xml
     *
     * @var string
     */
    public $path = '';

    /**
     * @var int
     */
    public $storagePid = 0;

    /**
     * This is the main method that is called when a task is executed
     * Note that there is no error handling, errors and failures are expected
     * to be handled and logged by the client implementations.
     * Should return TRUE on successful execution, FALSE on error.
     *
     * @return bool Returns TRUE on successful execution, FALSE on error
     * @throws \Exception
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
                    $indexer = GeneralUtility::makeInstance(Indexer::class, $file->getStorage());
                    $indexer->updateIndexEntry($file);
                }
            } else {
                $this->addMessage('The defined file is not a valid file. Maybe you have defined a folder. Please re-check file path', FlashMessage::ERROR);
                return false;
            }
        } catch (\Exception $e) {
            $this->addMessage('Currently no file for import found.', FlashMessage::INFO);
            return true;
        }

        try {
            if ($this->importFile($file)) {
                $file->delete();
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Import file
     *
     * @param File $file
     * @return bool
     * @throws \Exception
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
        /** @var ImporterInterface $importer */
        $importer = GeneralUtility::makeInstance($className, $file);
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
     * @return void
     * @throws \Exception
     */
    public function addMessage($message, $severity = FlashMessage::OK)
    {
        /** @var FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, '', $severity);
        /** @var $flashMessageService FlashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        /** @var $defaultFlashMessageQueue FlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }
}
