<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Command;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Service\DayRelationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/*
 * The naming is a little bit miss-understandable. It comes from the early days of development where I had many
 * problems with all these day records.
 * Today this class deletes ALL day records and creates them from scratch.
 */
class RebuildCommand extends Command
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
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var DatabaseService
     */
    protected $databaseService;

    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * Will be called by DI, so please don't add extbase classes with inject methods here.
     *
     * @param ObjectManagerInterface $objectManager
     * @param DatabaseService $databaseService
     * @param CacheManager $cacheManager
     * @param string|null $name
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        DatabaseService $databaseService,
        CacheManager $cacheManager,
        string $name = null
    ) {
        parent::__construct($name);

        $this->objectManager = $objectManager;
        $this->databaseService = $databaseService;
        $this->cacheManager = $cacheManager;
    }

    protected function configure(): void
    {
        $this->setDescription(
            'Executing this command will delete all day records found in day table. ' .
            'Afterwards, it searches for each current and future event and re-creates all day records again. ' .
            'If you have any problems with created day records, this command is the first place to start. ' .
            'Please do not start this command by a CronJob each day, as for the time it runs, there ' .
            'may no events visible in frontend. Please use our Scheduler Task instead.'
        );
    }

    /**
     * Delete and re-create all day records of events2
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
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

    protected function truncateDayTable(): void
    {
        $this->databaseService->truncateTable('tx_events2_domain_model_day', true);
        $this->output->writeln('I have truncated the day table');
    }

    protected function reGenerateDayRelations(): void
    {
        $dayCounter = 0;
        $dayRelations = $this->objectManager->get(DayRelationService::class);

        $persistenceManager = $this->objectManager->get(PersistenceManagerInterface::class);

        // With each changing PID, pageTSConfigCache will grow by roundabout 200KB, which may exceed memory_limit
        $runtimeCache = $this->cacheManager->getCache('runtime');

        $this->output->writeln('Process each event record');

        $statement = $this->databaseService
            ->getQueryBuilderForAllEvents()
            ->select('uid', 'pid')
            ->execute();

        while ($eventRecord = $statement->fetch(\PDO::FETCH_ASSOC)) {
            try {
                $event = $dayRelations->createDayRelations((int)$eventRecord['uid']);
                if ($event instanceof Event) {
                    $this->output->writeln(sprintf(
                        'Process event UID: %09d, PID: %05d, created: %04d day records, RAM: %d',
                        $event->getUid(),
                        $event->getPid(),
                        $event->getDays()->count(),
                        memory_get_usage()
                    ));
                    $dayCounter += $event->getDays()->count();
                }
            } catch (\Exception $e) {
                $this->output->writeln(sprintf(
                    'ERROR event UID: %09d, PID: %05d',
                    $eventRecord['uid'],
                    $eventRecord['pid']
                ));
            }

            // clean up persistence manager to reduce memory usage
            // it also clears persistence session
            $persistenceManager->clearState();
            $runtimeCache->flush();
            gc_collect_cycles();
        }

        $this->output->writeln(sprintf(
            'We have recreated the day records for %d event records and %d day records in total',
            $this->getAmountOfEventRecordsToProcess(),
            $dayCounter
        ));
    }

    protected function getAmountOfEventRecordsToProcess(): int
    {
        $queryBuilder = $this->databaseService->getQueryBuilderForAllEvents();
        return (int)$queryBuilder
            ->count('*')
            ->execute()
            ->fetchColumn();
    }
}
