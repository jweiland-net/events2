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
use Doctrine\DBAL\Result;
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
    description: 'Re-creates the day records of all current and future events event by event. '
        . 'Use option --truncate to delete all day records first.',
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
            'This command searches for each current and future event and re-creates its day records. '
            . 'The events stay visible in frontend while the command is running, so it can be used for '
            . 'recurring executions (CronJob, Scheduler). If you have any problems with created day records, '
            . 'this command is the first place to start. With option --truncate all records of the day table '
            . 'will be deleted (TRUNCATE) first. Please do not use this option for recurring executions, as '
            . 'there may be no events visible in frontend while the command is running. If the day records of '
            . 'an event can not be created, the command continues with the next event, lists all failed events '
            . 'at the end and returns a failure exit code.',
        );
        $this->addOption(
            'truncate',
            null,
            InputOption::VALUE_NONE,
            'Delete (TRUNCATE) all records of the day table before re-creating the day records. '
            . 'Events will not be visible in frontend until their day records have been re-created.',
        );
    }

    /**
     * Delete and re-create all day-records of events2
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('truncate')) {
            $io->title('Recreating Day Records: Truncating and Rebuilding Day-Table');
            $this->truncateDayTable($io);
        } else {
            $io->title('Recreating Day Records: Rebuilding day records event by event');
        }

        $errors = $this->reGenerateDayRelations($io);
        if ($errors !== []) {
            $io->error(sprintf('Day records of %d event(s) could not be created', count($errors)));
            $io->listing($errors);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function truncateDayTable(SymfonyStyle $io): void
    {
        $io->section('Truncating Day Table');
        $this->databaseService->truncateTable('tx_events2_domain_model_day', true);
        $io->info('Day table truncated');
    }

    /**
     * @return string[] Error messages of the events which could not be processed
     */
    protected function reGenerateDayRelations(SymfonyStyle $io): array
    {
        $this->setProgressBarFormatDefinitions();

        $io->section('Creating day records');

        $progressBar = $io->createProgressBar($this->getAmountOfEventRecordsToProcess());
        $progressBar->start();

        $errors = [];
        $queryResult = $this->getEventRecordsToProcess();
        while ($simpleEventRecord = $queryResult->fetchAssociative()) {
            $eventUid = (int)$simpleEventRecord['uid'];
            $eventPid = (int)$simpleEventRecord['pid'];
            $error = $this->createDayRelationsOfEvent($eventUid, $eventPid);
            if ($error !== '') {
                $errors[] = $error;
            }

            $progressBar->setMessage(sprintf('Process event UID: %09d, PID: %05d', $eventUid, $eventPid));
            $progressBar->advance();
        }

        $progressBar->finish();

        $io->info('Day records created');

        return $errors;
    }

    /**
     * A failing event must not stop the rebuild of all other events.
     * Throwable is caught, as also errors like TypeError may occur on broken event records.
     *
     * @return string Error message or empty string on success
     */
    protected function createDayRelationsOfEvent(int $eventUid, int $eventPid): string
    {
        try {
            $this->dayRelationService->createDayRelations($eventUid);
        } catch (\Throwable $exception) {
            return sprintf(
                'Event UID: %d, PID: %d, Error: %s, File: %s, Line: %d',
                $eventUid,
                $eventPid,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
            );
        } finally {
            // The runtime cache grows with each processed event (f.e. page TSconfig of each PID)
            $this->runtimeCache->flush();
        }

        return '';
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

    protected function getEventRecordsToProcess(): Result
    {
        return $this->databaseService
            ->getQueryBuilderForAllEvents()
            ->select('uid', 'pid')
            ->executeQuery();
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
