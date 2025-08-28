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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RebuildCommand extends Command
{
    /**
     * Will be called by DI, so please don't add extbase classes with inject methods here.
     */
    public function __construct(
        protected readonly DatabaseService $databaseService,
        protected readonly DayRelationService $dayRelationService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription(
            'Executing this command will TRUNCATE (delete) all records from day table. '
            . 'Afterwards, it searches for each current and future event and re-creates all day records again. '
            . 'If you have any problems with created day records, this command is the first place to start. '
            . 'Please be careful running this command as a CronJob each day, as for the time it runs, there '
            . 'may no events visible in frontend. We prefer using the Scheduler Task instead.',
        );
    }

    /**
     * Delete and re-create all day-records of events2
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Recreating Day Records: Truncating and Rebuilding Day-Table');

        $this->truncateDayTable($io);
        $this->reGenerateDayRelations($io);

        return 0;
    }

    protected function truncateDayTable(SymfonyStyle $io): void
    {
        $io->section('Truncating Day Table');
        $this->databaseService->truncateTable('tx_events2_domain_model_day', true);
        $io->info('Day table truncated');
    }

    protected function reGenerateDayRelations(SymfonyStyle $io): void
    {
        // Add Message to the ProgressBar. Formats copied from ProgressBar class.
        ProgressBar::setFormatDefinition(ProgressBar::FORMAT_NORMAL, ' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        ProgressBar::setFormatDefinition(ProgressBar::FORMAT_VERBOSE, ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s% -- %message%');
        ProgressBar::setFormatDefinition(ProgressBar::FORMAT_VERY_VERBOSE, ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% -- %message%');
        ProgressBar::setFormatDefinition(ProgressBar::FORMAT_DEBUG, ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% -- %message%');

        $io->section('Creating day records');

        $progressBar = $io->createProgressBar($this->getAmountOfEventRecordsToProcess());
        $progressBar->start();

        $queryResult = $this->databaseService
            ->getQueryBuilderForAllEvents()
            ->select('uid', 'pid')
            ->executeQuery();

        while ($simpleEventRecord = $queryResult->fetchAssociative()) {
            $this->dayRelationService->createDayRelations((int)$simpleEventRecord['uid']);

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
