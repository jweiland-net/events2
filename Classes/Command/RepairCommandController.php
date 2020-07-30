<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Command;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Service\DayRelationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/*
 * The naming is a little bit miss-understandable. It comes from the early days of development where I had many
 * problems with all these day records.
 * Today this class deletes ALL day records and creates them from scratch.
 */
class RepairCommandController extends Command
{
    /**
     * Needed to wrap activity bar:
     * ...........F.......
     * ....N....S.........
     *
     * @var int
     */
    protected $rowCounter = 0;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * Defines the allowed options for this command
     */
    protected function configure()
    {
        $this->setDescription('Delete and re-create all day records of events2');
    }

    /**
     * Delete and re-create all day records of events2
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $output->writeln('Start repairing day records of events');
        $output->writeln('');
        $this->truncateDayTable();
        $output->writeln('');
        $this->reGenerateDayRelations();
        $output->writeln('');

        return 0;
    }

    /**
     * Truncate day table. We will build them up again within the next steps
     *
     * @return void
     */
    protected function truncateDayTable()
    {
        $databaseService = GeneralUtility::makeInstance(DatabaseService::class);
        $databaseService->truncateTable('tx_events2_domain_model_day', true);

        $this->output->writeln('I have truncated the day table' . PHP_EOL);
    }

    /**
     * After solving bugs in DayGenerator it would be good to recreate all days for events
     *
     * @return void
     */
    protected function reGenerateDayRelations()
    {
        $eventCounter = 0;
        $dayCounter = 0;
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $dayRelations = $objectManager->get(DayRelationService::class);

        $this->echoValue('Process each event record' . PHP_EOL);

        $databaseService = GeneralUtility::makeInstance(DatabaseService::class);
        $rows = $databaseService->getCurrentAndFutureEvents();
        if (!empty($rows)) {
            $persistenceManager = $objectManager->get(PersistenceManager::class);

            // with each changing PID pageTSConfigCache will grow by roundabout 200KB
            // which may exceed memory_limit
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

        $this->output->writeln(sprintf(
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
