<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Task;

use JWeiland\Events2\Importer\XmlImporter;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Task to import events by an XML file
 */
class Import extends AbstractTask
{
    /**
     * Full path from document root. F.e. /fileadmin/events_import/Import.xml
     */
    public string $path = '';

    /**
     * It's public and will be set externally by scheduler.
     * Internally it's INT, but the form-value is string.
     */
    public string|int $storagePid = 0;

    /**
     * This is the main method that is called when a task is executed
     * Note that there is no error handling, errors and failures are expected
     * to be handled and logged by the client implementations.
     * Should return TRUE on successful execution, FALSE on error.
     *
     * @return bool Returns TRUE on successful execution, FALSE on error
     * @throws \Exception
     */
    public function execute(): bool
    {
        try {
            $file = $this->getResourceFactory()->retrieveFileOrFolderObject($this->path);
            if ($file instanceof File) {
                if ($file->isMissing()) {
                    $this->addMessage('The defined file seems to be missing. Please check, if file is still at its place', ContextualFeedbackSeverity::ERROR);
                    return false;
                }

                $this->updateFalIndexEntry($file);
            } else {
                $this->addMessage('The defined file is not a valid file. Maybe you have defined a folder. Please re-check file path', ContextualFeedbackSeverity::ERROR);
                return false;
            }
        } catch (\Exception $e) {
            $this->addMessage('Currently no file for import found.', ContextualFeedbackSeverity::INFO);
            return true;
        }

        try {
            if ($this->importFile($file)) {
                $file->delete();
                return true;
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    protected function importFile(File $file): bool
    {
        $importer = $this->getXmlImporter();
        $importer->setStoragePid($this->getStoragePid());
        $importer->setFile($file);
        if (!$importer->checkFile()) {
            return false;
        }

        return $importer->import();
    }

    /**
     * This method is used to add a message to the internal queue
     *
     * @param string $message The message itself
     * @param ContextualFeedbackSeverity $severity Message level (according to ContextualFeedbackSeverity class enum types)
     * @throws \Exception
     */
    public function addMessage(string $message, ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK): void
    {
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, '', $severity);
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * The used file may be overwritten via FTP or SCP in storage.
     * So, before using it, we should update file information in FAL first
     */
    protected function updateFalIndexEntry(File $file): void
    {
        $this->getFalIndexer($file->getStorage())->updateIndexEntry($file);
    }

    protected function getStoragePid(): int
    {
        return (int)$this->storagePid;
    }

    protected function getXmlImporter(): XmlImporter
    {
        return GeneralUtility::makeInstance(XmlImporter::class);
    }

    protected function getResourceFactory(): ResourceFactory
    {
        return GeneralUtility::makeInstance(ResourceFactory::class);
    }

    protected function getFalIndexer(ResourceStorage $resourceStorage): Indexer
    {
        return GeneralUtility::makeInstance(Indexer::class, $resourceStorage);
    }
}
