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
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use JWeiland\Events2\Service\DayRelationService;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\BackendInterface;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RepairCommandController extends CommandController
{
    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @var \JWeiland\Events2\Utility\DateTimeUtility
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
     * initializes this object.
     */
    public function initializeObject()
    {
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];
    }

    /**
     * Repair events.
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
        $this->databaseConnection->exec_TRUNCATEquery('tx_events2_domain_model_day');
        $this->outputLine('I have truncated the day table' . PHP_EOL);
    }

    /**
     * After solving bugs in DayGenerator it would be good to recreate all days for events
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function reGenerateDayRelations()
    {
        $eventCounter = 0;
        $dayCounter = 0;

        /** @var DayRelationService $dayRelations */
        $dayRelations = $this->objectManager->get('JWeiland\\Events2\\Service\\DayRelationService');

        $this->echoValue('Process each event record' . PHP_EOL);

        // select only current and future events
        // do not select hidden records as eventRepository->findByIdentifier will not find them
        $rows = $this->databaseConnection->exec_SELECTgetRows(
            'uid,pid',
            'tx_events2_domain_model_event',
            'hidden=0 AND deleted=0 AND (
              (event_type = \'single\' AND event_begin > UNIX_TIMESTAMP())
              OR (event_type = \'duration\' AND (event_end = 0 OR event_end > UNIX_TIMESTAMP()))
              OR (event_type = \'recurring\' AND (recurring_end = 0 OR recurring_end > UNIX_TIMESTAMP()))
            )'
        );

        if (!empty($rows)) {
            /** @var PersistenceManager $persistenceManager */
            $persistenceManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager');

            // with each changing PID pageTSConfigCache will grow by roundabout 200KB
            // we need a possibility to reset this level 1 cache
            /** @var BackendInterface $extbaseDbBackend */
            $extbaseDbBackend = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\BackendInterface');
            $reflectedExtbaseDbBackend = new \ReflectionClass($extbaseDbBackend);
            $reflectedPageTSConfigCache = $reflectedExtbaseDbBackend->getProperty('pageTSConfigCache');
            $reflectedPageTSConfigCache->setAccessible(true);

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
                // reset pageTsConfigCache with help of reflections
                $reflectedPageTSConfigCache->setValue($extbaseDbBackend, array());
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
     * echo "whatEver"
     *
     * @param string $value
     * @param boolean $reset
     * @return void
     */
    protected function echoValue($value = '.', $reset = false)
    {
        if ($reset) $this->rowCounter = 0;
        if ($this->rowCounter < 40) {
            echo $value;
            $this->rowCounter++;
        } else {
            echo PHP_EOL . $value;
            $this->rowCounter = 1;
        }
    }
}

