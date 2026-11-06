<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Command;

use Doctrine\DBAL\Exception;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Service\DayRelationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

#[AsCommand(
    name: 'events2:rebuild',
    description: 'Re-creates the day records of all current and future events. '
        . 'Truncates the day table first, unless option --no-truncate is set.',
)]
class RebuildCommand extends Command
{
    /**
     * Will be called by DI, so please don't add extbase classes with inject methods here.
     */
    public function __construct(
        protected readonly DatabaseService $databaseService,
        protected readonly DayRelationService $dayRelationService,
        #[Autowire(service: 'cache.runtime')]
        protected readonly FrontendInterface $runtimeCache,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(
            'Executing this command will TRUNCATE (delete) all records from day table. '
            . 'Afterwards, it searches for each current and future event and re-creates all day records again. '
            . 'If you have any problems with created day records, this command is the first place to start. '
            . 'Please be careful running this command as a CronJob each day, as for the time it runs, there '
            . 'may no events visible in frontend. For recurring executions (CronJob, Scheduler) please use '
            . 'option --no-truncate. It keeps the day table and re-creates the day records event by event.',
        );
        $this->addOption(
            'no-truncate',
            null,
            InputOption::VALUE_NONE,
            'Do not truncate the day table. The day records will be re-created event by event, '
            . 'so the events stay visible in frontend while the command is running.',
        );
    }

    /**
     * Delete and re-create all day-records of events2
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('no-truncate')) {
            $io->title('Recreating Day Records: Rebuilding day records event by event');
        } else {
            $io->title('Recreating Day Records: Truncating and Rebuilding Day-Table');
            $this->truncateDayTable($io);
        }

        $this->reGenerateDayRelations($io);

        return Command::SUCCESS;
    }

    protected function truncateDayTable(SymfonyStyle $io): void
    {
        $io->section('Truncating Day Table');
        $this->databaseService->truncateTable('tx_events2_domain_model_day', true);
        $io->info('Day table truncated');
    }

    protected function reGenerateDayRelations(SymfonyStyle $io): void
    {
        $this->setProgressBarFormatDefinitions();

        $io->section('Creating day records');

        $progressBar = $io->createProgressBar($this->getAmountOfEventRecordsToProcess());
        $progressBar->start();

        $queryResult = $this->databaseService
            ->getQueryBuilderForAllEvents()
            ->select('uid', 'pid')
            ->executeQuery();

        while ($simpleEventRecord = $queryResult->fetchAssociative()) {
            $this->dayRelationService->createDayRelations((int)$simpleEventRecord['uid']);

            // The runtime cache grows with each processed event (f.e. page TSconfig of each PID)
            $this->runtimeCache->flush();

            $progressBar->setMessage(sprintf(
                'Process event UID: %09d, PID: %05d',
                (int)$simpleEventRecord['uid'],
                (int)$simpleEventRecord['pid'],
            ));

            $progressBar->advance();
        }

        $progressBar->finish();

        $io->info('Day records created');
    }

    /**
     * Add message to the ProgressBar. Formats copied from ProgressBar class.
     */
    protected function setProgressBarFormatDefinitions(): void
    {
        ProgressBar::setFormatDefinition(ProgressBar::FORMAT_NORMAL, ' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        ProgressBar::setFormatDefinition(ProgressBar::FORMAT_VERBOSE, ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s% -- %message%');
        ProgressBar::setFormatDefinition(ProgressBar::FORMAT_VERY_VERBOSE, ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% -- %message%');
        ProgressBar::setFormatDefinition(ProgressBar::FORMAT_DEBUG, ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% -- %message%');
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
}
