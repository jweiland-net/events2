<?php

namespace JWeiland\Events2\Command;

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
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * The naming is a little bit miss-understandable. It comes from the early days of development where I had many
 * problems with all these day records.
 * Today this class deletes ALL day records and creates them from scratch.
 */
class RepairCommandController extends CommandController
{
    /**
     * @var DateTimeUtility
     */
    protected $dateTimeUtility;

    /**
     * Needed to wrap activity bar:
     * ...........F.......
     * ....N....S.........
     *
     * @var int
     */
    protected $rowCounter = 0;

    /**
     * inject DateTime Utility.
     *
     * @param DateTimeUtility $dateTimeUtility
     */
    public function injectDateTimeUtility(DateTimeUtility $dateTimeUtility)
    {
        $this->dateTimeUtility = $dateTimeUtility;
    }

    /**
     * Repair events.
     *
     * @throws \Exception
     */
    public function eventsCommand()
    {
        $this->outputLine('Start repairing day records of events');

        $this->outputLine('');
        $this->truncateDayTable();
        $this->outputLine('');
        $this->reGenerateDayRelations();
        $this->outputLine('');
    }

    /**
     * Truncate day table. We will build them up again within the next steps
     *
     * @return void
     */
    protected function truncateDayTable()
    {
        /** @var DatabaseService $databaseService */
        $databaseService = GeneralUtility::makeInstance(DatabaseService::class);
        $databaseService->truncateTable('tx_events2_domain_model_day', true);

        $this->outputLine('I have truncated the day table' . PHP_EOL);
    }

    /**
     * After solving bugs in DayGenerator it would be good to recreate all days for events
     *
     * @return void
     * @throws \Exception
     */
    protected function reGenerateDayRelations()
    {
        $eventCounter = 0;
        $dayCounter = 0;
        $dayRelations = $this->objectManager->get(DayRelationService::class);

        $this->echoValue('Process each event record' . PHP_EOL);

        /** @var DatabaseService $databaseService */
        $databaseService = GeneralUtility::makeInstance(DatabaseService::class);
        $rows = $databaseService->getCurrentAndFutureEvents();
        if (!empty($rows)) {
            $persistenceManager = $this->objectManager->get(PersistenceManager::class);

            // with each changing PID pageTSConfigCache will grow by roundabout 200KB
            // which may exceed memory_limit
            /** @var $runtimeCache \TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend */
            $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)
                ->getCache('cache_runtime');

            foreach ($rows as $key => $row) {
                $event = $dayRelations->createDayRelations((int)$row['uid']);
                if ($event instanceof Event) {
                    $this->echoValue(sprintf(
                        'Process event UID: %09d, PID: %05d, created: %04d day records, RAM: %d' . PHP_EOL,
                        $event->getUid(),
                        $event->getPid(),
                        $event->getDays()->count(),
                        memory_get_usage()
                    ));
                    $eventCounter++;
                    $dayCounter += $event->getDays()->count();
                } else {
                    $this->echoValue(sprintf(
                        'ERROR event UID: %09d, PID: %05d' . PHP_EOL,
                        $row['uid'],
                        $row['pid']
                    ));
                }

                // clean up persistence manager to reduce memory usage
                // it also clears persistence session
                $persistenceManager->clearState();
                $runtimeCache->flush();
                gc_collect_cycles();
            }
        }

        $this->outputLine(sprintf(
            'We have recreated the day records for %d event records and %d day records' . PHP_EOL,
            $eventCounter,
            $dayCounter
        ));
    }

    /**
     * Echo $value to CLI
     *
     * @param string $value In most cases you should use only ONE letter
     * @param bool $reset If true, we insert a line break
     * @return void
     */
    protected function echoValue($value = '.', $reset = false)
    {
        if ($reset) {
            $this->rowCounter = 0;
        }
        if ($this->rowCounter < 40) {
            echo $value;
            $this->rowCounter++;
        } else {
            echo PHP_EOL . $value;
            $this->rowCounter = 1;
        }
    }
}
