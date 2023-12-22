<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Task;

use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Service\DayRelationService;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Scheduler\ProgressProviderInterface;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/*
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
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var DatabaseService
     */
    protected $databaseService;

    /**
     * @var Registry
     */
    protected $registry;

    public function __construct(
        ObjectManagerInterface $objectManager,
        CacheManager $cacheManager,
        DatabaseService $databaseService,
        Registry $registry
    ) {
        parent::__construct();

        $this->objectManager = $objectManager;
        $this->cacheManager = $cacheManager;
        $this->databaseService = $databaseService;
        $this->registry = $registry;
    }

    public function execute(): bool
    {
        // Do not move these lines of code into constructor.
        // It will break serialization. Error: Serialization of 'Closure' is not allowed
        $dayRelationService = $this->objectManager->get(DayRelationService::class);
        $persistenceManager = $this->objectManager->get(PersistenceManagerInterface::class);

        $this->registry->removeAllByNamespace('events2TaskCreateUpdate');
        $amountOfEventRecordsToProcess = $this->getAmountOfEventRecordsToProcess();

        // Each time when the PID changes in loop, TYPO3 will build up and cache pageTSConfig which needs
        // roundabout 200KB each PID. This may exceed PHP:memory_limit on TYPO3 instances with a lot of different
        // storage folders for event records.
        $runtimeCache = $this->cacheManager->getCache('runtime');

        // We order event records by PID for better pageTSConfig cache usage
        $statement = $this->databaseService
            ->getQueryBuilderForAllEvents()
            ->select('uid', 'pid')
            ->orderBy('pid', 'ASC')
            ->execute();

        $counter = 0;
        $currentPid = 0;
        while ($eventRecord = $statement->fetch(\PDO::FETCH_ASSOC)) {
            // Flush cache, if PID changes. See comments above
            if ($currentPid !== $eventRecord['pid']) {
                $runtimeCache->flush();
            }

            $counter++;

            $this->registry->set('events2TaskCreateUpdate', 'info', [
                'uid' => $eventRecord['uid'],
                'pid' => $eventRecord['pid']
            ]);

            try {
                // Remove all day records and create new ones
                $dayRelationService->createDayRelations((int)$eventRecord['uid']);
            } catch (\Exception $e) {
                $this->addMessage(sprintf(
                    'Event UID: %d, PID: %d, Error: %s, File: %s, Line: %d',
                    $eventRecord['uid'],
                    $eventRecord['pid'],
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ), AbstractMessage::ERROR);
                return false;
            }

            $this->registry->set('events2TaskCreateUpdate', 'progress', [
                'records' => $amountOfEventRecordsToProcess,
                'counter' => $counter
            ]);

            // Flush cache of Extbase PersistenceManager. It also flushes the Extbase Session cache.
            $persistenceManager->clearState();
        }

        $this->registry->remove('events2TaskCreateUpdate', 'info');

        $this->cleanUpICalDirectory();

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
    public function getAdditionalInformation(): string
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
        }
        return 0.0;
    }

    protected function cleanUpICalDirectory(): void
    {
        $iCalDirectory = Environment::getPublicPath() . '/' . 'typo3temp/tx_events2/iCal/';
        if (is_dir($iCalDirectory)) {
            foreach (new \DirectoryIterator($iCalDirectory) as $fileInfo) {
                if ($fileInfo->isDot()) {
                    continue;
                }

                if ($fileInfo->isFile() && time() - $fileInfo->getCTime() >= (24 * 60 * 60)) {
                    unlink($fileInfo->getRealPath());
                }
            }
        }
    }

    protected function getAmountOfEventRecordsToProcess(): int
    {
        $queryBuilder = $this->databaseService->getQueryBuilderForAllEvents();
        return (int)$queryBuilder
            ->count('*')
            ->execute()
            ->fetchColumn();
    }

    /**
     * This method is used to add a message to the internal queue
     *
     * @param string $message The message itself
     * @param int $severity Message level (according to \TYPO3\CMS\Core\Messaging\FlashMessage class constants)
     * @throws \Exception
     */
    public function addMessage(string $message, int $severity = AbstractMessage::OK): void
    {
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, '', $severity);
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }

    public function __sleep()
    {
        return array_keys(get_object_vars($this));
    }
}
