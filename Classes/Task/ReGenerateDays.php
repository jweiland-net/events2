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
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Service\DayRelationService;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Scheduler\ProgressProviderInterface;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * This class loops through all events and re-creates the day records.
 * Instead of the RepairCommand, this class does NOT truncate the whole day table.
 */
class ReGenerateDays extends AbstractTask implements ProgressProviderInterface
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * constructor of this class.
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->registry = $this->objectManager->get(Registry::class);
        parent::__construct();
    }

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
        $dayRelations = $this->objectManager->get(DayRelationService::class);
        $persistenceManager = $this->objectManager->get(PersistenceManager::class);

        // with each changing PID pageTSConfigCache will grow by roundabout 200KB
        // which may exceed memory_limit
        /** @var $runtimeCache \TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend */
        $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)
            ->getCache('cache_runtime');

        $this->registry->removeAllByNamespace('events2TaskCreateUpdate');

        /** @var DatabaseService $databaseService */
        $databaseService = GeneralUtility::makeInstance(DatabaseService::class);
        $events = $databaseService->getCurrentAndFutureEvents();
        if (!empty($events)) {
            $counter = 0;
            foreach ($events as $event) {
                $counter++;
                $this->registry->set('events2TaskCreateUpdate', 'info', [
                    'uid' => $event['uid'],
                    'pid' => $event['pid']
                ]);

                try {
                    $dayRelations->createDayRelations((int)$event['uid']);
                } catch (\Exception $e) {
                    $this->addMessage(sprintf(
                        'Event UID: %d, PID: %d, Error: %s, File: %s, Line: %d',
                        $event['uid'],
                        $event['pid'],
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine()
                    ), FlashMessage::ERROR);
                    return false;
                }

                // clean up persistence manager to reduce in-memory
                $persistenceManager->clearState();

                $this->registry->set('events2TaskCreateUpdate', 'progress', [
                    'records' => count($events),
                    'counter' => $counter
                ]);

                // clean up persistence manager to reduce memory usage
                // it also clears persistence session
                $persistenceManager->clearState();
                $runtimeCache->flush();
                gc_collect_cycles();
            }
        }

        $this->registry->remove('events2TaskCreateUpdate', 'info');

        // remove old iCAL downloads
        $iCalDirectory = PATH_site . 'typo3temp/tx_events2/iCal/';
        if (is_dir($iCalDirectory)) {
            foreach (new \DirectoryIterator($iCalDirectory) as $fileInfo) {
                if ($fileInfo->isDot()) {
                    continue;
                }
                if ($fileInfo->isFile() && time() - $fileInfo->getCTime() >= 1 * 24 * 60 * 60) {
                    unlink($fileInfo->getRealPath());
                }
            }
        }

        return true;
    }

    /**
     * This method is designed to return some additional information about the task,
     * that may help to set it apart from other tasks from the same class
     * This additional information is used - for example - in the Scheduler's BE module
     * This method should be implemented in most task classes
     *
     * @return string Information to display
     */
    public function getAdditionalInformation()
    {
        $content = '';
        $info = $this->registry->get('events2TaskCreateUpdate', 'info');
        if ($info) {
            $content = sprintf(
                'Current event: uid: %d, pid: %d, memory: %d.',
                $info['uid'],
                $info['pid'],
                memory_get_usage()
            );
        }
        return $content;
    }

    /**
     * Gets the progress of a task.
     *
     * @return float Progress of the task as a two decimal precision float. f.e. 44.87
     */
    public function getProgress()
    {
        $progress = $this->registry->get('events2TaskCreateUpdate', 'progress');
        if ($progress) {
            return 100 / $progress['records'] * $progress['counter'];
        } else {
            return 0.0;
        }
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

    /**
     * Get TYPO3s Connection Pool
     *
     * @return ConnectionPool
     */
    protected function getConnectionPool()
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }

    /**
     * This object will be serialized in tx_scheduler_task.
     * While executing this task, it seems that __construct will not be called again and
     * all properties will be reconstructed by the information in serialized value.
     * These properties will be created again with new() instead of GeneralUtility::makeInstance()
     * which leads to the problem, that object of type SingletonInterface were created twice.
     *
     * @return void
     */
    public function __wakeup()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->registry = $this->objectManager->get(Registry::class);
    }
}
