<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Task;

use Doctrine\DBAL\Exception;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Service\DayRelationService;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Scheduler\ProgressProviderInterface;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * This class loops through all events and re-creates the day records.
 * Instead of the RepairCommand, this class does NOT truncate the whole day table.
 */
class ReGenerateDays extends AbstractTask implements ProgressProviderInterface
{
    public function __construct(
        protected readonly CacheManager $cacheManager,
        protected readonly DatabaseService $databaseService,
        protected readonly Registry $registry
    ) {
        parent::__construct();
    }

    public function execute(): bool
    {
        // Do not move these lines of code into constructor.
        // It will break serialization. Error: Serialization of 'Closure' is not allowed
        $dayRelationService = GeneralUtility::makeInstance(DayRelationService::class);
        $persistenceManager = GeneralUtility::makeInstance(PersistenceManagerInterface::class);

        // with each changing PID pageTSConfigCache will grow by roundabout 200KB
        // which may exceed memory_limit
        $runtimeCache = $this->cacheManager->getCache('runtime');

        $this->registry->removeAllByNamespace('events2TaskCreateUpdate');

        $amountOfEventRecordsToProcess = $this->getAmountOfEventRecordsToProcess();
        $queryResult = $this->databaseService
            ->getQueryBuilderForAllEvents()
            ->select('uid', 'pid')
            ->executeQuery();

        $counter = 0;
        while ($eventRecord = $queryResult->fetchAssociative()) {
            $counter++;
            $this->registry->set('events2TaskCreateUpdate', 'info', [
                'uid' => $eventRecord['uid'],
                'pid' => $eventRecord['pid'],
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
                ), ContextualFeedbackSeverity::ERROR);
                return false;
            }

            $this->registry->set('events2TaskCreateUpdate', 'progress', [
                'records' => $amountOfEventRecordsToProcess,
                'counter' => $counter,
            ]);

            // clean up persistence manager to reduce memory usage
            // it also clears persistence session
            $persistenceManager->clearState();
            $runtimeCache->flush();
            gc_collect_cycles();
        }

        $this->registry->remove('events2TaskCreateUpdate', 'info');

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
    public function getProgress(): float
    {
        $progress = $this->registry->get('events2TaskCreateUpdate', 'progress');
        if ($progress) {
            return (float)(100 / $progress['records'] * $progress['counter']);
        }

        return 0.0;
    }

    protected function getAmountOfEventRecordsToProcess(): int
    {
        try {
            return (int)$this->databaseService->getQueryBuilderForAllEvents()
                ->count('*')
                ->executeQuery()
                ->fetchOne();
        } catch (Exception $e) {
        }

        return 0;
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

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }

    public function __sleep()
    {
        return array_keys(get_object_vars($this));
    }
}
